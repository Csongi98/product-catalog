<?php
namespace App\Tests\Repository;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/*
 * ProductListExistingTest
 * Funkció: ellenőrzi, hogy a ProductRepository képes-e lekérni létező termékeket.
 * Input: tesztadatbázis (feltöltve termékekkel)
 * Output: sikeres lekérés, a Product entitások valid mezőkkel
 */
class ProductListExistingTest extends KernelTestCase
{
    public function testListExistingProducts(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        /** @var ProductRepository $repo */
        $repo = $container->get(ProductRepository::class);

        $products = $repo->findBy([], ['id' => 'ASC'], 20);

        if (count($products) === 0) {
            $this->markTestSkipped('Nincs product a teszt adatbázisban – read-only teszt.');
        }

        $this->assertIsArray($products);

        foreach ($products as $p) {
            $this->assertInstanceOf(Product::class, $p);
            $this->assertNotEmpty($p->getId(), 'Product ID hiányzik.');
            $this->assertNotEmpty($p->getName(), 'Product name hiányzik.');
        }
    }
}
