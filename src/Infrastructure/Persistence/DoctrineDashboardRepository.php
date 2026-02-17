<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Dashboard\Entity\Dashboard;
use App\Domain\Dashboard\Repository\DashboardRepositoryInterface;
use App\Domain\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DoctrineDashboardRepository extends ServiceEntityRepository implements DashboardRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dashboard::class);
    }

    public function findById(string $id): ?Dashboard
    {
        return $this->find($id);
    }

    public function findByUser(User $user): ?Dashboard
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.widgets', 'w')
            ->addSelect('w')
            ->where('d.user = :user')
            ->setParameter('user', $user)
            ->orderBy('w.position', 'ASC')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(Dashboard $dashboard): void
    {
        $this->getEntityManager()->persist($dashboard);
        $this->getEntityManager()->flush();
    }

    public function remove(Dashboard $dashboard): void
    {
        $this->getEntityManager()->remove($dashboard);
        $this->getEntityManager()->flush();
    }
}
