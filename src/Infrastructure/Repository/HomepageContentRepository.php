<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Content\Entity\HomepageContent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HomepageContent>
 */
class HomepageContentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HomepageContent::class);
    }

    public function findPublishedHomepage(): ?HomepageContent
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.status = :status')
            ->setParameter('status', 'published')
            ->orderBy('h.publishedAt', 'DESC')
            ->addOrderBy('h.updatedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findEditableHomepage(): HomepageContent
    {
        $homepage = $this->createQueryBuilder('h')
            ->orderBy('h.updatedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $homepage instanceof HomepageContent ? $homepage : new HomepageContent();
    }
}
