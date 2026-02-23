<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Http;

use App\Infrastructure\Http\OpenFoodFactsClient;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class OpenFoodFactsClientTest extends TestCase
{
    private function createClient(array $responses): OpenFoodFactsClient
    {
        $mockResponses = array_map(
            fn ($data) => new MockResponse(json_encode($data)),
            $responses
        );

        $httpClient = new MockHttpClient($mockResponses);
        $cache = new ArrayAdapter();
        $logger = new NullLogger();

        return new OpenFoodFactsClient(
            $httpClient,
            $cache,
            $logger,
            'https://world.openfoodfacts.org'
        );
    }

    public function testSearchProducts(): void
    {
        $client = $this->createClient([
            [
                'products' => [
                    [
                        'code' => '123',
                        'product_name' => 'Nutella',
                        'brands' => 'Ferrero',
                        'nutriscore_grade' => 'e',
                        'image_small_url' => 'img.jpg',
                        'categories' => 'Pâte à tartiner',
                    ],
                ],
            ],
        ]);

        $results = $client->searchProducts('nutella');

        $this->assertCount(1, $results);
        $this->assertSame('123', $results[0]['barcode']);
        $this->assertSame('Nutella', $results[0]['name']);
        $this->assertSame('E', $results[0]['nutriscore']);
    }

    public function testGetProductByBarcode(): void
    {
        $client = $this->createClient([
            [
                'status' => 1,
                'product' => [
                    'code' => '456',
                    'product_name' => 'Coca Cola',
                    'brands' => 'Coca-Cola',
                    'nutriscore_grade' => 'd',
                ],
            ],
        ]);

        $product = $client->getProductByBarcode('456');

        $this->assertNotNull($product);
        $this->assertSame('456', $product['barcode']);
        $this->assertSame('Coca Cola', $product['name']);
    }

    public function testGetProductByBarcodeNotFound(): void
    {
        $client = $this->createClient([
            [
                'status' => 0,
            ],
        ]);

        $product = $client->getProductByBarcode('999');

        $this->assertNull($product);
    }

    public function testGetProductsByCategory(): void
    {
        $client = $this->createClient([
            [
                'products' => [
                    [
                        'code' => '789',
                        'product_name' => 'Yaourt',
                        'nutriscore_grade' => 'b',
                    ],
                ],
            ],
        ]);

        $results = $client->getProductsByCategory('yaourts');

        $this->assertCount(1, $results);
        $this->assertSame('789', $results[0]['barcode']);
    }

    public function testApiErrorReturnsEmptyArray(): void
    {
        $httpClient = new MockHttpClient([
            new MockResponse('API error', ['http_code' => 500]),
        ]);

        $client = new OpenFoodFactsClient(
            $httpClient,
            new ArrayAdapter(),
            new NullLogger(),
            'https://world.openfoodfacts.org'
        );

        $results = $client->searchProducts('error');

        $this->assertSame([], $results);
    }
}
