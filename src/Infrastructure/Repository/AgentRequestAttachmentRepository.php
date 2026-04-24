<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Agent\Entity\AgentRequestAttachment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AgentRequestAttachment>
 */
class AgentRequestAttachmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AgentRequestAttachment::class);
    }
}
