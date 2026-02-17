<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;


class OpenFoodFactsClientAdminDashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly ChartBuilderInterface $chartBuilder,
    ) {}

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        $chart = $this->chartBuilder->createChart(Chart::TYPE_LINE);
        // ...set chart data and options somehow

        return $this->render('admin/dashboard.html.twig', [
            'chart' => $chart,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('🥗 OFF Dashboard — Admin')
            ->setFaviconPath('favicon.ico')
            ->setTranslationDomain('admin')
            ->renderContentMaximized();
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::section('Utilisateurs');
        yield MenuItem::linkToCrud('Utilisateurs', 'fa fa-users', \App\Domain\User\Entity\User::class);

        yield MenuItem::section('Application');
        yield MenuItem::linkToUrl('Voir le Dashboard', 'fa fa-eye', '/');
        yield MenuItem::linkToUrl('Mailpit (emails)', 'fa fa-envelope', 'http://localhost:8025', '_blank');
    }
}
