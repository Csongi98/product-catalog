<?php

namespace App\Controller\Api;

use App\Entity\Category;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/products', name: 'api_products_')]
class ProductApiController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
public function list(
    Request $req,
    ProductRepository $repo,
    EntityManagerInterface $em
): JsonResponse {
    $page    = max(1, (int) $req->query->get('page', 1));
    $perPage = min(60, max(1, (int) $req->query->get('perPage', 20)));
    $search  = trim((string) $req->query->get('search', ''));
    $catId   = $req->query->get('categoryId');

    $sort    = (string) $req->query->get('sort', 'createdAt');
    $dirRaw  = strtolower((string) $req->query->get('dir', 'desc'));
    $dir     = $dirRaw === 'asc' ? 'ASC' : 'DESC';

    $season    = trim((string) $req->query->get('season', '')); 
    $diameter  = $req->query->get('diameter');  

    $qb = $repo->createQueryBuilder('p');
    $this->applyFilters($qb, $em, $catId, $search, $season, $diameter);

    if ($sort === 'relevance' && $search !== '') {
        $qb->addSelect(
            "CASE 
                WHEN LOWER(p.name) LIKE :qs THEN 2
                WHEN LOWER(p.description) LIKE :qs THEN 1
                ELSE 0
             END AS HIDDEN score"
        )
        ->setParameter('qs', '%'.mb_strtolower($search).'%')
        ->addOrderBy('score', 'DESC')
        ->addOrderBy('p.createdAt', 'DESC');
    } else {
        $map = [
            'name'      => 'p.name',
            'price'     => 'p.price',
            'createdAt' => 'p.createdAt',
        ];
        $col = $map[$sort] ?? 'p.createdAt';
        $qb->orderBy($col, $dir);
    }

    $qb->setFirstResult(($page - 1) * $perPage)
       ->setMaxResults($perPage);

    $items = $qb->getQuery()->getResult();

    $countQb = $repo->createQueryBuilder('p')
        ->select('COUNT(p.id)');
    $this->applyFilters($countQb, $em, $catId, $search, $season, $diameter);
    $total = (int) $countQb->getQuery()->getSingleScalarResult();

    return $this->json([
        'page'    => $page,
        'perPage' => $perPage,
        'total'   => $total,
        'items'   => array_map(static fn($p) => [
            'id'          => $p->getId(),
            'name'        => $p->getName(),
            'price'       => $p->getPrice(),
            'netPrice'    => $p->getNetPrice(),
            'imageUrl'    => $p->getImageUrl(),
            'description' => $p->getDescription(),
        ], $items),
    ]);
}

    private function applyFilters(QueryBuilder $qb, EntityManagerInterface $em, $catId, string $search,string $season = '',
    $diameter = null): void
    {
        if ($catId) {
            $cat = $em->getRepository(Category::class)->find((int) $catId);
            if ($cat) {
                $qb->andWhere('p.category = :c')->setParameter('c', $cat);
            }
        }

        if ($search !== '') {
            $qb->andWhere('LOWER(p.name) LIKE :q OR LOWER(p.description) LIKE :q')
               ->setParameter('q', '%'.mb_strtolower($search).'%');
        }

        if ($season !== '') {
            $season = mb_strtolower($season);

            $map = [
                'nyári'       => ['nyári', 'summer'],
                'téli'        => ['téli', 'winter', 'snowproof', 'snow'],
                '4 évszakos'  => ['4 évszak', '4 évszakos', 'négyévszak', 'all season', 'all-season', 'allseason'],
            ];

            if (isset($map[$season])) {
                $ors = $qb->expr()->orX();
                foreach ($map[$season] as $i => $kw) {
                    $param = "season_kw_$i";
                    $ors->add($qb->expr()->like('LOWER(p.name)', ":$param"));
                    $qb->setParameter($param, '%'.mb_strtolower($kw).'%');
                }
                $qb->andWhere($ors);
            }
        }

        if ($diameter !== null && $diameter !== '' && is_numeric($diameter)) {
            $d = (int)$diameter;

            $or = $qb->expr()->orX(
                $qb->expr()->like('p.name', ':d1'),
                $qb->expr()->like('p.name', ':d2')
            );
            $qb->andWhere($or)
            ->setParameter('d1', '%R'.$d.'%')
            ->setParameter('d2', '%R '.$d.'%');
        }
    }

    #[Route('/random', name: 'random', methods: ['GET'])]
    public function random(ProductRepository $repo): JsonResponse
    {
        $limit = 12;

        $total = (int)$repo->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()->getSingleScalarResult();

        if ($total === 0) {
            return $this->json(['items' => []]);
        }

        $offset = 0;
        if ($total > $limit) {
            $offset = random_int(0, max(0, $total - $limit));
        }

        $items = $repo->createQueryBuilder('p')
            ->orderBy('p.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()->getResult();

        return $this->json([
            'items' => array_map(static fn($p) => [
                'id'          => $p->getId(),
                'name'        => $p->getName(),
                'price'       => $p->getPrice(),
                'netPrice'    => $p->getNetPrice(),
                'imageUrl'    => $p->getImageUrl(),
                'description' => $p->getDescription(),
            ], $items),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id, ProductRepository $repo): JsonResponse
    {
        $p = $repo->find($id);
        if (!$p) {
            return $this->json(['error' => 'Not found'], 404);
        }
        return $this->json([
            'id'          => $p->getId(),
            'name'        => $p->getName(),
            'price'       => $p->getPrice(),
            'netPrice'    => $p->getNetPrice(),
            'imageUrl'    => $p->getImageUrl(),
            'description' => $p->getDescription(),
        ]);
    }


}
