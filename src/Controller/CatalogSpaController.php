<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CatalogSpaController extends AbstractController
{
    #[Route('/catalog', name: 'catalog_spa')]
    public function __invoke(): Response
    {
        return $this->render('spa/catalog.html.twig');
    }
}
