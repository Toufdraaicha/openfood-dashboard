<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DoctrineUserRepository extends ServiceEntityRepository implements UserRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findById(string $id): ?User
    {
        return $this->find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function save(User $user): void
    {
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function remove(User $user): void
    {
        $this->getEntityManager()->remove($user);
        $this->getEntityManager()->flush();
    }

    public function findAll(): array
    {
        return parent::findAll();
    }

    public function findLocked(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.isLocked = true')
            ->orderBy('u.lockedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
