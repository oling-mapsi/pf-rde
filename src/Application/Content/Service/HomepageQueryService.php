<?php

declare(strict_types=1);

namespace App\Application\Content\Service;

use App\Domain\Access\VisibilityScope;
use App\Domain\Content\Entity\HomepageContent;
use App\Domain\Content\Entity\HomepageSection;
use App\Infrastructure\Repository\HomepageContentRepository;
use App\Infrastructure\Repository\HomepageSectionRepository;
use App\Infrastructure\Repository\NewsRepository;
use App\Infrastructure\Repository\QuickLinkRepository;
use App\Infrastructure\Repository\DataSourceRepository;
use App\Infrastructure\Repository\DatasetResourceRepository;
use App\Infrastructure\Repository\StaticMapRepository;
use App\Infrastructure\Repository\TaxonomyTermRepository;

final class HomepageQueryService
{
    public function __construct(
        private readonly NewsRepository $newsRepository,
        private readonly QuickLinkRepository $quickLinkRepository,
        private readonly DataSourceRepository $dataSourceRepository,
        private readonly DatasetResourceRepository $datasetResourceRepository,
        private readonly StaticMapRepository $staticMapRepository,
        private readonly HomepageContentRepository $homepageContentRepository,
        private readonly HomepageSectionRepository $homepageSectionRepository,
        private readonly TaxonomyTermRepository $taxonomyTermRepository,
    ) {
    }

    public function buildHomepageViewModel(): array
    {
        $legacyDatasetCount = $this->datasetResourceRepository->countPublishedResources();
        $legacyThemeCount = $this->staticMapRepository->countPublishedThemes();
        $sourceCount = $this->dataSourceRepository->countPublishedSources();
        $sourceThemeCount = $this->dataSourceRepository->countPublishedThemes();
        $datasetCount = $sourceCount > 0 ? $sourceCount : $legacyDatasetCount;
        $themeCount = $sourceThemeCount > 0 ? $sourceThemeCount : $legacyThemeCount;
        $heroThemeItems = $this->buildFeaturedThemeHeroItems();
        $homepage = $this->homepageContentRepository->findPublishedHomepage();
        $sections = $this->homepageSectionRepository->findPublishedOrdered();
        $homepageSections = $sections === []
            ? $this->buildDefaultSections($datasetCount, $themeCount, $heroThemeItems)
            : $this->buildSectionsViewModel($sections, $datasetCount, $themeCount, $heroThemeItems);

        return [
            'homepage' => $homepage,
            'homepageHero' => $this->buildHeroViewModel($homepage, $datasetCount, $themeCount),
            'homeHeroThemes' => $heroThemeItems,
            'homepageSections' => $homepageSections,
            'latestNews' => $this->newsRepository->findLatestPublished(3),
            'quickLinks' => $this->quickLinkRepository->findBy(['status' => 'published'], ['position' => 'ASC'], 8),
            'datasetCount' => $datasetCount,
            'themeCount' => $themeCount,
            'sourceCount' => $sourceCount,
            'sourceThemeCount' => $sourceThemeCount,
        ];
    }

    /** @return array<string, mixed> */
    private function buildHeroViewModel(?HomepageContent $homepage, int $datasetCount, int $themeCount): array
    {
        $searchIntro = $homepage?->getSearchIntro();
        $title = $homepage?->getHeroTitle() ?: 'La plateforme Open Data et SIG de Routes de Guadeloupe';
        $baseline = $homepage?->getHeroBaseline() ?: 'Plateforme de référence pour la cartographie routière de la Guadeloupe. Cartothèque statique, cartes interactives, information usagers et services agents.';

        return [
            'title' => $title,
            'titleLines' => $this->splitHeroTitle($title),
            'baseline' => $baseline,
            'baselineLines' => $this->splitHeroBaseline($baseline),
            'searchIntro' => $searchIntro ?: sprintf('Explorer les %d jeux de données dans les %d thèmes', $datasetCount, $themeCount),
            'searchPlaceholder' => $homepage?->getSearchPlaceholder() ?: 'Rechercher une carte, un jeu de données ou une ressource SIG',
            'primaryCtaLabel' => $homepage?->getPrimaryCtaLabel(),
            'primaryCtaUrl' => $homepage?->getPrimaryCtaUrl(),
        ];
    }

