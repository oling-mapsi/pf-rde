<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Access\Entity\ExternalResourceRequest;
use App\Domain\Access\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ExternalResourceRequest>
 */
class ExternalResourceRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExternalResourceRequest::class);
    }

    /**
     * @return list<ExternalResourceRequest>
     */
    public function findLatestForUser(User $user, int $limit = 30): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.requester = :user')
            ->setParameter('user', $user)
            ->orderBy('r.submittedAt', 'DESC')
            ->setMaxResults(max(1, $limit))
            ->getQuery()
            ->getResult();
    }
}

