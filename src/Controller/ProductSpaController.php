<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProductSpaController extends AbstractController
{
    #[Route('/product/{id}', name: 'product_spa', requirements: ['id' => '\d+'])]
    public function __invoke(int $id): Response
    {
        return $this->render('product/show.html.twig');
    }
}
