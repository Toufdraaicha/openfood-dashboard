<?php

declare(strict_types=1);

namespace App\Application\Query\SearchProducts;

final readonly class SearchProductsQuery
{
    public function __construct(
        public string $query,
        public int    $limit = 10,
        public string $category = '',
    ) {}
}
