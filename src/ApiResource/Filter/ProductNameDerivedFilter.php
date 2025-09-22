<?php
namespace App\ApiResource\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

final class ProductNameDerivedFilter extends AbstractFilter
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
        if (!\in_array($property, ['season', 'diameter'], true) || $value === null || $value === '') {
            return;
        }
        if (!$this->isPropertyEnabled($property, $resourceClass)) {
            return;
        }

        $alias = $this->getAlias($queryBuilder, $resourceClass);

        if ($property === 'season') {
            $val = mb_strtolower(trim((string) $value));

            $patterns = match ($val) {
                'nyári', 'nyari' => ['%nyári%', '%nyari%', '%summer%'],
                'téli', 'teli'   => ['%téli%', '%teli%', '%winter%', '%snow%', '%snowproof%'],
                '4 évszakos', 'négy évszakos', 'all season', 'négyévszakos' =>
                    ['%4 évszak%', '%4évszak%', '%négy%évszak%', '%all season%', '%all-season%', '%allseason%'],
                default => [],
            };

            if ($patterns) {
                $orX = $queryBuilder->expr()->orX();
                foreach ($patterns as $pat) {
                    $param = $queryNameGenerator->generateParameterName('season');
                    $orX->add($queryBuilder->expr()->like(sprintf('LOWER(%s.name)', $alias), ':' . $param));
                    $queryBuilder->setParameter($param, mb_strtolower($pat));
                }
                $queryBuilder->andWhere($orX);
            }
        }

        if ($property === 'diameter') {
            $d = (int) $value;
            if ($d > 0) {
                $paramR = $queryNameGenerator->generateParameterName('diameter_r');
                $paramInch = $queryNameGenerator->generateParameterName('diameter_inch');

                $queryBuilder
                    ->andWhere(
                        $queryBuilder->expr()->orX(
                            $queryBuilder->expr()->like(sprintf('LOWER(%s.name)', $alias), ':' . $paramR),
                            $queryBuilder->expr()->like(sprintf('LOWER(%s.name)', $alias), ':' . $paramInch)
                        )
                    )
                    ->setParameter($paramR, '%r' . $d . '%')
                    ->setParameter($paramInch, '% ' . $d . '"%');
            }
        }
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'season' => [
                'property' => 'season',
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'openapi' => [
                    'description' => 'Évszak névből (nyári / téli / 4 évszakos, all season).',
                    'example' => 'nyári',
                ],
            ],
            'diameter' => [
                'property' => 'diameter',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Felni átmérő névből (pl. R16 vagy 16").',
                    'example' => 16,
                ],
            ],
        ];
    }

    private function getAlias(QueryBuilder $qb, string $resourceClass): string
    {
        $rootAliases = $qb->getRootAliases();
        return $rootAliases[0] ?? 'o';
    }
}
