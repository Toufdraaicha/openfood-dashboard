<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\User\Entity\LoginAttempt;
use App\Domain\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $securityLogger,
    ) {}

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->getString('email');
        $password = $request->request->getString('password');

        // Vérifier si l'utilisateur existe
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);

        // Vérifier si le compte est bloqué AVANT de vérifier le password
        if ($user && $user->isLocked()) {
            $this->securityLogger->warning('Tentative de connexion sur compte bloqué', [
                'email' => $email,
                'ip' => $request->getClientIp(),
            ]);

            throw new CustomUserMessageAuthenticationException(
                'Votre compte a été bloqué après plusieurs tentatives échouées. Contactez un administrateur.'
            );
        }

        $request->getSession()->set('_security.last_username', $email);

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $request->request->getString('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        /** @var User $user */
        $user = $token->getUser();

        // Enregistrer tentative réussie
        $user->recordSuccessfulLogin();
        $this->recordLoginAttempt($user, $request->getClientIp(), true);

        $this->securityLogger->info('Connexion réussie', [
            'email' => $user->getEmail(),
            'ip' => $request->getClientIp(),
        ]);

        // Rediriger vers 2FA
        return new RedirectResponse($this->urlGenerator->generate('2fa_login'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $email = $request->request->getString('email');

        // Enregistrer tentative échouée si l'utilisateur existe
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($user) {
            $user->recordFailedLogin();
            $this->em->flush();

            $this->securityLogger->warning('Échec de connexion', [
                'email' => $email,
                'ip' => $request->getClientIp(),
                'failed_attempts' => $user->getFailedLoginCount(),
                'is_locked' => $user->isLocked(),
            ]);

            if ($user->isLocked()) {
                $this->securityLogger->critical('Compte bloqué automatiquement', [
                    'email' => $email,
                    'ip' => $request->getClientIp(),
                ]);
            }
        }

        $this->recordLoginAttempt($user, $request->getClientIp(), false);

        $request->getSession()->set('_security.last_username', $email);

        return parent::onAuthenticationFailure($request, $exception);
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate('app_login');
    }

    private function recordLoginAttempt(?User $user, ?string $ipAddress, bool $success): void
    {
        $attempt = new LoginAttempt(
            $user,
            $user?->getEmail() ?? 'unknown',
            $ipAddress ?? 'unknown',
            $success
        );

        $this->em->persist($attempt);
        $this->em->flush();
    }
}
