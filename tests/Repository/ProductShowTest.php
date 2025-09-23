<?php
namespace App\Tests\Repository;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/*
 * ProductShowTest
 * Funkció: ellenőrzi, hogy egy adott termék azonosító (ID) alapján lekérhető-e.
 * Input: tesztadatbázis (legalább 1 Product entitás)
 * Output: sikeres lekérés esetén helyes Product példány, helyes ID és nem üres név
 */
class ProductShowTest extends KernelTestCase
{
    public function testFindProductById(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        /** @var ProductRepository $repo */
        $repo = $container->get(ProductRepository::class);

        $first = $repo->findOneBy([], ['id' => 'ASC']);

        if (!$first) {
            $this->markTestSkipped('Nincs product a teszt adatbázisban – nem tudunk ID alapján keresni.');
        }

        $id = $first->getId();

        $found = $repo->find($id);

        $this->assertInstanceOf(Product::class, $found);
        $this->assertSame($id, $found->getId());
        $this->assertNotEmpty($found->getName(), 'Product név üres.');
    }
}
