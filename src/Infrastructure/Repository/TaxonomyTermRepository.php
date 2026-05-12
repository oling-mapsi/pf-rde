<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Taxonomy\Entity\TaxonomyTerm;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TaxonomyTerm>
 */
class TaxonomyTermRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TaxonomyTerm::class);
    }

    /** @return list<TaxonomyTerm> */
    public function findActiveMapThemes(?int $limit = null): array
    {
        return $this->sortMapThemes(
            $this->createQueryBuilder('t')
                ->andWhere('t.taxonomy = :taxonomy')
                ->andWhere('t.active = :active')
                ->setParameter('taxonomy', TaxonomyTerm::MAP_THEME_TAXONOMY)
                ->setParameter('active', true)
                ->getQuery()
                ->getResult(),
            $limit,
        );
    }

    /** @return list<TaxonomyTerm> */
    public function findFeaturedMapThemes(?int $limit = null): array
    {
        /** @var list<TaxonomyTerm> $themes */
        $themes = $this->createQueryBuilder('t')
            ->andWhere('t.taxonomy = :taxonomy')
            ->andWhere('t.active = :active')
            ->setParameter('taxonomy', TaxonomyTerm::MAP_THEME_TAXONOMY)
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();

        return $this->sortMapThemes(array_values(array_filter(
            $themes,
            static fn (TaxonomyTerm $theme): bool => $theme->isFeaturedOnHomepage(),
        )), $limit);
    }

    /**
     * @return array<string, string>
     */
    public function findMapThemeChoicesForSelect(): array
    {
        /** @var list<TaxonomyTerm> $themes */
        $themes = $this->createQueryBuilder('t')
            ->andWhere('t.taxonomy = :taxonomy')
            ->andWhere('t.active = :active')
            ->setParameter('taxonomy', TaxonomyTerm::MAP_THEME_TAXONOMY)
            ->setParameter('active', true)
            ->orderBy('t.label', 'ASC')
            ->getQuery()
            ->getResult();

        $choices = [];
        foreach ($themes as $theme) {
            $label = trim($theme->getLabel());
            if ($label === '') {
                continue;
            }

            $choices[$label] = $label;
        }

        return $choices;
    }

    /**
     * @param list<TaxonomyTerm> $themes
     *
     * @return list<TaxonomyTerm>
     */
    private function sortMapThemes(array $themes, ?int $limit = null): array
    {
        usort($themes, static function (TaxonomyTerm $left, TaxonomyTerm $right): int {
            $positionSort = $left->getPosition() <=> $right->getPosition();
            if ($positionSort !== 0) {
                return $positionSort;
            }

            return strcasecmp($left->getLabel(), $right->getLabel());
        });

        if ($limit !== null) {
            return array_slice($themes, 0, max(1, $limit));
        }

        return $themes;
    }
}
