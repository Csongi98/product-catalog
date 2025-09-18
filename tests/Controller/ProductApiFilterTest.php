<?php
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProductApiFilterTest extends WebTestCase
{
    public function testFilterParamsDoNotError(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/products?season=ny%C3%A1ri&diameter=17');
        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('items', $data);
    }
}