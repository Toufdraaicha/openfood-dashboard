<?php

declare(strict_types=1);

namespace App\Domain\User\Event;

final readonly class UserLockedOut
{
    public function __construct(
        public string $userId,
        public string $email,
        public \DateTimeImmutable $lockedAt,
    ) {}
}
