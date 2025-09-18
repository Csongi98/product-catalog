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

        // 1) Lista lekérdezés
        $qb = $repo->createQueryBuilder('p');
        $this->applyFilters($qb, $em, $catId, $search);
        $qb->orderBy('p.createdAt', 'DESC')
           ->setFirstResult(($page - 1) * $perPage)
           ->setMaxResults($perPage);
        $items = $qb->getQuery()->getResult();

        // 2) Count lekérdezés – ugyanazokat a szűrőket ÉS feltételeket külön ráhúzzuk
        $countQb = $repo->createQueryBuilder('p');
        $countQb->select('COUNT(p.id)');
        $this->applyFilters($countQb, $em, $catId, $search);
        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        return $this->json([
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
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

    private function applyFilters(QueryBuilder $qb, EntityManagerInterface $em, $catId, string $search): void
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
    }
}
