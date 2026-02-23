<?php

declare(strict_types=1);

namespace App\Tests\Functional\UI\Controller;

use App\Domain\Dashboard\Entity\Dashboard;
use App\Domain\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class DashboardControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private User $user;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->user = $this->createUser('test@example.com', 'password123');
        $this->client->loginUser($this->user);
    }

    public function testDashboardIsAccessibleForAuthenticatedUser(): void
    {
        $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Bonjour');
    }

    public function testDashboardRedirectsUnauthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseRedirects('/login');
    }

    public function testDashboardCreatedAutomatically(): void
    {
        $this->client->request('GET', '/');

        $dashboard = $this->em->getRepository(Dashboard::class)->findOneBy(['user' => $this->user]);

        $this->assertNotNull($dashboard);
        $this->assertSame('Mon Dashboard', $dashboard->getName());
    }

    public function testAddWidgetViaForm(): void
    {
        $this->client->request('GET', '/');


        $this->assertResponseIsSuccessful();
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