    /** @return list<string> */
    private function splitHeroTitle(string $title): array
    {
        $lines = $this->splitEditableLines($title);
        if (\count($lines) > 1) {
            return $lines;
        }

        $oldHomeTitleSuffix = ' de Routes de Guadeloupe';
        if (str_ends_with($title, $oldHomeTitleSuffix)) {
            return [
                substr($title, 0, -\strlen($oldHomeTitleSuffix)),
                'de Routes de Guadeloupe',
            ];
        }

        return $lines;
    }

    /** @return list<string> */
    private function splitHeroBaseline(string $baseline): array
    {
        $lines = $this->splitEditableLines($baseline);
        if (\count($lines) > 1) {
            return $lines;
        }

        $sentences = preg_split('/(?<=\\.)\\s+/', $baseline) ?: [];
        $sentences = array_values(array_filter(array_map('trim', $sentences)));

        return $sentences !== [] ? $sentences : $lines;
    }

    /** @return list<string> */
    private function splitEditableLines(string $text): array
    {
        $lines = preg_split('/\\R+/', $text) ?: [];
        $lines = array_values(array_filter(array_map('trim', $lines)));

        return $lines !== [] ? $lines : [$text];
    }

    /**
     * @param list<HomepageSection> $sections
     * @param list<array{title: string, url: string, label: string, icon: string, color: string, accent: string}> $featuredHeroItems
     *
     * @return list<array<string, mixed>>
     */
    private function buildSectionsViewModel(array $sections, int $datasetCount, int $themeCount, array $featuredHeroItems): array
    {
        return array_map(fn (HomepageSection $section): array => $this->buildSectionViewModel($section, $datasetCount, $themeCount, $featuredHeroItems), $sections);
    }

    /** @return array<string, mixed> */
    private function buildSectionViewModel(HomepageSection $section, int $datasetCount, int $themeCount, array $featuredHeroItems): array
    {
        $items = $section->getItemsConfigArray();
        $filters = $section->getFiltersConfigArray();

        if ($section->getType() === HomepageSection::TYPE_LATEST_NEWS) {
            $items = $this->newsRepository->findLatestPublished($section->getItemLimit());
        }

        if ($section->getType() === HomepageSection::TYPE_FEATURED_RESOURCES) {
            $items = $this->staticMapRepository->findPublishedForHomepage($section->getItemLimit(), $filters);
        }

        if ($section->getType() === HomepageSection::TYPE_QUICK_LINKS) {
            $items = $this->quickLinkRepository->findBy(['status' => 'published'], ['position' => 'ASC'], $section->getItemLimit());
        }

        if ($section->getType() === HomepageSection::TYPE_DATA_HIGHLIGHTS) {
            $items = [
                ['title' => 'Jeux de données publiés', 'text' => sprintf('%d ressource%s disponible%s au format réutilisable.', $datasetCount, $datasetCount > 1 ? 's' : '', $datasetCount > 1 ? 's' : '')],
                ['title' => 'Thèmes de diffusion', 'text' => sprintf('%d thème%s organisé%s pour faciliter la recherche.', $themeCount, $themeCount > 1 ? 's' : '', $themeCount > 1 ? 's' : '')],
            ];
        }

        if ($section->getBackgroundStyle() === 'kpi' && $items === [] && $featuredHeroItems !== []) {
            $items = $featuredHeroItems;
        }

        $viewModel = [
            'id' => $section->getId(),
            'name' => $section->getName(),
            'type' => $section->getType(),
            'title' => $section->getTitle(),
            'intro' => $section->getIntro(),
            'body' => $section->getBody(),
            'imagePath' => $section->getImagePath(),
            'ctaLabel' => $section->getCtaLabel(),
            'ctaUrl' => $section->getCtaUrl(),
            'layout' => $section->getLayout(),
            'backgroundStyle' => $section->getBackgroundStyle(),
            'items' => $items,
        ];

        return $viewModel;
    }

