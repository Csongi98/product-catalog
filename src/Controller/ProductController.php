<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProductController extends AbstractController
{
    #[Route('/products', name: 'product_index')]
    public function index(ProductRepository $repo, Request $request): Response
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = 20;

        $qb = $repo->createQueryBuilder('p')
            ->orderBy('p.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage);

        $items = $qb->getQuery()->getResult();

        $total = (int) $repo->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $lastPage = (int) ceil($total / $perPage);

        return $this->render('product/index.html.twig', [
            'products' => $items,
            'page' => $page,
            'lastPage' => $lastPage,
        ]);
    }

    #[Route('/product/{id}', name: 'product_show', requirements: ['id' => '\d+'])]
    public function show(int $id, ProductRepository $repo): Response
    {
        $product = $repo->find($id);

        if (!$product) {
            throw $this->createNotFoundException('A termék nem található.');
        }

        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

}
