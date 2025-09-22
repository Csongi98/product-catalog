<?php
namespace App\ApiResource\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

final class RelevanceOrderFilter extends AbstractFilter
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
        if ($property !== 'sort' || $value !== 'relevance') {
            return;
        }

        $filters = $context['filters'] ?? [];
        $search = (string)($filters['search'] ?? '');

        if ($search === '') {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $param = $queryNameGenerator->generateParameterName('qs');

        $queryBuilder->addSelect(
            sprintf(
                "CASE 
                    WHEN LOWER(%s.name) LIKE :%s THEN 2
                    WHEN LOWER(%s.description) LIKE :%s THEN 1
                    ELSE 0
                 END AS HIDDEN score",
                $alias, $param, $alias, $param
            )
        )
        ->setParameter($param, '%' . mb_strtolower($search) . '%')
        ->addOrderBy('score', 'DESC')
        ->addOrderBy($alias . '.createdAt', 'DESC');
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'sort' => [
                'property' => 'sort',
                'type' => 'string',
                'required' => false,
                'openapi' => [
                    'description' => 'Sorting mode. Use "relevance" together with ?search=...',
                    'example' => 'relevance',
                ],
            ],
        ];
    }
}
