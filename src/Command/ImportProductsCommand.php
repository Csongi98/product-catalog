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

        $discard = fgetcsv($handle, 0, $del);

        $this->em->getConnection()->beginTransaction();
        $repoProduct  = $this->em->getRepository(Product::class);
        $repoCategory = $this->em->getRepository(Category::class);

        $processed = 0;
        $createdProducts = 0;
        $skippedDuplicate = 0;
        $skippedInvalid   = 0;

        try {
            while (($raw = fgetcsv($handle, 0, $del)) !== false) {
                $line = $this->joinCellsWithDelimiter($raw, $del);
                $line = preg_replace('/^\xEF\xBB\xBF/', '', $line);

                $row  = array_map('trim', explode($del, $line));
                $row  = array_pad($row, 7, null);

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

                if ($repoProduct->findOneBy(['identifier' => $identifier])) {
                    $skippedDuplicate++;
                    continue;
                }

                $parts = $this->breadcrumbToParts((string)$catBreadcrumb);
                $leafCategory = $this->findOrCreateCategoryChain($parts, $repoCategory);
                if (!$leafCategory) {
                    $leafCategory = $this->findOrCreateCategoryChain(['Ismeretlen'], $repoCategory);
                }

                $priceCents = $this->toCents($price);
                $netCents   = ($netPrice !== null && $netPrice !== '') ? $this->toCents($netPrice) : null;

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

    private function joinCellsWithDelimiter(array $cells, string $del = ';'): string
    {
        $out = '';
        $first = true;

        foreach ($cells as $cell) {
            $cell = (string)$cell;
            if ($first) {
                $cell = preg_replace('/^\xEF\xBB\xBF/', '', $cell);
                $out = $cell;
                $first = false;
                continue;
            }

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
            $out .= ltrim($cell, " \t\r\n");
        }

        return trim($out);
    }


    private function breadcrumbToParts(?string $breadcrumb): array
    {
        if (!$breadcrumb) {
            return ['Ismeretlen'];
        }
        $parts = array_map('trim', explode('>', $breadcrumb));
        $parts = array_values(array_filter($parts, fn($x) => $x !== ''));
        return $parts ?: ['Ismeretlen'];
    }


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


    private function toCents(null|string|int|float $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }
        $s = (string) $value;
        $s = str_replace(["\u{00A0}", ' '], '', $s);
        $s = str_replace(',', '.', $s);
        $f = (float) $s;
        return (int) round($f * 100);
    }
}
