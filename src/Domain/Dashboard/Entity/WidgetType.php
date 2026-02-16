<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Entity;

enum WidgetType: string
{
    case ProductsSearch  = 'products_search';
    case NutriScoreStats = 'nutri_score_stats';
    case CategoryTop     = 'category_top';
    case ProductDetail   = 'product_detail';

    public function label(): string
    {
        return match($this) {
            self::ProductsSearch  => 'Recherche de produits',
            self::NutriScoreStats => 'Stats Nutri-Score',
            self::CategoryTop     => 'Top catégorie',
            self::ProductDetail   => 'Détail produit',
        };
    }

    public function defaultTitle(): string
    {
        return match($this) {
            self::ProductsSearch  => 'Recherche OFF',
            self::NutriScoreStats => 'Nutri-Score',
            self::CategoryTop     => 'Top produits',
            self::ProductDetail   => 'Produit',
        };
    }

    public function defaultConfig(): array
    {
        return match($this) {
            self::ProductsSearch  => ['query' => '', 'limit' => 10],
            self::NutriScoreStats => ['category' => 'snacks'],
            self::CategoryTop     => ['category' => 'beverages', 'limit' => 5],
            self::ProductDetail   => ['barcode' => ''],
        };
    }
}
