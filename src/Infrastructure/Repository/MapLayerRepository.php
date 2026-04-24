<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Cartography\Entity\MapLayer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MapLayer>
 */
class MapLayerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MapLayer::class);
    }
}
