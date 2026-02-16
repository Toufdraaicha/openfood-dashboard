<?php

namespace App\Infrastructure\Security;

use App\Domain\User\Entity\LoginAttempt;
use App\Domain\User\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
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


class LoginFormAuthenticator  extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly EntityManagerInterface  $entityManager,
        private readonly RouterInterface          $router,
    ) {}

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->getString('email');
        $user  = $this->userRepository->findByEmail($email);

        if ($user?->isLocked()) {
            throw new CustomUserMessageAuthenticationException(
                'Votre compte est bloqué. Contactez un administrateur.'
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
            $attempt = new LoginAttempt($user, $user->getEmail(), $request->getClientIp() ?? 'unknown', true);
            $this->entityManager->persist($attempt);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->router->generate('app_dashboard'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $email = $request->request->getString('email');
        $user  = $this->userRepository->findByEmail($email);

        if ($user !== null) {
            $user->recordFailedLogin();
            $this->entityManager->persist($user);
        }

        $attempt = new LoginAttempt($user, $email, $request->getClientIp() ?? 'unknown', false);
        $this->entityManager->persist($attempt);
        $this->entityManager->flush();

        return parent::onAuthenticationFailure($request, $exception);
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->router->generate(self::LOGIN_ROUTE);
    }
}
