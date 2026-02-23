<?php

declare(strict_types=1);

namespace App\UI\Controller\Api;

use App\Domain\User\Repository\UserRepositoryInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/auth', name: 'api_auth_')]
class AuthApiController extends AbstractController
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly JWTTokenManagerInterface $jwtManager,
    ) {}

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($email) || empty($password)) {
            return $this->json([
                'success' => false,
                'error' => 'Email et mot de passe requis',
            ], 400);
        }

        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
            return $this->json([
                'success' => false,
                'error' => 'Identifiants invalides',
            ], 401);
        }

        if ($user->isLocked()) {
            return $this->json([
                'success' => false,
                'error' => 'Compte bloqué. Contactez un administrateur.',
            ], 403);
        }

        if (!$this->passwordHasher->isPasswordValid($user, $password)) {
            $user->recordFailedLogin();
            $this->userRepository->save($user);

            return $this->json([
                'success' => false,
                'error' => 'Identifiants invalides',
            ], 401);
        }

        $user->recordSuccessfulLogin();
        $this->userRepository->save($user);

        // Générer le token JWT
        $token = $this->jwtManager->create($user);

        return $this->json([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ],
        ]);
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(#[CurrentUser] $user): JsonResponse
    {
        if (!$user) {
            return $this->json([
                'success' => false,
                'error' => 'Non authentifié',
            ], 401);
        }

        return $this->json([
            'success' => true,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ],
        ]);
    }

    #[Route('/refresh', name: 'refresh', methods: ['POST'])]
    public function refresh(#[CurrentUser] $user): JsonResponse
    {
        if (!$user) {
            return $this->json([
                'success' => false,
                'error' => 'Token invalide',
            ], 401);
        }

        $token = $this->jwtManager->create($user);

        return $this->json([
            'success' => true,
            'token' => $token,
        ]);
    }
}
