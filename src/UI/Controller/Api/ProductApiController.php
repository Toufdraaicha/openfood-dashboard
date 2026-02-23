<?php

declare(strict_types=1);

namespace App\UI\Controller\Api;

use App\Application\Port\OpenFoodFactsClientInterface;
use App\Application\Query\SearchProducts\SearchProductsHandler;
use App\Application\Query\SearchProducts\SearchProductsQuery;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_')]
#[OA\Tag(name: 'Produits')]
#[Security(name: 'Bearer')]
class ProductApiController extends AbstractController
{
    public function __construct(
        private readonly SearchProductsHandler $searchHandler,
        private readonly OpenFoodFactsClientInterface $offClient,
    ) {
    }

    #[Route('/products/search', name: 'products_search', methods: ['GET'])]
    #[OA\Get(
        path: '/api/products/search',
        summary: 'Rechercher des produits',
        parameters: [
            new OA\Parameter(
                name: 'q',
                in: 'query',
                description: 'Terme de recherche',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'coca cola')
            ),
            new OA\Parameter(
                name: 'category',
                in: 'query',
                description: 'Catégorie de produits',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'beverages')
            ),
            new OA\Parameter(
                name: 'limit',
                in: 'query',
                description: 'Nombre de résultats',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 10, maximum: 50)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste de produits',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'query', type: 'string'),
                        new OA\Property(property: 'total', type: 'integer'),
                        new OA\Property(
                            property: 'products',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'barcode', type: 'string', example: '3017620422003'),
                                    new OA\Property(property: 'name', type: 'string', example: 'Nutella'),
                                    new OA\Property(property: 'brand', type: 'string', example: 'Ferrero'),
                                    new OA\Property(property: 'nutriscore', type: 'string', example: 'E'),
                                    new OA\Property(property: 'image', type: 'string', nullable: true),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Paramètre manquant'),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]
    public function search(Request $request): JsonResponse
    {
        $query = $request->query->getString('q');
        $limit = $request->query->getInt('limit', 10);
        $category = $request->query->getString('category');

        $products = $this->searchHandler->handle(
            new SearchProductsQuery($query, $limit, $category)
        );

        return $this->json([
            'query' => $query,
            'total' => count($products),
            'products' => $products,
        ]);
    }

    #[Route('/products/{barcode}', name: 'product_detail', methods: ['GET'])]
    #[OA\Get(
        path: '/api/products/{barcode}',
        summary: 'Détail d\'un produit par code-barres',
        parameters: [
            new OA\Parameter(
                name: 'barcode',
                in: 'path',
                description: 'Code-barres du produit',
                required: true,
                schema: new OA\Schema(type: 'string', example: '3017620422003')
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Détail produit'),
            new OA\Response(response: 404, description: 'Produit non trouvé'),
        ]
    )]
    public function detail(string $barcode): JsonResponse
    {
        $product = $this->offClient->getProductByBarcode($barcode);

        if (!$product) {
            return $this->json(['error' => 'Produit non trouvé'], 404);
        }

        return $this->json($product);
    }

    #[Route('/products/category/{category}', name: 'products_category', methods: ['GET'])]
    #[OA\Get(
        path: '/api/products/category/{category}',
        summary: 'Produits par catégorie',
        parameters: [
            new OA\Parameter(
                name: 'category',
                in: 'path',
                description: 'Nom de la catégorie',
                required: true,
                schema: new OA\Schema(type: 'string', example: 'beverages')
            ),
            new OA\Parameter(
                name: 'limit',
                in: 'query',
                description: 'Nombre de résultats',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 5)
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Liste de produits par catégorie'),
        ]
    )]
    public function byCategory(string $category, Request $request): JsonResponse
    {
        $limit = $request->query->getInt('limit', 5);
        $products = $this->offClient->getProductsByCategory($category, $limit);

        return $this->json([
            'category' => $category,
            'total' => count($products),
            'products' => $products,
        ]);
    }
}
