<?php

declare(strict_types=1);

namespace App\Application\Cartography\Service;

use App\Application\Cartography\DTO\DataCatalogSearchCriteria;
use App\Application\Design\LogoPaletteService;
use App\Domain\Cartography\Entity\DataCategory;
use App\Domain\Cartography\Entity\DataSource;
use App\Infrastructure\Repository\DataCategoryRepository;
use App\Infrastructure\Repository\DataSourceRepository;

final class DataCatalogService
{
    public function __construct(
        private readonly DataSourceRepository $dataSourceRepository,
        private readonly DataCategoryRepository $dataCategoryRepository,
        private readonly LogoPaletteService $logoPaletteService,
    ) {
    }

    /**
     * @param list<string> $allowedScopes
     *
     * @return array{
     *   items: list<array{type: string, kind: 'data_source', entity: DataSource}>,
     *   total: int,
     *   page: int,
     *   perPage: int,
     *   totalPages: int,
     *   themes: list<string>,
     *   categories: list<array{slug: string, name: string, iconKey: string, colorHex: string}>,
     *   typeLabels: array<string, string>,
     *   categoryCounters: array<string, int>,
     *   themeCounters: array<string, int>,
     *   typeCounters: array<string, int>
     * }
     */
    public function search(DataCatalogSearchCriteria $criteria, array $allowedScopes): array
    {
        $dataSources = $this->dataSourceRepository->searchPublishedForDataCatalog($criteria->query, [], [], $allowedScopes);

        $allEntries = [];
        foreach ($dataSources as $dataSource) {
            $allEntries[] = [
                'type' => $dataSource->getSourceType(),
                'kind' => 'data_source',
                'entity' => $dataSource,
            ];
        }

        $categories = $this->findAvailableCategories($allowedScopes);
        $themes = $this->findAvailableThemes($allowedScopes);

        $entries = $this->filterEntries(
            $allEntries,
            $criteria->themes,
            $criteria->categories,
            $criteria->types,
        );

        usort($entries, fn (array $left, array $right): int => $this->compareEntries($left, $right));

        $typeCounters = $this->buildTypeCountersFromEntries($this->filterEntries(
            $allEntries,
            $criteria->themes,
            $criteria->categories,
            [],
        ));
        $categoryCounters = $this->buildCategoryCountersFromEntries($this->filterEntries(
            $allEntries,
            $criteria->themes,
            [],
            $criteria->types,
        ), $categories);
        $themeCounters = $this->buildThemeCountersFromEntries($this->filterEntries(
            $allEntries,
            [],
            $criteria->categories,
            $criteria->types,
        ), $themes);

        $total = count($entries);
        $offset = max(0, ($criteria->page - 1) * $criteria->perPage);
        $items = array_slice($entries, $offset, $criteria->perPage);
        $totalPages = (int) max(1, (int) ceil($total / max(1, $criteria->perPage)));

        return [
            'items' => array_values($items),
            'total' => $total,
            'page' => $criteria->page,
            'perPage' => $criteria->perPage,
            'totalPages' => $totalPages,
            'themes' => $themes,
            'categories' => $categories,
            'typeLabels' => DataSource::TYPE_LABELS,
            'categoryCounters' => $categoryCounters,
            'themeCounters' => $themeCounters,
            'typeCounters' => $typeCounters,
        ];
    }

    /**
     * @param list<array{type: string, kind: 'data_source', entity: DataSource}> $entries
     * @param list<string> $themes
     * @param list<string> $categories
     * @param list<string> $types
     *
     * @return list<array{type: string, kind: 'data_source', entity: DataSource}>
     */
    private function filterEntries(array $entries, array $themes, array $categories, array $types): array
    {
        $filtered = $entries;

        if ($themes !== []) {
            $allowedThemes = array_flip($themes);
            $filtered = array_values(array_filter(
                $filtered,
                static function (array $entry) use ($allowedThemes): bool {
                    $entity = $entry['entity'];

                    return $entity instanceof DataSource && isset($allowedThemes[(string) $entity->getTheme()]);
                }
            ));
        }

        if ($categories !== []) {
            $selectedCategories = array_flip($categories);
            $filtered = array_values(array_filter(
                $filtered,
                static function (array $entry) use ($selectedCategories): bool {
                    $entity = $entry['entity'];
                    if (!$entity instanceof DataSource) {
                        return false;
                    }

                    foreach ($entity->getCategories() as $category) {
                        if (isset($selectedCategories[$category->getSlug()])) {
                            return true;
                        }
                    }

                    return false;
                }
            ));
        }

        if ($types !== []) {
            $allowedTypes = array_flip($types);
            $filtered = array_values(array_filter(
                $filtered,
                static fn (array $entry): bool => isset($allowedTypes[$entry['type']])
            ));
        }

        return $filtered;
    }

