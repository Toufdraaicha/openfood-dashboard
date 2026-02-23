<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Application\Port\OpenFoodFactsClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpenFoodFactsClient implements OpenFoodFactsClientInterface
{
    private const CACHE_TTL = 10800; //   heure
    private const TIMEOUT = 20;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
        #[Autowire(service: 'monolog.logger.off_api')]
        private readonly LoggerInterface $logger,
        #[Autowire('%env(OPENFOODFACTS_BASE_URL)%')]
        private readonly string $baseUrl,
    ) {
    }

    public function searchProducts(string $query, int $limit = 10): array
    {
        $cacheKey = 'off_search_' . md5($query . $limit);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($query, $limit) {
            $item->expiresAfter(self::CACHE_TTL);

            $start = microtime(true);

            try {

                $response = $this->httpClient->request('GET', $this->baseUrl . '/cgi/search.pl', [
                    'query' => [

                        'search_simple' => 1,
                        'action' => 'process',
                        'json' => 1,
                        'page_size' => $limit,
                        'fields' => 'code,product_name,brands,nutriscore_grade,image_small_url,categories',
                    ],
                    'timeout' => self::TIMEOUT,
                ]);

                $data = $response->toArray();
                $duration = round((microtime(true) - $start) * 1000);

                $this->logger->info('OFF search success', [
                    'query' => $query,
                    'limit' => $limit,
                    'results' => count($data['products'] ?? []),
                    'duration_ms' => $duration,
                ]);

                return $this->formatProducts($data['products'] ?? []);
            } catch (ClientExceptionInterface | ServerExceptionInterface $e) {

                $this->logger->error('OFF HTTP error during search', [
                    'query' => $query,
                    'limit' => $limit,
                    'exception' => $e,
                ]);
            } catch (TransportExceptionInterface $e) {
                $this->logger->critical('OFF network error during search', [
                    'query' => $query,
                    'exception' => $e,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Unexpected OFF error during search', [
                    'query' => $query,
                    'exception' => $e,
                ]);
            }

            return [];
        });
    }

    public function getProductByBarcode(string $barcode): ?array
    {
        $cacheKey = 'off_product_' . $barcode;

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($barcode) {
            $item->expiresAfter(self::CACHE_TTL);

            $start = microtime(true);

            try {
                $response = $this->httpClient->request(
                    'GET',
                    $this->baseUrl . '/api/v2/product/' . $barcode . '.json',
                    ['timeout' => self::TIMEOUT]
                );

                $data = $response->toArray();
                $duration = round((microtime(true) - $start) * 1000);

                if (($data['status'] ?? 0) !== 1) {
                    $this->logger->warning('OFF product not found', [
                        'barcode' => $barcode,
                        'duration_ms' => $duration,
                    ]);

                    return null;
                }

                $this->logger->info('OFF barcode success', [
                    'barcode' => $barcode,
                    'duration_ms' => $duration,
                ]);

                return $this->formatProduct($data['product'] ?? []);
            } catch (ClientExceptionInterface | ServerExceptionInterface $e) {
                $this->logger->error('OFF HTTP error during barcode lookup', [
                    'barcode' => $barcode,
                    'exception' => $e,
                ]);
            } catch (TransportExceptionInterface $e) {
                $this->logger->critical('OFF network error during barcode lookup', [
                    'barcode' => $barcode,
                    'exception' => $e,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Unexpected OFF error during barcode lookup', [
                    'barcode' => $barcode,
                    'exception' => $e,
                ]);
            }

            return null;
        });
    }

    public function getProductsByCategory(string $category, int $limit = 5): array
    {
        $cacheKey = 'off_category_' . md5($category . $limit);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($category, $limit) {
            $item->expiresAfter(self::CACHE_TTL);

            $start = microtime(true);

            try {
                $response = $this->httpClient->request(
                    'GET',
                    $this->baseUrl . '/category/' . urlencode($category) . '.json',
                    [
                        'query' => ['page_size' => $limit],
                        'timeout' => self::TIMEOUT,
                    ]
                );

                $data = $response->toArray();
                $duration = round((microtime(true) - $start) * 1000);

                $this->logger->info('OFF category success', [
                    'category' => $category,
                    'limit' => $limit,
                    'results' => count($data['products'] ?? []),
                    'duration_ms' => $duration,
                ]);

                return $this->formatProducts($data['products'] ?? []);
            } catch (ClientExceptionInterface | ServerExceptionInterface $e) {
                $this->logger->error('OFF HTTP error during category lookup', [
                    'category' => $category,
                    'exception' => $e,
                ]);
            } catch (TransportExceptionInterface $e) {
                $this->logger->critical('OFF network error during category lookup', [
                    'category' => $category,
                    'exception' => $e,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Unexpected OFF error during category lookup', [
                    'category' => $category,
                    'exception' => $e,
                ]);
            }

            return [];
        });
    }

    private function formatProducts(array $products): array
    {
        return array_map(fn ($product) => $this->formatProduct($product), $products);
    }

    private function formatProduct(array $product): array
    {
        return [
            'barcode' => $product['code'] ?? $product['_id'] ?? '',
            'name' => $product['product_name'] ?? 'Produit inconnu',
            'brand' => $product['brands'] ?? '',
            'nutriscore' => strtoupper($product['nutriscore_grade'] ?? '?'),
            'image' => $product['image_small_url'] ?? null,
            'categories' => $product['categories'] ?? '',
        ];
    }
}


