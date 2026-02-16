<?php

declare(strict_types=1);

namespace App\Domain\User\Entity;

use App\Domain\Dashboard\Entity\Dashboard;
use App\Domain\User\Event\UserLockedOut;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: '`user`')]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface, TwoFactorInterface
{
    public const MAX_FAILED_ATTEMPTS = 5;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private string $id;

    #[ORM\Column(length: 180, unique: true)]
    private string $email;

    #[ORM\Column]
    private string $password;

    private string $plainPassword;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $failedLoginCount = 0;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isLocked = false;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lockedAt = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $emailAuthCode = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $emailAuthCodeExpiresAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Dashboard::class, cascade: ['persist', 'remove'])]
    private ?Dashboard $dashboard = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: LoginAttempt::class, cascade: ['persist'])]
    private Collection $loginAttempts;

    private array $domainEvents = [];

    public function __construct(string $email="", string $hashedPassword="", array $roles = ['ROLE_USER'])
    {
        $this->email         = $email;
        $this->plainPassword      = $hashedPassword;
        $this->roles         = $roles;
        $this->createdAt     = new \DateTimeImmutable();
        $this->loginAttempts = new ArrayCollection();
    }

    public function recordFailedLogin(): void
    {
        $this->failedLoginCount++;
        if ($this->failedLoginCount >= self::MAX_FAILED_ATTEMPTS) {
            $this->lock();
        }
    }

    public function recordSuccessfulLogin(): void
    {
        $this->failedLoginCount = 0;
    }

    public function lock(): void
    {
        $this->isLocked       = true;
        $this->lockedAt       = new \DateTimeImmutable();
        $this->domainEvents[] = new UserLockedOut($this->id, $this->email, $this->lockedAt);
    }

    public function unlock(): void
    {
        $this->isLocked         = false;
        $this->lockedAt         = null;
        $this->failedLoginCount = 0;
    }

    public function isEmailAuthEnabled(): bool      { return true; }
    public function getEmailAuthRecipient(): string { return $this->email; }
    public function getEmailAuthCode(): ?string     { return $this->emailAuthCode; }

    public function setEmailAuthCode(?string $code): void
    {
        $this->emailAuthCode          = $code;
        $this->emailAuthCodeExpiresAt = $code ? new \DateTimeImmutable('+10 minutes') : null;
    }

    public function isEmailAuthCodeValid(): bool
    {
        return $this->emailAuthCodeExpiresAt?->getTimestamp() > time();
    }

    public function getUserIdentifier(): string { return $this->email; }

    public function getRoles(): array
    {
        $roles   = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function eraseCredentials(): void {}

    public function pullDomainEvents(): array
    {
        $events             = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }

    public function getId(): string                    { return $this->id; }
    public function getEmail(): string                 { return $this->email; }
    public function isLocked(): bool                   { return $this->isLocked; }
    public function getLockedAt(): ?\DateTimeImmutable { return $this->lockedAt; }
    public function getFailedLoginCount(): int         { return $this->failedLoginCount; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getDashboard(): ?Dashboard         { return $this->dashboard; }
    public function setPassword(string $p): void       { $this->password = $p; }
    public function setRoles(array $r): void           { $this->roles = $r; }
    public function setDashboard(?Dashboard $d): void  { $this->dashboard = $d; }

    public function getPlainPassword(): ?string
    {
        return $this->getPassword();
    }

    public function setPlainPassword(string $p)
    {
         $this->plainPassword=$p;
    }
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getPassword(): ?string
    {
       return $this->password;
    }
}
