<?php

declare(strict_types=1);

namespace App\Application\Query\SearchProducts;

use App\Application\Port\OpenFoodFactsClientInterface;

final class SearchProductsHandler
{
    public function __construct(
        private readonly OpenFoodFactsClientInterface $client,
    ) {}

    public function handle(SearchProductsQuery $query): array
    {
        return $this->client->searchProducts($query->query, $query->limit);
        if (!empty($query->category)) {
            return $this->client->getProductsByCategory($query->category, $query->limit);
        }

        if (empty($query->query)) {
            return [];
        }

        return $this->client->searchProducts($query->query, $query->limit);
    }
}
