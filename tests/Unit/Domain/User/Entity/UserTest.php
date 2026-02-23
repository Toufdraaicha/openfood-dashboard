<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\User\Entity;

use App\Domain\User\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testUserCreation(): void
    {
        $user = new User('test@example.com', 'hashed_password', ['ROLE_USER']);

        $this->assertSame('test@example.com', $user->getEmail());
        $this->assertSame('test@example.com', $user->getUserIdentifier());
        $this->assertContains('ROLE_USER', $user->getRoles());
        $this->assertFalse($user->isLocked());
        $this->assertSame(0, $user->getFailedLoginCount());
    }

    public function testRecordFailedLogin(): void
    {
        $user = new User('test@example.com', 'password');

        $user->recordFailedLogin();
        $this->assertSame(1, $user->getFailedLoginCount());
        $this->assertFalse($user->isLocked());

        // 5 tentatives = blocage
        for ($i = 0; $i < 4; $i++) {
            $user->recordFailedLogin();
        }

        $this->assertTrue($user->isLocked());
        $this->assertNotNull($user->getLockedAt());
    }

    public function testRecordSuccessfulLogin(): void
    {
        $user = new User('test@example.com', 'password');
        $user->recordFailedLogin();
        $user->recordFailedLogin();

        $this->assertSame(2, $user->getFailedLoginCount());

        $user->recordSuccessfulLogin();
        $this->assertSame(0, $user->getFailedLoginCount());
    }

    public function testUnlock(): void
    {
        $user = new User('test@example.com', 'password');

        // Bloquer l'utilisateur
        for ($i = 0; $i < 5; $i++) {
            $user->recordFailedLogin();
        }

        $this->assertTrue($user->isLocked());

        // Débloquer
        $user->unlock();
        $this->assertFalse($user->isLocked());
        $this->assertNull($user->getLockedAt());
        $this->assertSame(0, $user->getFailedLoginCount());
    }

    public function testRolesAlwaysContainRoleUser(): void
    {
        $user = new User('test@example.com', 'password', ['ROLE_ADMIN']);

        $roles = $user->getRoles();
        $this->assertContains('ROLE_USER', $roles);
        $this->assertContains('ROLE_ADMIN', $roles);
    }

    public function test2FAEmailConfig(): void
    {
        $user = new User('test@example.com', 'password');

        $this->assertTrue($user->isEmailAuthEnabled());
        $this->assertSame('test@example.com', $user->getEmailAuthRecipient());
        $this->assertNull($user->getEmailAuthCode());

        $user->setEmailAuthCode('123456');
        $this->assertSame('123456', $user->getEmailAuthCode());
        $this->assertTrue($user->isEmailAuthCodeValid());
    }
}
