<?php

namespace App\Controller\Api;

use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/categories', name: 'api_categories_')]
class CategoryApiController extends AbstractController
{
    #[Route('/branch', name: 'branch', methods: ['GET'])]
    public function branch(Request $request, CategoryRepository $cats): JsonResponse
    {
        $path = (string) $request->query->get('path', 'Autó, motor>Személygépkocsi abroncs');

        $node = $cats->findByPathString($path);
        if (!$node) {
            return $this->json([
                'branchName' => $path,
                'parentId'   => null,
                'children'   => [],
                'message'    => 'A megadott útvonal nem található.',
            ]);
        }

        $children = $cats->findChildren($node);

        return $this->json([
            'branchName' => $path,
            'parentId'   => $node->getId(),
            'children'   => array_map(static fn($c) => [
                'id'   => $c->getId(),
                'name' => $c->getName(),
            ], $children),
        ]);
    }
}
