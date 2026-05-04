<?php

declare(strict_types=1);

namespace App\Application\Cartography\Service;

use App\Application\Cartography\DTO\DataCatalogSearchCriteria;
use App\Domain\Cartography\Entity\InteractiveMap;
use App\Domain\Cartography\Entity\StaticMap;
use App\Infrastructure\Repository\InteractiveMapRepository;
use App\Infrastructure\Repository\StaticMapRepository;

final class DataCatalogService
{
    public function __construct(
        private readonly StaticMapRepository $staticMapRepository,
        private readonly InteractiveMapRepository $interactiveMapRepository,
    ) {
    }

    /**
     * @return array{
     *   items: list<array{type: 'static'|'interactive', entity: StaticMap|InteractiveMap}>,
     *   total: int,
     *   page: int,
     *   perPage: int,
     *   totalPages: int,
     *   themes: list<string>,
     *   typeCounters: array{static: int, interactive: int}
     * }
     */
    public function search(DataCatalogSearchCriteria $criteria): array
    {
        $staticMaps = $this->staticMapRepository->searchPublishedForDataCatalog($criteria->query, $criteria->themes);
        $interactiveMaps = $this->interactiveMapRepository->searchPublishedForDataCatalog($criteria->query);

        $entries = [];
        foreach ($staticMaps as $staticMap) {
            $entries[] = [
                'type' => 'static',
                'entity' => $staticMap,
            ];
        }
        foreach ($interactiveMaps as $interactiveMap) {
            $entries[] = [
                'type' => 'interactive',
                'entity' => $interactiveMap,
            ];
        }

        if ($criteria->types !== []) {
            $allowedTypes = array_flip($criteria->types);
            $entries = array_values(array_filter(
                $entries,
                static fn (array $entry): bool => isset($allowedTypes[$entry['type']])
            ));
        }

        usort($entries, fn (array $left, array $right): int => $this->compareEntries($left, $right));

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
            'themes' => $this->staticMapRepository->findAvailableThemes(),
            'typeCounters' => [
                'static' => count($staticMaps),
                'interactive' => count($interactiveMaps),
            ],
        ];
    }

    /**
     * @param array{type: 'static'|'interactive', entity: StaticMap|InteractiveMap} $left
     * @param array{type: 'static'|'interactive', entity: StaticMap|InteractiveMap} $right
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
}
