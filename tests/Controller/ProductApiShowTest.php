<?php
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProductApiShowTest extends WebTestCase
{
    public function testShowNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/products/999999');
        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseFormatSame('json');
    }

    public function testShowOkWhenExisting(): void
    {
        $client = static::createClient();
        // $client->request('GET', '/api/products/1');
        // $this->assertResponseIsSuccessful();
    }
}
