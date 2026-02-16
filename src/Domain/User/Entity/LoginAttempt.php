<?php

declare(strict_types=1);

namespace App\Domain\User\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'login_attempt')]
class LoginAttempt
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'loginAttempts')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user;

    #[ORM\Column(length: 180)]
    private string $email;

    #[ORM\Column(length: 45)]
    private string $ipAddress;

    #[ORM\Column(type: 'boolean')]
    private bool $success;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $attemptedAt;

    public function __construct(?User $user, string $email, string $ipAddress, bool $success)
    {
        $this->user        = $user;
        $this->email       = $email;
        $this->ipAddress   = $ipAddress;
        $this->success     = $success;
        $this->attemptedAt = new \DateTimeImmutable();
    }

    public function getId(): int                         { return $this->id; }
    public function getUser(): ?User                     { return $this->user; }
    public function getEmail(): string                   { return $this->email; }
    public function getIpAddress(): string               { return $this->ipAddress; }
    public function isSuccess(): bool                    { return $this->success; }
    public function getAttemptedAt(): \DateTimeImmutable { return $this->attemptedAt; }
}
