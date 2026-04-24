<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Agent\Entity\AgentRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AgentRequest>
 */
class AgentRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AgentRequest::class);
    }
}
