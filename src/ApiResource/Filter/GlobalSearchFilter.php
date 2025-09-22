<?php
namespace App\ApiResource\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

final class GlobalSearchFilter extends AbstractFilter
{
    protected function filterProperty(
        string $property,
        mixed $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        if ($property !== 'search' || $value === null || $value === '') {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $param = $queryNameGenerator->generateParameterName('search');

        $expr = $queryBuilder->expr()->orX(
            $queryBuilder->expr()->like(sprintf('LOWER(%s.name)', $alias), ':' . $param),
            $queryBuilder->expr()->like(sprintf('LOWER(%s.description)', $alias), ':' . $param)
        );

        $queryBuilder
            ->andWhere($expr)
            ->setParameter($param, '%' . mb_strtolower((string) $value) . '%');
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'search' => [
                'property' => 'search',
                'type' => 'string',
                'required' => false,
                'openapi' => [
                    'description' => 'Szöveg szerinti keresés a termék nevében és leírásában.',
                    'example' => 'michelin',
                ],
            ],
        ];
    }
}
