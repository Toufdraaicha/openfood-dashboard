<?php

declare(strict_types=1);

namespace App\UI\Controller;

use App\Domain\Dashboard\Entity\Dashboard;
use App\Domain\Dashboard\Entity\WidgetType;
use App\Domain\Dashboard\Repository\DashboardRepositoryInterface;
use App\Application\Port\OpenFoodFactsClientInterface;
use App\Application\Query\SearchProducts\SearchProductsHandler;
use App\Application\Query\SearchProducts\SearchProductsQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
class DashboardController extends AbstractController
{
    public function __construct(
        private readonly DashboardRepositoryInterface $dashboardRepository,
        private readonly SearchProductsHandler        $searchHandler,
        private readonly OpenFoodFactsClientInterface $offClient,
    ) {}

    #[Route('/', name: 'app_dashboard')]
    public function index(): Response
    {
        $user      = $this->getUser();
        $dashboard = $this->dashboardRepository->findByUser($user);

        if (!$dashboard) {
            $dashboard = new Dashboard($user, 'Mon Dashboard');
            $this->dashboardRepository->save($dashboard);
        }

        return $this->render('dashboard/index.html.twig', [
            'dashboard' => $dashboard,
        ]);
    }

    #[Route('/dashboard/widget/add', name: 'app_widget_add', methods: ['POST'])]
    public function addWidget(Request $request): JsonResponse
    {
        $data      = json_decode($request->getContent(), true);
        $type      = WidgetType::from($data['type'] ?? '');
        $dashboard = $this->dashboardRepository->findByUser($this->getUser());

        if (!$dashboard) {
            return $this->json(['error' => 'Dashboard not found'], 404);
        }

        $position = $dashboard->getWidgetCount();
        $widget   = $dashboard->addWidget($type, $position);
        $this->dashboardRepository->save($dashboard);

        return $this->json([
            'success'   => true,
            'widget_id' => $widget->getId(),
            'type'      => $widget->getType()->value,
            'title'     => $widget->getTitle(),
            'position'  => $widget->getPosition(),
        ]);
    }

    #[Route('/dashboard/widget/{id}/delete', name: 'app_widget_delete', methods: ['DELETE'])]
    public function deleteWidget(string $id): JsonResponse
    {
        $dashboard = $this->dashboardRepository->findByUser($this->getUser());

        if (!$dashboard) {
            return $this->json(['error' => 'Dashboard not found'], 404);
        }

        foreach ($dashboard->getWidgets() as $widget) {
            if ($widget->getId() === $id) {
                $dashboard->removeWidget($widget);
                break;
            }
        }

        $this->dashboardRepository->save($dashboard);

        return $this->json(['success' => true]);
    }

    #[Route('/dashboard/reorder', name: 'app_dashboard_reorder', methods: ['POST'])]
    public function reorder(Request $request): JsonResponse
    {
        $data      = json_decode($request->getContent(), true);
        $order     = $data['order'] ?? [];
        $dashboard = $this->dashboardRepository->findByUser($this->getUser());

        if (!$dashboard) {
            return $this->json(['error' => 'Dashboard not found'], 404);
        }

        $orderMap = array_flip($order);
        $dashboard->reorderWidgets($orderMap);
        $this->dashboardRepository->save($dashboard);

        return $this->json(['success' => true]);
    }

    #[Route('/dashboard/widget/{id}/data', name: 'app_widget_data', methods: ['GET'])]
    public function widgetData(string $id, Request $request): JsonResponse
    {
        $dashboard = $this->dashboardRepository->findByUser($this->getUser());

        if (!$dashboard) {
            return $this->json(['error' => 'Dashboard not found'], 404);
        }

        $widget = null;
        foreach ($dashboard->getWidgets() as $w) {
            if ($w->getId() === $id) {
                $widget = $w;
                break;
            }
        }

        if (!$widget) {
            return $this->json(['error' => 'Widget not found'], 404);
        }

        $data = match ($widget->getType()) {
            WidgetType::ProductsSearch => $this->searchHandler->handle(
                new SearchProductsQuery(
                    $widget->getConfigValue('query', ''),
                    $widget->getConfigValue('limit', 10),
                )
            ),
            WidgetType::CategoryTop => $this->offClient->getProductsByCategory(
                $widget->getConfigValue('category', 'beverages'),
                $widget->getConfigValue('limit', 5),
            ),
            WidgetType::ProductDetail => $this->offClient->getProductByBarcode(
                $widget->getConfigValue('barcode', '')
            ) ?? [],
            WidgetType::NutriScoreStats => $this->getNutriScoreStats(
                $widget->getConfigValue('category', 'snacks')
            ),
        };

        return $this->json(['data' => $data]);
    }

    #[Route('/dashboard/widget/{id}/config', name: 'app_widget_config', methods: ['POST'])]
    public function updateWidgetConfig(string $id, Request $request): JsonResponse
    {
        $data      = json_decode($request->getContent(), true);
        $dashboard = $this->dashboardRepository->findByUser($this->getUser());

        if (!$dashboard) {
            return $this->json(['error' => 'Dashboard not found'], 404);
        }

        foreach ($dashboard->getWidgets() as $widget) {
            if ($widget->getId() === $id) {
                $widget->updateConfig($data['config'] ?? []);
                break;
            }
        }

        $this->dashboardRepository->save($dashboard);

        return $this->json(['success' => true]);
    }

    private function getNutriScoreStats(string $category): array
    {
        $products = $this->offClient->getProductsByCategory($category, 50);
        $stats    = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, '?' => 0];

        foreach ($products as $product) {
            $score = strtoupper($product['nutriscore'] ?? '?');
            if (isset($stats[$score])) {
                $stats[$score]++;
            } else {
                $stats['?']++;
            }
        }

        return $stats;
    }
}
