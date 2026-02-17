<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Application\Port\OpenFoodFactsClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpenFoodFactsClient implements OpenFoodFactsClientInterface
{
    private const BASE_URL = 'https://world.openfoodfacts.org';
    private const CACHE_TTL = 3600; // 1 heure

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface      $cache,
        #[Autowire(service: 'monolog.logger.off_api')]
        private readonly LoggerInterface     $logger,
    ) {}

    public function searchProducts(string $query, int $limit = 10): array
    {
        $cacheKey = 'off_search_' . md5($query . $limit);
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($query, $limit) {
            $item->expiresAfter(self::CACHE_TTL);

            try {
                $response = $this->httpClient->request('GET', self::BASE_URL . '/cgi/search.pl', [
                    'query' => [
                        'search_terms'  => $query,
                        'search_simple' => 1,
                        'action'        => 'process',
                        'json'          => 1,
                        'page_size'     => $limit,
                        'fields'        => 'code,product_name,brands,nutriscore_grade,image_small_url,categories',
                    ],
                    'timeout' => 5,
                ]);

                $data = $response->toArray();

                $this->logger->info('OFF search', [
                    'query'   => $query,
                    'results' => count($data['products'] ?? []),
                ]);

                return $this->formatProducts($data['products'] ?? []);

            } catch (\Throwable $e) {
                $this->logger->error('OFF API error', [
                    'query' => $query,
                    'error' => $e->getMessage(),
                ]);
                return [];
            }
        });
    }

    public function getProductByBarcode(string $barcode): ?array
    {
        $cacheKey = 'off_product_' . $barcode;

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($barcode) {
            $item->expiresAfter(self::CACHE_TTL);

            try {
                $response = $this->httpClient->request(
                    'GET',
                    self::BASE_URL . '/api/v2/product/' . $barcode . '.json',
                    ['timeout' => 5]
                );

                $data = $response->toArray();

                if (($data['status'] ?? 0) !== 1) {
                    return null;
                }

                return $this->formatProduct($data['product'] ?? []);

            } catch (\Throwable $e) {
                $this->logger->error('OFF API barcode error', [
                    'barcode' => $barcode,
                    'error'   => $e->getMessage(),
                ]);
                return null;
            }
        });
    }

    public function getProductsByCategory(string $category, int $limit = 5): array
    {
        $cacheKey = 'off_category_' . md5($category . $limit);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($category, $limit) {
            $item->expiresAfter(self::CACHE_TTL);

            try {
                $response = $this->httpClient->request(
                    'GET',
                    self::BASE_URL . '/category/' . urlencode($category) . '.json',
                    [
                        'query'   => ['page_size' => $limit],
                        'timeout' => 5,
                    ]
                );

                $data = $response->toArray();

                return $this->formatProducts($data['products'] ?? []);

            } catch (\Throwable $e) {
                $this->logger->error('OFF category error', [
                    'category' => $category,
                    'error'    => $e->getMessage(),
                ]);
                return [];
            }
        });
    }

    private function formatProducts(array $products): array
    {
        return array_map(fn($p) => $this->formatProduct($p), $products);
    }

    private function formatProduct(array $product): array
    {
        return [
            'barcode'        => $product['code'] ?? $product['_id'] ?? '',
            'name'           => $product['product_name'] ?? 'Produit inconnu',
            'brand'          => $product['brands'] ?? '',
            'nutriscore'     => strtoupper($product['nutriscore_grade'] ?? '?'),
            'image'          => $product['image_small_url'] ?? null,
            'categories'     => $product['categories'] ?? '',
        ];
    }
}
