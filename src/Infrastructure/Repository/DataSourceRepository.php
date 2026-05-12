<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Access\VisibilityScope;
use App\Domain\Cartography\Entity\DataSource;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DataSource>
 */
class DataSourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DataSource::class);
    }

    /**
     * @param list<string> $themes
     * @param list<string> $categorySlugs
     * @param list<string> $allowedScopes
     *
     * @return list<DataSource>
     */
    public function searchPublishedForDataCatalog(?string $query, array $themes, array $categorySlugs, array $allowedScopes): array
    {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.linkedStaticMap', 'staticMap')->addSelect('staticMap')
            ->leftJoin('s.linkedInteractiveMap', 'interactiveMap')->addSelect('interactiveMap')
            ->leftJoin('s.serviceEndpoint', 'endpoint')->addSelect('endpoint')
            ->leftJoin('s.categories', 'category')->addSelect('category')
            ->andWhere('s.status = :status')
            ->andWhere('s.visibilityScope IN (:scopes)')
            ->setParameter('status', 'published')
            ->setParameter('scopes', $allowedScopes)
            ->orderBy('s.publishedAt', 'DESC')
            ->addOrderBy('s.createdAt', 'DESC');

        if ($query !== null && $query !== '') {
            $qb
                ->andWhere('LOWER(s.title) LIKE :q OR LOWER(COALESCE(s.summary, \'\')) LIKE :q OR LOWER(COALESCE(s.theme, \'\')) LIKE :q OR LOWER(COALESCE(s.format, \'\')) LIKE :q')
                ->setParameter('q', '%'.mb_strtolower($query).'%');
        }

        if ($themes !== []) {
            $qb
                ->andWhere('s.theme IN (:themes)')
                ->setParameter('themes', $themes);
        }

        if ($categorySlugs !== []) {
            $qb
                ->andWhere('category.slug IN (:categorySlugs)')
                ->setParameter('categorySlugs', $categorySlugs);
        }

        /** @var list<DataSource> $items */
        $items = $qb->getQuery()->getResult();

        return $items;
    }

    /** @return list<string> */
    public function findAvailableThemes(array $allowedScopes): array
    {
        $rows = $this->createQueryBuilder('s')
            ->select('DISTINCT s.theme as theme')
            ->andWhere('s.theme IS NOT NULL')
            ->andWhere('s.status = :status')
            ->andWhere('s.visibilityScope IN (:scopes)')
            ->setParameter('status', 'published')
            ->setParameter('scopes', $allowedScopes)
            ->orderBy('s.theme', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_values(array_filter(array_map(static fn (array $row): ?string => $row['theme'] ?? null, $rows)));
    }

    public function countPublishedSources(array $allowedScopes = [VisibilityScope::PUBLIC]): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s.status = :status')
            ->andWhere('s.visibilityScope IN (:scopes)')
            ->setParameter('status', 'published')
            ->setParameter('scopes', $allowedScopes)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countPublishedThemes(array $allowedScopes = [VisibilityScope::PUBLIC]): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(DISTINCT s.theme)')
            ->andWhere('s.status = :status')
            ->andWhere('s.visibilityScope IN (:scopes)')
            ->andWhere('s.theme IS NOT NULL')
            ->andWhere('TRIM(s.theme) <> :emptyTheme')
            ->setParameter('status', 'published')
            ->setParameter('scopes', $allowedScopes)
            ->setParameter('emptyTheme', '')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param list<string> $allowedScopes
     */
    public function findOnePublishedVisibleBySlug(string $slug, array $allowedScopes): ?DataSource
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.slug = :slug')
            ->andWhere('s.status = :status')
            ->andWhere('s.visibilityScope IN (:scopes)')
            ->setParameter('slug', $slug)
            ->setParameter('status', 'published')
            ->setParameter('scopes', $allowedScopes)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param list<string> $allowedScopes
     */
    public function findOnePublishedVisibleByLinkedStaticMapSlug(string $slug, array $allowedScopes): ?DataSource
    {
        return $this->createQueryBuilder('s')
            ->innerJoin('s.linkedStaticMap', 'm')
            ->andWhere('m.slug = :slug')
            ->andWhere('s.status = :status')
            ->andWhere('s.visibilityScope IN (:scopes)')
            ->setParameter('slug', $slug)
            ->setParameter('status', 'published')
            ->setParameter('scopes', $allowedScopes)
            ->orderBy('s.publishedAt', 'DESC')
            ->addOrderBy('s.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param list<string> $allowedScopes
     */
    public function findOnePublishedVisibleByLinkedInteractiveMapSlug(string $slug, array $allowedScopes): ?DataSource
    {
        return $this->createQueryBuilder('s')
            ->innerJoin('s.linkedInteractiveMap', 'm')
            ->andWhere('m.slug = :slug')
            ->andWhere('s.status = :status')
            ->andWhere('s.visibilityScope IN (:scopes)')
            ->setParameter('slug', $slug)
            ->setParameter('status', 'published')
            ->setParameter('scopes', $allowedScopes)
            ->orderBy('s.publishedAt', 'DESC')
            ->addOrderBy('s.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
