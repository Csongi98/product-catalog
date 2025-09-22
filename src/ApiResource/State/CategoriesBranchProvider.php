<?php

namespace App\ApiResource\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\CategoryRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

final class CategoriesBranchProvider implements ProviderInterface
{
    public function __construct(
        private readonly CategoryRepository $categories,
        private readonly RequestStack $requestStack
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): JsonResponse
    {
        $req = $this->requestStack->getCurrentRequest();
        $path = (string)($req?->query->get('path', 'Autó, motor>Személygépkocsi abroncs') ?? '');

        $node = $this->categories->findByPathString($path);
        if (!$node) {
            return new JsonResponse([
                'branchName' => $path,
                'parentId' => null,
                'children' => [],
                'message' => 'A megadott útvonal nem található.',
            ]);
        }

        $children = $this->categories->findChildren($node);

        return new JsonResponse([
            'branchName' => $path,
            'parentId' => $node->getId(),
            'children' => array_map(static fn($c) => [
                'id' => $c->getId(),
                'name' => $c->getName(),
            ], $children),
        ]);
    }
}
