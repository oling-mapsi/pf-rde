<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Analytics\Entity\DashboardMetricSnapshot;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DashboardMetricSnapshot>
 */
class DashboardMetricSnapshotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DashboardMetricSnapshot::class);
    }
}
