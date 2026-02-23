<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Dashboard\Entity;

use App\Domain\Dashboard\Entity\Dashboard;
use App\Domain\Dashboard\Entity\Widget;
use App\Domain\Dashboard\Enum\WidgetType;
use App\Domain\User\Entity\User;
use PHPUnit\Framework\TestCase;

class DashboardTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User('test@example.com', 'password');
    }

    public function testDashboardCreation(): void
    {
        $dashboard = new Dashboard($this->user, 'Mon Dashboard');

        $this->assertSame('Mon Dashboard', $dashboard->getName());
        $this->assertSame($this->user, $dashboard->getUser());
        $this->assertCount(0, $dashboard->getWidgets());
    }

    public function testAddWidget(): void
    {
        $dashboard = new Dashboard($this->user, 'Mon Dashboard');
        $widget = new Widget($dashboard, WidgetType::ProductsSearch, 0);

        $dashboard->addWidget($widget);

        $this->assertCount(1, $dashboard->getWidgets());
        $this->assertSame($dashboard, $widget->getDashboard());
    }

    public function testRemoveWidget(): void
    {
        $dashboard = new Dashboard($this->user, 'Mon Dashboard');
        $widget = new Widget($dashboard, WidgetType::ProductsSearch, 0);

        $dashboard->addWidget($widget);
        $this->assertCount(1, $dashboard->getWidgets());

        $dashboard->removeWidget($widget);
        $this->assertCount(0, $dashboard->getWidgets());
    }

    public function testReorderWidgets(): void
    {
        $dashboard = new Dashboard($this->user, 'Mon Dashboard');

        $widget1 = new Widget($dashboard, WidgetType::ProductsSearch, 0);
        $widget2 = new Widget($dashboard, WidgetType::NutriScoreStats, 1);
        $widget3 = new Widget($dashboard, WidgetType::CategoryTop, 2);

        $dashboard->addWidget($widget1);
        $dashboard->addWidget($widget2);
        $dashboard->addWidget($widget3);

        // Réorganiser : widget3 -> 0, widget1 -> 1, widget2 -> 2
        $orderMap = [
            $widget3->getId() => 0,
            $widget1->getId() => 1,
            $widget2->getId() => 2,
        ];

        $dashboard->reorderWidgets($orderMap);

        $this->assertSame(0, $widget3->getPosition());
        $this->assertSame(1, $widget1->getPosition());
        $this->assertSame(2, $widget2->getPosition());
    }

    public function testGetWidgetCount(): void
    {
        $dashboard = new Dashboard($this->user, 'Mon Dashboard');

        $this->assertSame(0, $dashboard->getWidgetCount());

        $dashboard->addWidget(new Widget($dashboard, WidgetType::ProductsSearch, 0));
        $this->assertSame(1, $dashboard->getWidgetCount());

        $dashboard->addWidget(new Widget($dashboard, WidgetType::NutriScoreStats, 1));
        $this->assertSame(2, $dashboard->getWidgetCount());
    }
}
