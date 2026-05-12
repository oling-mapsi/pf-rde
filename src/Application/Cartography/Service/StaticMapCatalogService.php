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
     * @param list<string> $allowedScopes
     *
     * @return array{items: list<StaticMap>, total: int, page: int, perPage: int, totalPages: int, themes: list<string>}
     */
    public function search(StaticMapSearchCriteria $criteria, array $allowedScopes): array
    {
        $result = $this->staticMapRepository->searchPublished($criteria, $allowedScopes);
        $totalPages = (int) max(1, (int) ceil($result['total'] / max(1, $result['perPage'])));

        return [
            ...$result,
            'totalPages' => $totalPages,
            'themes' => $this->staticMapRepository->findAvailableThemes($allowedScopes),
        ];
    }
}
