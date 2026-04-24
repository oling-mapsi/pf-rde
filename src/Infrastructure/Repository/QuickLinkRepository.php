<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Content\Entity\QuickLink;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<QuickLink>
 */
class QuickLinkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuickLink::class);
    }
}
