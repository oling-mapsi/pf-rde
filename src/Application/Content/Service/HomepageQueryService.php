<?php

declare(strict_types=1);

namespace App\Application\Content\Service;

use App\Infrastructure\Repository\NewsRepository;
use App\Infrastructure\Repository\QuickLinkRepository;
use App\Infrastructure\Repository\DatasetResourceRepository;
use App\Infrastructure\Repository\StaticMapRepository;

final class HomepageQueryService
{
    public function __construct(
        private readonly NewsRepository $newsRepository,
        private readonly QuickLinkRepository $quickLinkRepository,
        private readonly DatasetResourceRepository $datasetResourceRepository,
        private readonly StaticMapRepository $staticMapRepository,
    ) {
    }

    public function buildHomepageViewModel(): array
    {
        return [
            'latestNews' => $this->newsRepository->findLatestPublished(3),
            'quickLinks' => $this->quickLinkRepository->findBy(['status' => 'published'], ['position' => 'ASC'], 8),
            'datasetCount' => $this->datasetResourceRepository->countPublishedResources(),
            'themeCount' => $this->staticMapRepository->countPublishedThemes(),
        ];
    }
}
