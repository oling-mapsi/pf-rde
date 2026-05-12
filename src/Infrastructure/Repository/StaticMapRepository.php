<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Application\Cartography\DTO\StaticMapSearchCriteria;
use App\Domain\Access\VisibilityScope;
use App\Domain\Cartography\Entity\StaticMap;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StaticMap>
 */
class StaticMapRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StaticMap::class);
    }

    /**
     * @param list<string> $allowedScopes
     *
     * @return array{items: list<StaticMap>, total: int, page: int, perPage: int}
     */
    public function searchPublished(StaticMapSearchCriteria $criteria, array $allowedScopes): array
    {
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.assets', 'a')->addSelect('a')
            ->leftJoin('m.datasetResources', 'd')->addSelect('d')
            ->andWhere('m.status = :status')
            ->andWhere('m.visibilityScope IN (:scopes)')
            ->setParameter('status', 'published')
            ->setParameter('scopes', $allowedScopes)
            ->orderBy('m.publishedAt', 'DESC')
            ->addOrderBy('m.createdAt', 'DESC');

        if ($criteria->query !== null && $criteria->query !== '') {
            $qb
                ->andWhere('LOWER(m.title) LIKE :q OR LOWER(COALESCE(m.summary, \'\')) LIKE :q OR LOWER(COALESCE(m.theme, \'\')) LIKE :q')
                ->setParameter('q', '%' . mb_strtolower($criteria->query) . '%');
        }

        if ($criteria->themes !== []) {
            $qb
                ->andWhere('m.theme IN (:themes)')
                ->setParameter('themes', $criteria->themes);
        }

        if ($criteria->year !== null) {
            $start = new \DateTimeImmutable(sprintf('%d-01-01 00:00:00', $criteria->year));
            $end = $start->modify('+1 year');
            $qb
                ->andWhere('m.documentDate >= :start')
                ->andWhere('m.documentDate < :end')
                ->setParameter('start', $start)
                ->setParameter('end', $end);
        }

        $offset = max(0, ($criteria->page - 1) * $criteria->perPage);

        $qb->setFirstResult($offset)->setMaxResults($criteria->perPage);

        $paginator = new Paginator($qb->getQuery(), true);

        $items = iterator_to_array($paginator->getIterator());

        return [
            'items' => $items,
            'total' => count($paginator),
            'page' => $criteria->page,
            'perPage' => $criteria->perPage,
        ];
    }

    /** @return list<string> */
    public function findAvailableThemes(array $allowedScopes): array
    {
        $rows = $this->createQueryBuilder('m')
            ->select('DISTINCT m.theme as theme')
            ->andWhere('m.theme IS NOT NULL')
            ->andWhere('m.status = :status')
            ->andWhere('m.visibilityScope IN (:scopes)')
            ->setParameter('status', 'published')
            ->setParameter('scopes', $allowedScopes)
            ->orderBy('m.theme', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_values(array_filter(array_map(static fn (array $row): ?string => $row['theme'] ?? null, $rows)));
    }

    public function countPublishedThemes(array $allowedScopes = [VisibilityScope::PUBLIC]): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(DISTINCT m.theme)')
            ->andWhere('m.status = :status')
            ->andWhere('m.visibilityScope IN (:scopes)')
            ->andWhere('m.theme IS NOT NULL')
            ->andWhere('TRIM(m.theme) <> :emptyTheme')
            ->setParameter('status', 'published')
            ->setParameter('scopes', $allowedScopes)
            ->setParameter('emptyTheme', '')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return list<StaticMap>
     */
    public function findPublishedForHomepage(
        int $limit = 3,
        array $filters = [],
        array $allowedScopes = [VisibilityScope::PUBLIC]
    ): array
    {
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.assets', 'a')->addSelect('a')
            ->leftJoin('m.datasetResources', 'd')->addSelect('d')
            ->andWhere('m.status = :status')
            ->andWhere('m.visibilityScope IN (:scopes)')
            ->setParameter('status', 'published')
            ->setParameter('scopes', $allowedScopes)
            ->orderBy('m.publishedAt', 'DESC')
            ->addOrderBy('m.createdAt', 'DESC')
            ->setMaxResults(max(1, $limit));

        if (($filters['theme'] ?? null) !== null && trim((string) $filters['theme']) !== '') {
            $qb
                ->andWhere('m.theme = :theme')
                ->setParameter('theme', trim((string) $filters['theme']));
        }

        if (($filters['query'] ?? null) !== null && trim((string) $filters['query']) !== '') {
            $qb
                ->andWhere('LOWER(m.title) LIKE :q OR LOWER(COALESCE(m.summary, \'\')) LIKE :q OR LOWER(COALESCE(m.theme, \'\')) LIKE :q')
                ->setParameter('q', '%' . mb_strtolower(trim((string) $filters['query'])) . '%');
        }

        /** @var list<StaticMap> $items */
        $items = $qb->getQuery()->getResult();

        return $items;
    }

    /**
     * @param list<string> $themes
     * @param list<string> $allowedScopes
     *
     * @return list<StaticMap>
     */
    public function searchPublishedForDataCatalog(?string $query, array $themes, array $allowedScopes): array
    {
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.assets', 'a')->addSelect('a')
            ->leftJoin('m.datasetResources', 'd')->addSelect('d')
            ->andWhere('m.status = :status')
            ->andWhere('m.visibilityScope IN (:scopes)')
            ->setParameter('status', 'published')
            ->setParameter('scopes', $allowedScopes)
            ->orderBy('m.publishedAt', 'DESC')
            ->addOrderBy('m.createdAt', 'DESC');

        if ($query !== null && $query !== '') {
            $qb
                ->andWhere('LOWER(m.title) LIKE :q OR LOWER(COALESCE(m.summary, \'\')) LIKE :q OR LOWER(COALESCE(m.theme, \'\')) LIKE :q')
                ->setParameter('q', '%' . mb_strtolower($query) . '%');
        }

        if ($themes !== []) {
            $qb
                ->andWhere('m.theme IN (:themes)')
                ->setParameter('themes', $themes);
        }

        /** @var list<StaticMap> $items */
        $items = $qb->getQuery()->getResult();

        return $items;
    }

    /**
     * @param list<string> $allowedScopes
     */
    public function findOnePublishedVisibleBySlug(string $slug, array $allowedScopes): ?StaticMap
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.assets', 'a')->addSelect('a')
            ->leftJoin('m.datasetResources', 'd')->addSelect('d')
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
