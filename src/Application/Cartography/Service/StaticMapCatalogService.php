<?php

declare(strict_types=1);

namespace App\Application\Cartography\Service;

use App\Application\Cartography\DTO\StaticMapSearchCriteria;
use App\Domain\Cartography\Entity\StaticMap;
use App\Infrastructure\Repository\StaticMapRepository;

final class StaticMapCatalogService
{
    public function __construct(private readonly StaticMapRepository $staticMapRepository)
    {
    }

    /**
     * @return array{items: list<StaticMap>, total: int, page: int, perPage: int, totalPages: int, themes: list<string>}
     */
    public function search(StaticMapSearchCriteria $criteria): array
    {
        $result = $this->staticMapRepository->searchPublished($criteria);
        $totalPages = (int) max(1, (int) ceil($result['total'] / max(1, $result['perPage'])));

        return [
            ...$result,
            'totalPages' => $totalPages,
            'themes' => $this->staticMapRepository->findAvailableThemes(),
        ];
    }
}