    /** @return list<array<string, mixed>> */
    private function buildDefaultSections(int $datasetCount, int $themeCount, array $featuredHeroItems): array
    {
        return [
            [
                'id' => 'quick-access',
                'type' => HomepageSection::TYPE_MANUAL_CARDS,
                'title' => 'Accès principaux du portail',
                'intro' => null,
                'body' => null,
                'imagePath' => null,
                'ctaLabel' => null,
                'ctaUrl' => null,
                'layout' => HomepageSection::LAYOUT_GRID,
                'backgroundStyle' => 'kpi',
                'items' => $featuredHeroItems !== [] ? $featuredHeroItems : [
                    ['title' => 'Cartothèque statique', 'url' => '/donnees-cartes?type%5B0%5D=static', 'label' => 'Accéder à la cartothèque', 'accent' => 'orange', 'icon' => 'map'],
                    ['title' => 'Cartes interactives', 'url' => '/donnees-cartes?type%5B0%5D=interactive', 'label' => 'Voir les cartes', 'accent' => 'blue', 'icon' => 'layers'],
                    ['title' => 'Information publique', 'url' => '/actualites', 'label' => 'Lire les actualités', 'accent' => 'yellow', 'icon' => 'megaphone'],
                    ['title' => 'Espace agents', 'url' => '/connexion', 'label' => 'Accéder à l’espace', 'accent' => 'green', 'icon' => 'shield'],
                ],
            ],
            [
                'id' => 'featured-resources',
                'type' => HomepageSection::TYPE_FEATURED_RESOURCES,
                'title' => 'Données de référence',
                'intro' => sprintf('%d jeu%s de données publié%s dans %d thème%s.', $datasetCount, $datasetCount > 1 ? 'x' : '', $datasetCount > 1 ? 's' : '', $themeCount, $themeCount > 1 ? 's' : ''),
                'body' => null,
                'imagePath' => null,
                'ctaLabel' => 'Explorer le catalogue',
                'ctaUrl' => '/donnees-cartes',
                'layout' => HomepageSection::LAYOUT_GRID,
                'backgroundStyle' => 'light',
                'items' => $this->staticMapRepository->findPublishedForHomepage(3),
            ],
            [
                'id' => 'latest-news',
                'type' => HomepageSection::TYPE_LATEST_NEWS,
                'title' => 'Actualités et communiqués',
                'intro' => null,
                'body' => null,
                'imagePath' => null,
                'ctaLabel' => 'Voir toutes les actualités',
                'ctaUrl' => '/actualites',
                'layout' => HomepageSection::LAYOUT_GRID,
                'backgroundStyle' => 'muted',
                'items' => $this->newsRepository->findLatestPublished(3),
            ],
        ];
    }

    /** @return list<array{title: string, url: string, label: string, icon: string, color: string, accent: string}> */
    private function buildFeaturedThemeHeroItems(?int $limit = null): array
    {
        $themes = $this->taxonomyTermRepository->findFeaturedMapThemes($limit);
        if ($themes === []) {
            $themes = $this->taxonomyTermRepository->findActiveMapThemes($limit);
        }

        $fallbackAccents = ['orange', 'blue', 'yellow', 'green'];
        $items = [];

        foreach ($themes as $index => $theme) {
            $themeName = $theme->getLabel();
            $items[] = [
                'title' => $themeName,
                'url' => '/donnees-cartes?theme%5B0%5D='.rawurlencode($themeName),
                'label' => sprintf('Filtrer le catalogue sur le thème %s', $themeName),
                'icon' => $theme->getIconKey(),
                'color' => $theme->getColorHex(),
                'accent' => $fallbackAccents[$index % \count($fallbackAccents)],
            ];
        }

        if ($items !== []) {
            return $items;
        }

        $fallbackThemes = array_unique(array_filter(array_merge(
            $this->dataSourceRepository->findAvailableThemes(VisibilityScope::all()),
            $this->staticMapRepository->findAvailableThemes(VisibilityScope::all()),
        )));

        $fallbackColors = ['#FC5000', '#38B4E7', '#FBD002', '#AAAE02'];
        $fallbackIcons = ['map', 'layers', 'route', 'shield'];

        foreach (array_slice(array_values($fallbackThemes), 0, $limit) as $index => $themeName) {
            $items[] = [
                'title' => $themeName,
                'url' => '/donnees-cartes?theme%5B0%5D='.rawurlencode($themeName),
                'label' => sprintf('Filtrer le catalogue sur le thème %s', $themeName),
                'icon' => $fallbackIcons[$index % \count($fallbackIcons)],
                'color' => $fallbackColors[$index % \count($fallbackColors)],
                'accent' => $fallbackAccents[$index % \count($fallbackAccents)],
            ];
        }

        return $items;
    }
}
