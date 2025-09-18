<?php

namespace App\Tests\Repository;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Repository\ProductRepository;

class ProductRepositoryTest extends KernelTestCase
{
    public function testFindAllWorks(): void
    {
        self::bootKernel();
        $repo = static::getContainer()->get(ProductRepository::class);

        $products = $repo->findAll();

        $this->assertIsArray($products);
        
        $this->assertNotNull($products);
    }
}
