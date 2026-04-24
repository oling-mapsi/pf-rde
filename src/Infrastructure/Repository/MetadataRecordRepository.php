<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Cartography\Entity\MetadataRecord;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MetadataRecord>
 */
class MetadataRecordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MetadataRecord::class);
    }
}
