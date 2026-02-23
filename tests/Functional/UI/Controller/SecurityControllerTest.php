<?php

declare(strict_types=1);

namespace App\Tests\Functional\UI\Controller;

use App\Domain\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testLoginPageIsAccessible(): void
    {
        $this->client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Bienvenue');
    }

    public function testLoginWithValidCredentials(): void
    {
        // Créer un utilisateur de test
        $user = $this->createUser('test@example.com', 'password123');

        $crawler = $this->client->request('GET', '/login');

        $form = $crawler->selectButton('Se connecter')->form([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $this->client->submit($form);

        // Doit rediriger vers 2FA
        $this->assertResponseRedirects('/2fa');
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $this->createUser('test@example.com', 'password123');

        $crawler = $this->client->request('GET', '/login');

        $form = $crawler->selectButton('Se connecter')->form([
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $this->client->submit($form);
        $this->client->followRedirect();

        $this->assertSelectorExists('.alert-error');
    }

    public function testAccountLockedAfter5FailedAttempts(): void
    {
        $user = $this->createUser('test@example.com', 'password123');

        // 5 tentatives échouées
        for ($i = 0; $i < 5; $i++) {
            $crawler = $this->client->request('GET', '/login');
            $form = $crawler->selectButton('Se connecter')->form([
                'email' => 'test@example.com',
                'password' => 'wrongpassword',
            ]);
            $this->client->submit($form);
        }

        // Rafraîchir l'entité
        $this->em->refresh($user);

        $this->assertTrue($user->isLocked());
        $this->assertSame(5, $user->getFailedLoginCount());
    }

    public function testLogout(): void
    {
        $user = $this->createUser('test@example.com', 'password123');
        $this->client->loginUser($user);

        $this->client->request('GET', '/logout');

        $this->assertResponseRedirects('/login');
    }

    private function createUser(string $email, string $plainPassword): User
    {
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        
        $user = new User($email, '', ['ROLE_USER']);
        $user->setPassword($hasher->hashPassword($user, $plainPassword));

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->em->close();
    }
}
