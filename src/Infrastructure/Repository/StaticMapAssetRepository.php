<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Cartography\Entity\StaticMapAsset;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StaticMapAsset>
 */
class StaticMapAssetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StaticMapAsset::class);
    }
}
