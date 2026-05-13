<?php

declare(strict_types=1);

namespace App\Application\Access\Service;

use App\Domain\Access\Entity\UserFavorite;
use App\Domain\Cartography\Entity\DataSource;
use App\Domain\Cartography\Entity\InteractiveMap;
use App\Domain\Cartography\Entity\StaticMap;
use App\Infrastructure\Repository\DataSourceRepository;
use App\Infrastructure\Repository\InteractiveMapRepository;
use App\Infrastructure\Repository\StaticMapRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class CatalogResourceLocator
{
    public function __construct(
        private readonly DataSourceRepository $dataSourceRepository,
        private readonly StaticMapRepository $staticMapRepository,
        private readonly InteractiveMapRepository $interactiveMapRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * @param list<string> $allowedScopes
     *
     * @return array{kind: string, slug: string, title: string, url: string, thumbnailPath?: ?string}|null
     */
    public function findFavoriteTarget(string $kind, string $slug, array $allowedScopes): ?array
    {
        $normalizedKind = strtolower(trim($kind));
        $normalizedSlug = trim($slug);

        if ($normalizedSlug === '') {
            return null;
        }

        if ($normalizedKind === UserFavorite::KIND_DATA_SOURCE) {
            $source = $this->dataSourceRepository->findOnePublishedVisibleBySlug($normalizedSlug, $allowedScopes);
            if (!$source instanceof DataSource) {
                return null;
            }

            return [
                'kind' => UserFavorite::KIND_DATA_SOURCE,
                'slug' => $source->getSlug(),
                'title' => $source->getTitle(),
                'url' => $this->urlGenerator->generate('app_data_source_show', ['slug' => $source->getSlug()]),
                'thumbnailPath' => $source->getThumbnailPath(),
            ];
        }

        if ($normalizedKind === UserFavorite::KIND_STATIC_MAP) {
            $map = $this->staticMapRepository->findOnePublishedVisibleBySlug($normalizedSlug, $allowedScopes);
            if (!$map instanceof StaticMap) {
                return null;
            }

            return [
                'kind' => UserFavorite::KIND_STATIC_MAP,
                'slug' => $map->getSlug(),
                'title' => $map->getTitle(),
                'url' => $this->urlGenerator->generate('app_static_map_show', ['slug' => $map->getSlug()]),
                'thumbnailPath' => $map->getThumbnailPath(),
            ];
        }

        if ($normalizedKind === UserFavorite::KIND_INTERACTIVE_MAP) {
            $map = $this->interactiveMapRepository->findOnePublishedVisibleBySlug($normalizedSlug, $allowedScopes);
            if (!$map instanceof InteractiveMap) {
                return null;
            }

            return [
                'kind' => UserFavorite::KIND_INTERACTIVE_MAP,
                'slug' => $map->getSlug(),
                'title' => $map->getTitle(),
                'url' => $this->urlGenerator->generate('app_interactive_map_show', ['slug' => $map->getSlug()]),
            ];
        }

        return null;
    }
}
