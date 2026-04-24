<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Access\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findActiveByEmail(string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.email = :email')
            ->andWhere('u.isActive = :active')
            ->setParameter('email', strtolower(trim($email)))
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
