<?php

namespace App\ApiResource\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;


final class RandomProductsProvider implements ProviderInterface
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): iterable
    {
        $limit = 12;

        $total = (int) $this->em->getRepository(Product::class)
            ->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();

        if ($total === 0) {
            return [];
        }

        $offset = $total > $limit
            ? random_int(0, max(0, $total - $limit))
            : 0;

        return $this->em->getRepository(Product::class)
            ->createQueryBuilder('p')
            ->orderBy('p.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
