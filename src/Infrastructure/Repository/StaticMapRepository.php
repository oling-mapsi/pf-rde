<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Application\Cartography\DTO\StaticMapSearchCriteria;
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
     * @return array{items: list<StaticMap>, total: int, page: int, perPage: int}
     */
    public function searchPublished(StaticMapSearchCriteria $criteria): array
    {
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.assets', 'a')->addSelect('a')
            ->leftJoin('m.datasetResources', 'd')->addSelect('d')
            ->andWhere('m.status = :status')
            ->setParameter('status', 'published')
            ->orderBy('m.publishedAt', 'DESC')
            ->addOrderBy('m.createdAt', 'DESC');

        if ($criteria->query !== null && $criteria->query !== '') {
            $qb
                ->andWhere('LOWER(m.title) LIKE :q OR LOWER(COALESCE(m.summary, \'\')) LIKE :q OR LOWER(COALESCE(m.theme, \'\')) LIKE :q')
                ->setParameter('q', '%' . mb_strtolower($criteria->query) . '%');
        }

        if ($criteria->theme !== null && $criteria->theme !== '') {
            $qb
                ->andWhere('m.theme = :theme')
                ->setParameter('theme', $criteria->theme);
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
    public function findAvailableThemes(): array
    {
        $rows = $this->createQueryBuilder('m')
            ->select('DISTINCT m.theme as theme')
            ->andWhere('m.theme IS NOT NULL')
            ->andWhere('m.status = :status')
            ->setParameter('status', 'published')
            ->orderBy('m.theme', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_values(array_filter(array_map(static fn (array $row): ?string => $row['theme'] ?? null, $rows)));
    }
}
