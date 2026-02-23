<?php

declare(strict_types=1);

namespace App\UI\Controller\Api;

use App\Domain\Dashboard\Entity\Dashboard;
use App\Domain\Dashboard\Entity\Widget;
use App\Domain\Dashboard\Enum\WidgetType;
use App\Domain\Dashboard\Repository\DashboardRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/dashboard', name: 'api_dashboard_')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class DashboardApiController extends AbstractController
{
    public function __construct(
        private readonly DashboardRepositoryInterface $dashboardRepository,
    ) {}

    #[Route('', name: 'get', methods: ['GET'])]
    public function getDashboard(): JsonResponse
    {
        $user = $this->getUser();
        $dashboard = $this->dashboardRepository->findByUser($user);

        if (!$dashboard) {
            $dashboard = new Dashboard($user, 'Mon Dashboard');
            $this->dashboardRepository->save($dashboard);
        }

        return $this->json([
            'success' => true,
            'data' => [
                'id' => $dashboard->getId(),
                'name' => $dashboard->getName(),
                'widgetCount' => $dashboard->getWidgets()->count(),
                'updatedAt' => $dashboard->getUpdatedAt()->format('Y-m-d H:i:s'),
                'widgets' => array_map(fn($w) => [
                    'id' => $w->getId(),
                    'type' => $w->getType()->value,
                    'title' => $w->getTitle(),
                    'position' => $w->getPosition(),
                    'config' => $w->getConfig(),
                ], $dashboard->getWidgets()->toArray()),
            ],
        ]);
    }

    #[Route('/widgets', name: 'add_widget', methods: ['POST'])]
    public function addWidget(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $type = WidgetType::from($data['type'] ?? '');

        $dashboard = $this->dashboardRepository->findByUser($this->getUser());

        if (!$dashboard) {
            return $this->json(['success' => false, 'error' => 'Dashboard not found'], 404);
        }

        $position = $dashboard->getWidgets()->count();
        $widget = new Widget($dashboard, $type, $position);

        $dashboard->addWidget($widget);
        $this->dashboardRepository->save($dashboard);

        return $this->json([
            'success' => true,
            'data' => [
                'id' => $widget->getId(),
                'type' => $widget->getType()->value,
                'title' => $widget->getTitle(),
                'position' => $widget->getPosition(),
                'config' => $widget->getConfig(),
            ],
        ]);
    }

    #[Route('/widgets/{id}', name: 'delete_widget', methods: ['DELETE'])]
    public function deleteWidget(string $id): JsonResponse
    {
        $dashboard = $this->dashboardRepository->findByUser($this->getUser());

        if (!$dashboard) {
            return $this->json(['success' => false, 'error' => 'Dashboard not found'], 404);
        }

        foreach ($dashboard->getWidgets() as $widget) {
            if ($widget->getId() === $id) {
                $dashboard->removeWidget($widget);
                $this->dashboardRepository->save($dashboard);
                return $this->json(['success' => true]);
            }
        }

        return $this->json(['success' => false, 'error' => 'Widget not found'], 404);
    }

    #[Route('/reorder', name: 'reorder', methods: ['POST'])]
    public function reorder(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $order = $data['order'] ?? [];

        $dashboard = $this->dashboardRepository->findByUser($this->getUser());

        if (!$dashboard) {
            return $this->json(['success' => false, 'error' => 'Dashboard not found'], 404);
        }

        $orderMap = array_flip($order);
        $dashboard->reorderWidgets($orderMap);
        $this->dashboardRepository->save($dashboard);

        return $this->json(['success' => true]);
    }

    #[Route('/widgets/{id}/config', name: 'update_widget_config', methods: ['PUT', 'PATCH'])]
    public function updateConfig(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $dashboard = $this->dashboardRepository->findByUser($this->getUser());

        if (!$dashboard) {
            return $this->json(['success' => false, 'error' => 'Dashboard not found'], 404);
        }

        foreach ($dashboard->getWidgets() as $widget) {
            if ($widget->getId() === $id) {
                $widget->updateConfig($data);
                $this->dashboardRepository->save($dashboard);
                return $this->json(['success' => true]);
            }
        }

        return $this->json(['success' => false, 'error' => 'Widget not found'], 404);
    }
}
