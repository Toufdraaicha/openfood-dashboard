<?php

declare(strict_types=1);

namespace App\Application\Port;

interface OpenFoodFactsClientInterface
{
    public function searchProducts(string $query, int $limit = 10): array;
    public function getProductByBarcode(string $barcode): ?array;
    public function getProductsByCategory(string $category, int $limit = 5): array;
}
