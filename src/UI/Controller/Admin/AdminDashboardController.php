<?php

declare(strict_types=1);

namespace App\UI\Controller\Admin;

use App\Domain\User\Entity\LoginAttempt;
use App\Domain\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;


class AdminDashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        // Stats
        $totalUsers = $this->em->getRepository(User::class)->count([]);
        $lockedUsers = $this->em->getRepository(User::class)->count(['isLocked' => true]);
        $totalAttempts = $this->em->getRepository(LoginAttempt::class)->count([]);
        $failedAttempts = $this->em->getRepository(LoginAttempt::class)->count(['success' => false]);

        return $this->render('admin/dashboard.html.twig', [
            'stats' => [
                'totalUsers' => $totalUsers,
                'lockedUsers' => $lockedUsers,
                'totalAttempts' => $totalAttempts,
                'failedAttempts' => $failedAttempts,
            ],
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('🥗 OFF Admin')
            ->setFaviconPath('favicon.ico');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        yield MenuItem::section('👥 Utilisateurs');
        yield MenuItem::linkToCrud('Utilisateurs', 'fa fa-users', User::class);

        yield MenuItem::section('🔐 Sécurité');
        yield MenuItem::linkToCrud('Tentatives de connexion', 'fa fa-shield', LoginAttempt::class);

        yield MenuItem::section('🔗 Liens');
        yield MenuItem::linkToUrl('Voir le Dashboard', 'fa fa-eye', '/');
        yield MenuItem::linkToUrl('Mailpit (emails)', 'fa fa-envelope', 'http://localhost:8025')->setLinkTarget('_blank');
        yield MenuItem::linkToUrl('API Swagger', 'fa fa-code', '/api/doc')->setLinkTarget('_blank');

        yield MenuItem::section();
        yield MenuItem::linkToLogout('Déconnexion', 'fa fa-sign-out');
    }
}