    /**
     * @param array{type: string, kind: string, entity: DataSource} $left
     * @param array{type: string, kind: string, entity: DataSource} $right
     */
    private function compareEntries(array $left, array $right): int
    {
        $leftDate = $left['entity']->getPublishedAt() ?? $left['entity']->getCreatedAt();
        $rightDate = $right['entity']->getPublishedAt() ?? $right['entity']->getCreatedAt();

        if ($leftDate === null && $rightDate !== null) {
            return 1;
        }

        if ($leftDate !== null && $rightDate === null) {
            return -1;
        }

        if ($leftDate !== null && $rightDate !== null) {
            $dateComparison = $rightDate <=> $leftDate;
            if ($dateComparison !== 0) {
                return $dateComparison;
            }
        }

        return strcasecmp($left['entity']->getTitle(), $right['entity']->getTitle());
    }

    /** @return list<string> */
    private function findAvailableThemes(array $allowedScopes): array
    {
        $themes = $this->dataSourceRepository->findAvailableThemes($allowedScopes);

        $themes = array_values(array_unique(array_filter($themes)));
        sort($themes);

        return $themes;
    }

    /**
     * @param list<string> $allowedScopes
     *
     * @return list<array{slug: string, name: string, iconKey: string, colorHex: string}>
     */
    private function findAvailableCategories(array $allowedScopes): array
    {
        $categories = $this->dataCategoryRepository->createQueryBuilder('c')
            ->distinct()
            ->innerJoin('c.sources', 's')
            ->andWhere('c.status = :categoryStatus')
            ->andWhere('s.status = :sourceStatus')
            ->andWhere('s.visibilityScope IN (:scopes)')
            ->setParameter('categoryStatus', 'published')
            ->setParameter('sourceStatus', 'published')
            ->setParameter('scopes', $allowedScopes)
            ->orderBy('c.position', 'ASC')
            ->addOrderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();

        $palette = $this->logoPaletteService->getThemePalette(4);

        return array_map(
            fn (DataCategory $category, int $index): array => [
                'slug' => $category->getSlug(),
                'name' => $category->getName(),
                'iconKey' => $category->getIconKey(),
                'colorHex' => $category->getStoredColorHex() ?? $palette[$index % count($palette)],
            ],
            $categories,
            array_keys($categories),
        );
    }

    /** @return array<string, int> */
    private function buildTypeCountersFromEntries(array $entries): array
    {
        $counters = array_fill_keys(array_keys(DataSource::TYPE_LABELS), 0);

        foreach ($entries as $entry) {
            $type = (string) ($entry['type'] ?? '');
            if ($type === '') {
                continue;
            }
            if (!isset($counters[$type])) {
                $counters[$type] = 0;
            }
            ++$counters[$type];
        }

        return $counters;
    }

    /**
     * @param list<array{slug: string, name: string, iconKey: string, colorHex: string}> $categories
     *
     * @return array<string, int>
     */
    private function buildCategoryCountersFromEntries(array $entries, array $categories): array
    {
        $counters = [];
        foreach ($categories as $category) {
            $slug = (string) ($category['slug'] ?? '');
            if ($slug !== '') {
                $counters[$slug] = 0;
            }
        }

        foreach ($entries as $entry) {
            $entity = $entry['entity'] ?? null;
            if (!$entity instanceof DataSource) {
                continue;
            }

            foreach ($entity->getCategories() as $category) {
                $slug = (string) $category->getSlug();
                if ($slug === '') {
                    continue;
                }
                if (!isset($counters[$slug])) {
                    $counters[$slug] = 0;
                }
                ++$counters[$slug];
            }
        }

        return $counters;
    }

    /**
     * @param list<string> $themes
     *
     * @return array<string, int>
     */
    private function buildThemeCountersFromEntries(array $entries, array $themes): array
    {
        $counters = [];
        foreach ($themes as $theme) {
            $themeKey = trim($theme);
            if ($themeKey !== '') {
                $counters[$themeKey] = 0;
            }
        }

        foreach ($entries as $entry) {
            $entity = $entry['entity'] ?? null;
            if (!$entity instanceof DataSource) {
                continue;
            }

            $theme = trim((string) $entity->getTheme());
            if ($theme === '') {
                continue;
            }
            if (!isset($counters[$theme])) {
                $counters[$theme] = 0;
            }
            ++$counters[$theme];
        }

        return $counters;
    }

}
