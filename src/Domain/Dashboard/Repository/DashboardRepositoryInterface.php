<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Repository;

use App\Domain\Dashboard\Entity\Dashboard;
use App\Domain\User\Entity\User;

interface DashboardRepositoryInterface
{
    public function findById(string $id): ?Dashboard;

    public function findByUser(User $user): ?Dashboard;

    public function save(Dashboard $dashboard): void;

    public function remove(Dashboard $dashboard): void;
}
