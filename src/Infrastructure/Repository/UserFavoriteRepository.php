<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Access\Entity\User;
use App\Domain\Access\Entity\UserFavorite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserFavorite>
 */
class UserFavoriteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserFavorite::class);
    }

    /**
     * @return list<UserFavorite>
     */
    public function findLatestForUser(User $user, int $limit = 50): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.user = :user')
            ->setParameter('user', $user)
            ->orderBy('f.updatedAt', 'DESC')
            ->addOrderBy('f.createdAt', 'DESC')
            ->setMaxResults(max(1, $limit))
            ->getQuery()
            ->getResult();
    }

    public function findOneForUserAndResource(User $user, string $resourceKind, string $resourceSlug): ?UserFavorite
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.user = :user')
            ->andWhere('f.resourceKind = :kind')
            ->andWhere('f.resourceSlug = :slug')
            ->setParameter('user', $user)
            ->setParameter('kind', strtolower(trim($resourceKind)))
            ->setParameter('slug', trim($resourceSlug))
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<string>
     */
    public function findResourceSlugsForUserAndKind(User $user, string $resourceKind): array
    {
        $rows = $this->createQueryBuilder('f')
            ->select('f.resourceSlug AS slug')
            ->andWhere('f.user = :user')
            ->andWhere('f.resourceKind = :kind')
            ->setParameter('user', $user)
            ->setParameter('kind', strtolower(trim($resourceKind)))
            ->getQuery()
            ->getArrayResult();

        return array_values(array_filter(array_map(
            static fn (array $row): string => trim((string) ($row['slug'] ?? '')),
            $rows,
        )));
    }
}
