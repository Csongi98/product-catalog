<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CategoryController extends AbstractController
{
    #[Route('/tyres', name: 'tyre_categories')]
    public function tyreCategories(CategoryRepository $cats): Response
    {
        $path = ['Autó, motor', 'Személygépkocsi abroncs'];

        $level2 = $cats->findByPath($path);
        if (!$level2) {
            return $this->render('category/tyres.html.twig', [
                'parentPath' => $path,
                'level2' => null,
                'level3' => [],
            ]);
        }

        $level3 = $cats->findChildren($level2);

        return $this->render('category/tyres.html.twig', [
            'parentPath' => $path,
            'level2' => $level2,
            'level3' => $level3,
        ]);
    }

    #[Route('/category/{id}', name: 'category_show', requirements: ['id' => '\d+'])]
    public function show(Category $category, ProductRepository $products): Response
    {
        $items = $products->createQueryBuilder('p')
            ->andWhere('p.category = :c')->setParameter('c', $category)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults(48)
            ->getQuery()->getResult();

        return $this->render('category/show.html.twig', [
            'category' => $category,
            'products' => $items,
            'page' => 1,
            'lastPage' => 1,
        ]);
    }
}
