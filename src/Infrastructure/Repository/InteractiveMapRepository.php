<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Cartography\Entity\InteractiveMap;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<InteractiveMap>
 */
class InteractiveMapRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InteractiveMap::class);
    }

    /**
     * @param list<string> $allowedScopes
     *
     * @return list<InteractiveMap>
     */
    public function searchPublishedForDataCatalog(?string $query, array $allowedScopes): array
    {
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.layers', 'l')->addSelect('l')
            ->andWhere('m.status = :status')
            ->andWhere('m.visibilityScope IN (:scopes)')
            ->setParameter('status', 'published')
            ->setParameter('scopes', $allowedScopes)
            ->orderBy('m.publishedAt', 'DESC')
            ->addOrderBy('m.createdAt', 'DESC');

        if ($query !== null && $query !== '') {
            $qb
                ->andWhere('LOWER(m.title) LIKE :q OR LOWER(COALESCE(m.summary, \'\')) LIKE :q')
                ->setParameter('q', '%' . mb_strtolower($query) . '%');
        }

        /** @var list<InteractiveMap> $items */
        $items = $qb->getQuery()->getResult();

        return $items;
    }

    /**
     * @param list<string> $allowedScopes
     */
    public function findOnePublishedVisibleBySlug(string $slug, array $allowedScopes): ?InteractiveMap
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.layers', 'l')->addSelect('l')
            ->andWhere('m.slug = :slug')
            ->andWhere('m.status = :status')
            ->andWhere('m.visibilityScope IN (:scopes)')
            ->setParameter('slug', $slug)
            ->setParameter('status', 'published')
            ->setParameter('scopes', $allowedScopes)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
