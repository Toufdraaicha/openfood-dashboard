<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\User\Entity\LoginAttempt;
use App\Domain\User\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const string LOGIN_ROUTE = 'app_login';

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly EntityManagerInterface  $entityManager,
        private readonly RouterInterface         $router,
        #[Autowire(service: 'monolog.logger.security')]
        private readonly LoggerInterface         $logger,
    ) {}

    public function authenticate(Request $request): Passport
    {

        $email = $request->request->getString('_username');
        $user  = $this->userRepository->findByEmail($email);

        // ── Vérification blocage AVANT le mot de passe ──
        if ($user?->isLocked()) {
            $this->logger->warning('Login attempt on locked account', [
                'email' => $email,
                'ip'    => $request->getClientIp(),
            ]);

            throw new CustomUserMessageAuthenticationException(
                'Votre compte est bloqué après trop de tentatives. Contactez un administrateur.'
            );
        }

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($request->request->getString('password')),
            [
                new CsrfTokenBadge('authenticate', $request->request->getString('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {

        $user = $token->getUser();

        if ($user instanceof \App\Domain\User\Entity\User) {
            $user->recordSuccessfulLogin();

            $attempt = new LoginAttempt(
                $user,
                $user->getEmail(),
                $request->getClientIp() ?? 'unknown',
                true,
            );

            $this->entityManager->persist($attempt);
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->logger->info('Login successful', [
                'email' => $user->getEmail(),
                'ip'    => $request->getClientIp(),
                'ip'    => $request->getClientIp(),
            ]);
        }

        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->router->generate('app_dashboard'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $email = $request->request->getString('_username');
        $user  = $this->userRepository->findByEmail($email);

        if ($user !== null) {
            $user->recordFailedLogin();
            $this->entityManager->persist($user);

            $this->logger->warning('Login failed', [
                'email'    => $email,
                'ip'       => $request->getClientIp(),
                'attempts' => $user->getFailedLoginCount(),
                'locked'   => $user->isLocked(),
            ]);
        }

        $attempt = new LoginAttempt(
            $user,
            $email,
            $request->getClientIp() ?? 'unknown',
            false,
        );

        $this->entityManager->persist($attempt);
        $this->entityManager->flush();

        return parent::onAuthenticationFailure($request, $exception);
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->router->generate(self::LOGIN_ROUTE);
    }
}
