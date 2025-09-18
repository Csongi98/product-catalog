<?php

namespace App\Command;

use App\Entity\Category;
use App\Entity\Product;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-products',
    description: 'Import products from CSV; adatok a 2. sortól, soronként a teljes sor (A..Z) összefűzve, majd ; szerint bontva.'
)]
class ImportProductsCommand extends Command
{
    /** Egyszerű in-memory cache: "<parentId>|<name>" => Category */
    private array $categoryCache = [];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Connection $conn,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('csv', InputArgument::REQUIRED, 'Path to CSV file')
            ->addOption('delimiter', 'd', InputOption::VALUE_REQUIRED, 'CSV mezőelválasztó', ';')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Próba: ne írjon az adatbázisba')
            ->addOption('truncate', null, InputOption::VALUE_NONE, 'Import előtt ürítse a product és category táblát (FIGYELEM!)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io        = new SymfonyStyle($input, $output);
        $path      = (string) $input->getArgument('csv');
        $del       = (string) $input->getOption('delimiter');
        $dry       = (bool) $input->getOption('dry-run');
        $truncate  = (bool) $input->getOption('truncate');

        if (!is_readable($path)) {
            $io->error("CSV nem olvasható: $path");
            return Command::FAILURE;
        }

        if ($truncate && !$dry) {
            $io->warning('TRUNCATE: product + category ürítése…');
            $this->conn->executeStatement('SET FOREIGN_KEY_CHECKS=0');
            $this->conn->executeStatement('TRUNCATE TABLE product');
            $this->conn->executeStatement('TRUNCATE TABLE category');
            $this->conn->executeStatement('SET FOREIGN_KEY_CHECKS=1');
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            $io->error("Nem sikerült megnyitni: $path");
            return Command::FAILURE;
        }

        // --- 1) Az első sort TELJESEN kihagyjuk (A1-ben csak cím van)
        $discard = fgetcsv($handle, 0, $del); // ignore header row completely

        // --- Import tranzakció
        $this->em->getConnection()->beginTransaction();
        $repoProduct  = $this->em->getRepository(Product::class);
        $repoCategory = $this->em->getRepository(Category::class);

        $processed = 0;
        $createdProducts = 0;
        $skippedDuplicate = 0;
        $skippedInvalid   = 0;

        try {
            // 2. sortól dolgozunk
            while (($raw = fgetcsv($handle, 0, $del)) !== false) {
                // Sor összefűzése intelligensen: közé ; ha hiányzik
                $line = $this->joinCellsWithDelimiter($raw, $del);
                // BOM le:
                $line = preg_replace('/^\xEF\xBB\xBF/', '', $line);

                // ; szerinti bontás
                $row  = array_map('trim', explode($del, $line));
                // legyen pontosan 7 mező
                $row  = array_pad($row, 7, null);

                // elvárt sorrend:
                // 0 identifier, 1 name, 2 category_id, 3 category (breadcrumb),
                // 4 price, 5 net_price, 6 image_url
                $identifier    = $row[0] ?? null;
                $name          = $row[1] ?? null;
                $catBreadcrumb = $row[3] ?? null;
                $price         = $row[4] ?? null;
                $netPrice      = $row[5] ?? null;
                $imageUrl      = $row[6] ?? null;

                if (!$identifier || !$name) {
                    $skippedInvalid++;
                    continue;
                }

                // duplikáció: identifier egyedi
                if ($repoProduct->findOneBy(['identifier' => $identifier])) {
                    $skippedDuplicate++;
                    continue;
                }

                // --- kategóriafa felépítése breadcrumbból ---
                $parts = $this->breadcrumbToParts((string)$catBreadcrumb);
                $leafCategory = $this->findOrCreateCategoryChain($parts, $repoCategory);
                if (!$leafCategory) {
                    $leafCategory = $this->findOrCreateCategoryChain(['Ismeretlen'], $repoCategory);
                }

                // --- árak normalizálása (Ft -> fillér) ---
                $priceCents = $this->toCents($price);
                $netCents   = ($netPrice !== null && $netPrice !== '') ? $this->toCents($netPrice) : null;

                // --- termék ---
                $p = new Product();
                $p->setIdentifier((string)$identifier);
                $p->setName((string)$name);
                $p->setPrice($priceCents);
                $p->setNetPrice($netCents);
                $p->setImageUrl($imageUrl ?: null);
                $p->setDescription('Lorem ipsum dolor sit amet…');
                $p->setCreatedAt(new \DateTimeImmutable());
                $p->setCategory($leafCategory);

                if (!$dry) {
                    $this->em->persist($p);
                }

                $createdProducts++;
                $processed++;

                // batch flush 200-asával
                if ($processed % 200 === 0 && !$dry) {
                    $this->em->flush();
                    $this->em->clear();
                    $this->categoryCache = [];
                    $repoProduct  = $this->em->getRepository(Product::class);
                    $repoCategory = $this->em->getRepository(Category::class);
                }
            }

            fclose($handle);

            if (!$dry) {
                $this->em->flush();
                $this->em->getConnection()->commit();
            } else {
                $this->em->getConnection()->rollBack();
            }

            $io->success(sprintf(
                'Import kész. Termékek: %d, duplikált: %d, hibás: %d%s',
                $createdProducts, $skippedDuplicate, $skippedInvalid, $dry ? ' (dry-run)' : ''
            ));
            return Command::SUCCESS;

        } catch (\Throwable $e) {
            if ($this->em->getConnection()->isTransactionActive()) {
                $this->em->getConnection()->rollBack();
            }
            if (is_resource($handle)) {
                fclose($handle);
            }
            $io->error('Hiba import közben: '.$e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Egy teljes sor celláin végigmegy, és egymás után fűzi őket.
     * Ha két cella határán hiányzik a pontosvessző, beszúr egyet.
     */
    private function joinCellsWithDelimiter(array $cells, string $del = ';'): string
    {
        $out = '';
        $first = true;

        foreach ($cells as $cell) {
            $cell = (string)$cell;
            if ($first) {
                // első cella: csak BOM-ot szedjük le az elejéről
                $cell = preg_replace('/^\xEF\xBB\xBF/', '', $cell);
                $out = $cell;
                $first = false;
                continue;
            }

            // kell-e delimiter a határra?
            $needDel = true;
            if ($out !== '' && str_ends_with($out, $del)) {
                $needDel = false;
            }
            if ($cell !== '' && str_starts_with($cell, $del)) {
                $needDel = false;
            }

            if ($needDel) {
                $out .= $del;
            }
            $out .= ltrim($cell, " \t\r\n"); // bal oldali whitespace le
        }

        return trim($out);
    }

    /** "Autó, motor > Személygépkocsi abroncs > Barum" -> ["Autó, motor","Személygépkocsi abroncs","Barum"] */
    private function breadcrumbToParts(?string $breadcrumb): array
    {
        if (!$breadcrumb) {
            return ['Ismeretlen'];
        }
        $parts = array_map('trim', explode('>', $breadcrumb));
        $parts = array_values(array_filter($parts, fn($x) => $x !== ''));
        return $parts ?: ['Ismeretlen'];
    }

    /**
     * Végigmegy a részeken és (parent, name) alapján keres/létrehoz Category-t.
     * Visszaadja a LEVÉL node-ot (ehhez kötjük a terméket).
     */
    private function findOrCreateCategoryChain(array $parts, $repoCategory): ?Category
    {
        $parent = null;

        foreach ($parts as $name) {
            $key = ($parent?->getId() ?? 0) . '|' . $name;

            if (isset($this->categoryCache[$key])) {
                $current = $this->categoryCache[$key];
            } else {
                $current = $repoCategory->findOneBy(['name' => $name, 'parent' => $parent]);
                if (!$current) {
                    $current = new Category();
                    $current->setName($name);
                    $current->setParent($parent);
                    $current->setCreatedAt(new \DateTimeImmutable());
                    $this->em->persist($current);
                }
                $this->categoryCache[$key] = $current;
            }

            $parent = $current;
        }

        return $parent;
    }

    /** "12 345,67" → 1234567 (fillér) */
    private function toCents(null|string|int|float $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }
        $s = (string) $value;
        $s = str_replace(["\u{00A0}", ' '], '', $s); // nem törhető + normál space
        $s = str_replace(',', '.', $s);
        $f = (float) $s;
        return (int) round($f * 100);
    }
}
