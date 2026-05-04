<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Cartography\Entity\DatasetResource;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DatasetResource>
 */
class DatasetResourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DatasetResource::class);
    }

    public function countPublishedResources(): int
    {
        return (int) $this->createQueryBuilder('resource')
            ->select('COUNT(resource.id)')
            ->innerJoin('resource.staticMap', 'map')
            ->andWhere('map.status = :status')
            ->setParameter('status', 'published')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
