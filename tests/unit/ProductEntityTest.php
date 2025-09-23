<?php
namespace App\Tests\Unit;

use App\Entity\Product;
use App\Entity\Category;
use PHPUnit\Framework\TestCase;

/*
 * ProductEntityTest
 * Funkció: a Product entitás getter és setter metódusainak ellenőrzése.
 * Input: új Product és Category példányok
 * Output: sikeres assertion-ök, ha a set értékek helyesen visszaadhatók a get metódusokkal.
 */
class ProductEntityTest extends TestCase
{
    public function testProductGettersAndSetters(): void
    {
        $category = new Category();
        $category->setName('Kategória 1');

        $product = new Product();
        $product->setName('Teszt termék');
        $product->setCategory($category);

        $this->assertSame('Teszt termék', $product->getName());
        $this->assertSame($category, $product->getCategory());
    }
}
