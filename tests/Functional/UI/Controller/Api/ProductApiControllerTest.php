<?php

declare(strict_types=1);

namespace App\Tests\Functional\UI\Controller\Api;

use App\Domain\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ProductApiControllerTest extends WebTestCase
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

    public function testSearchProducts(): void
    {
        $this->client->request('GET', '/api/products/search?q=nutella&limit=5');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
        $this->assertIsArray($data['data']);
    }

    public function testSearchRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/products/search?q=nutella');

        $this->assertResponseStatusCodeSame(302); // Redirect to login
    }

    public function testGetProductByBarcode(): void
    {
        $this->client->request('GET', '/api/products/3017620422003');

        $this->assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
    }

    public function testGetProductsByCategory(): void
    {
        $this->client->request('GET', '/api/products/category/beverages?limit=5');

        $this->assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertSame('beverages', $data['category']);
    }

    public function testNutriScoreStats(): void
    {
        $this->client->request('GET', '/api/stats/nutriscore/snacks');

        $this->assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('A', $data['data']);
        $this->assertArrayHasKey('E', $data['data']);
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
