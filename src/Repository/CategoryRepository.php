<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function findByPathString(string $path, string $sep = '>'): ?Category
    {
        $names = array_map('trim', explode($sep, $path));
        $parent = null;

        foreach ($names as $name) {
            if ($name === '') {
                return null;
            }
            $category = $this->findOneBy(['name' => $name, 'parent' => $parent]);
            if (!$category) {
                return null;
            }
            $parent = $category;
        }

        return $parent; 
    }

    public function findChildren(Category $parent): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.parent = :p')->setParameter('p', $parent)
            ->orderBy('c.name', 'ASC')
            ->getQuery()->getResult();
    }
}
