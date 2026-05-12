<?php

declare(strict_types=1);

namespace App\UI\Controller;

use App\Application\Access\Service\VisibilityScopeResolver;
use App\Application\Cartography\DTO\DataCatalogSearchCriteria;
use App\Application\Cartography\Service\DataCatalogService;
use App\Domain\Access\Entity\User;
use App\Domain\Access\Entity\UserFavorite;
use App\Domain\Cartography\Entity\DatasetResource;
use App\Domain\Cartography\Entity\DataSource;
use App\Application\Interop\Sig\SigHealthcheckService;
use App\Infrastructure\Repository\DataSourceRepository;
use App\Infrastructure\Repository\InteractiveMapRepository;
use App\Infrastructure\Repository\StaticMapRepository;
use App\Infrastructure\Repository\UserFavoriteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CartographyController extends AbstractController
{
    public function __construct(
        private readonly VisibilityScopeResolver $visibilityScopeResolver,
        private readonly UserFavoriteRepository $favoriteRepository,
    ) {}

    #[Route('/donnees-cartes', name: 'app_data_catalog', methods: ['GET'])]
    #[Route('/cartotheque', name: 'app_static_map_catalog', methods: ['GET'])]
    #[Route('/cartes-interactives', name: 'app_interactive_map_catalog', methods: ['GET'])]
    public function dataCatalog(Request $request, DataCatalogService $catalogService): Response
    {
        $criteria = DataCatalogSearchCriteria::fromRequest($request);
        $catalog = $catalogService->search(
            $criteria,
            $this->visibilityScopeResolver->resolveForUser($this->getUser()),
        );

        if ($request->isXmlHttpRequest() || $request->query->getBoolean('partial')) {
            if ($request->query->getBoolean('ajax')) {
                return new JsonResponse([
                    'html' => $this->renderView('public/catalog/_results.html.twig', [
                        'catalog' => $catalog,
                        'criteria' => $criteria,
                    ]),
                    'typeCounters' => $catalog['typeCounters'] ?? [],
                    'categoryCounters' => $catalog['categoryCounters'] ?? [],
                    'themeCounters' => $catalog['themeCounters'] ?? [],
                    'total' => $catalog['total'] ?? 0,
                    'page' => $catalog['page'] ?? 1,
                    'totalPages' => $catalog['totalPages'] ?? 1,
                ]);
            }

            return $this->render('public/catalog/_results.html.twig', [
                'catalog' => $catalog,
                'criteria' => $criteria,
            ]);
        }

        return $this->render('public/catalog/index.html.twig', [
            'catalog' => $catalog,
            'criteria' => $criteria,
        ]);
    }

    #[Route('/donnees-cartes/source/{slug}', name: 'app_data_source_show', methods: ['GET'])]
    public function dataSourceShow(
        string $slug,
        DataSourceRepository $repository,
    ): Response
    {
        $allowedScopes = $this->visibilityScopeResolver->resolveForUser($this->getUser());
        $source = $repository->findOnePublishedVisibleBySlug(
            $slug,
            $allowedScopes,
        );
        if ($source === null) {
            throw $this->createNotFoundException('Source de données introuvable.');
        }

        $primaryAccess = $this->resolveSourcePrimaryAccess(
            $source,
        );

        return $this->render('public/catalog/source_show.html.twig', [
            'source' => $source,
            'primaryAccess' => $primaryAccess,
            'favoriteMeta' => $this->buildFavoriteMeta(UserFavorite::KIND_DATA_SOURCE, $source->getSlug()),
        ]);
    }

    #[Route('/cartotheque/{slug}', name: 'app_static_map_show', methods: ['GET'])]
    public function staticShow(string $slug, StaticMapRepository $repository): Response
    {
        $allowedScopes = $this->visibilityScopeResolver->resolveForUser($this->getUser());
        $map = $repository->findOnePublishedVisibleBySlug($slug, $allowedScopes);
        if ($map === null) {
            throw $this->createNotFoundException('Carte introuvable.');
        }

        $visibleDatasets = array_values(array_filter(
            $map->getDatasetResources()->toArray(),
            static fn (DatasetResource $dataset): bool => \in_array($dataset->getVisibilityScope(), $allowedScopes, true),
        ));

        return $this->render('public/cartography/show.html.twig', [
            'map' => $map,
            'visibleDatasets' => $visibleDatasets,
            'favoriteMeta' => $this->buildFavoriteMeta(UserFavorite::KIND_STATIC_MAP, $map->getSlug()),
        ]);
    }

    #[Route('/cartes-interactives/{slug}', name: 'app_interactive_map_show', methods: ['GET'])]
    public function interactiveShow(
        string $slug,
        InteractiveMapRepository $repository,
        SigHealthcheckService $sigHealthcheckService,
    ): Response {
        $allowedScopes = $this->visibilityScopeResolver->resolveForUser($this->getUser());
        $map = $repository->findOnePublishedVisibleBySlug(
            $slug,
            $allowedScopes,
        );
        if ($map === null) {
            throw $this->createNotFoundException('Carte interactive introuvable.');
        }

        $health = $sigHealthcheckService->checkAll();
        $isDegradedMode = false;
        foreach ($health as $check) {
            if ($check->available === false) {
                $isDegradedMode = true;
                break;
            }
        }

        return $this->render('public/interactive/show.html.twig', [
            'map' => $map,
            'healthchecks' => $health,
            'degradedMode' => $isDegradedMode,
            'favoriteMeta' => $this->buildFavoriteMeta(UserFavorite::KIND_INTERACTIVE_MAP, $map->getSlug()),
        ]);
    }

    /**
     * @return array{enabled: bool, isFavorite: bool}
     */
    private function buildFavoriteMeta(string $kind, string $slug): array
    {
        if (!$this->isGranted('ROLE_EXTERNAL')) {
            return ['enabled' => false, 'isFavorite' => false];
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            return ['enabled' => false, 'isFavorite' => false];
        }

        return [
            'enabled' => true,
            'isFavorite' => $this->favoriteRepository->findOneForUserAndResource($user, $kind, $slug) !== null,
        ];
    }

    /** @return array{url: ?string, label: string, reason: ?string} */
    private function resolveSourcePrimaryAccess(
        DataSource $source,
    ): array {
        $sourceType = $source->getSourceType();
        $sourceUrl = $this->normalizePublicResourceLink($source->getSourceUrl());
        $filePath = $this->normalizePublicResourceLink($source->getFilePath(), preferFilesRoute: true);
        $endpointUrl = $this->normalizePublicResourceLink($source->getServiceEndpoint()?->getBaseUrl());

        $publicSourceUrl = null;
        if (
            $sourceUrl !== null
            && str_starts_with($sourceUrl, '/donnees-cartes/source/') === false
            && str_starts_with($sourceUrl, '/cartes-interactives/') === false
            && str_starts_with($sourceUrl, '/cartotheque/') === false
            && $this->isPublicWebUrl($sourceUrl)
        ) {
            $publicSourceUrl = $sourceUrl;
        }

        $publicEndpointUrl = null;
        if ($endpointUrl !== null && $this->isPublicWebUrl($endpointUrl)) {
            $publicEndpointUrl = $endpointUrl;
        }

        // Un seul principe UX: fiche source d'abord, puis action principale selon le type.
        if ($sourceType === DataSource::TYPE_DATA_FILE || $sourceType === DataSource::TYPE_STATIC_MAP) {
            if ($filePath !== null) {
                if (str_starts_with($filePath, '/') && !$this->isPublishedLocalPathAvailable($filePath)) {
                    return [
                        'url' => null,
                        'label' => 'Fichier indisponible',
                        'reason' => 'Le fichier lié à cette source n’est pas disponible sur le portail.',
                    ];
                }

                return [
                    'url' => $filePath,
                    'label' => 'Télécharger le fichier',
                    'reason' => null,
                ];
            }
            if ($publicSourceUrl !== null) {
                return [
                    'url' => $publicSourceUrl,
                    'label' => 'Ouvrir la ressource',
                    'reason' => null,
                ];
            }
            if ($publicEndpointUrl !== null) {
                return [
                    'url' => $publicEndpointUrl,
                    'label' => 'Accéder au service',
                    'reason' => null,
                ];
            }

            return [
                'url' => null,
                'label' => 'Accès indisponible',
                'reason' => 'Aucun fichier ou lien public n’est disponible pour cette fiche.',
            ];
        }

        if ($publicSourceUrl !== null) {
            return [
                'url' => $publicSourceUrl,
                'label' => $sourceType === DataSource::TYPE_WMS || $sourceType === DataSource::TYPE_WFS
                    ? 'Ouvrir le service'
                    : 'Accéder à la source',
                'reason' => null,
            ];
        }

        if ($publicEndpointUrl !== null) {
            return [
                'url' => $publicEndpointUrl,
                'label' => 'Accéder au service',
                'reason' => null,
            ];
        }

        if ($endpointUrl !== null && !$this->isPublicWebUrl($endpointUrl)) {
            return [
                'url' => null,
                'label' => 'Service interne',
                'reason' => 'Le service lié à cette source est interne et n’est pas accessible publiquement.',
            ];
        }

        return [
            'url' => null,
            'label' => 'Accès indisponible',
            'reason' => 'Aucun lien de ressource n’est disponible pour cette fiche.',
        ];
    }

    private function normalizePublicResourceLink(?string $value, bool $preferFilesRoute = false): ?string
    {
        $candidate = trim((string) $value);
        if ($candidate === '') {
            return null;
        }

        if (str_starts_with($candidate, 'http://') || str_starts_with($candidate, 'https://')) {
            return $candidate;
        }

        if (!str_starts_with($candidate, '/')) {
            $candidate = '/'.$candidate;
        }

        if ($preferFilesRoute === true && str_starts_with($candidate, '/files/')) {
            return $candidate;
        }

        if (str_starts_with($candidate, '/uploads/') || str_starts_with($candidate, '/data/') || str_starts_with($candidate, '/files/')) {
            return $candidate;
        }

        return '/uploads/data-sources/'.ltrim($candidate, '/');
    }

    private function isPublicWebUrl(string $url): bool
    {
        if (str_starts_with($url, '/')) {
            return true;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '') {
            return false;
        }

        if ($host === 'localhost' || str_ends_with($host, '.local')) {
            return false;
        }

        return true;
    }

    private function isPublishedLocalPathAvailable(string $path): bool
    {
        if (!str_starts_with($path, '/')) {
            return false;
        }

        $projectDir = (string) $this->getParameter('kernel.project_dir');
        $publicDir = $projectDir.'/public';
        $relativePath = ltrim($path, '/');

        $candidates = [
            $publicDir.'/'.$relativePath,
        ];

        if (str_starts_with($path, '/files/')) {
            $relativeFile = ltrim(substr($path, strlen('/files/')), '/');
            $candidates[] = $publicDir.'/uploads/'.$relativeFile;
            $candidates[] = $publicDir.'/uploads/data-sources/'.basename($relativeFile);
        }

        foreach ($candidates as $candidate) {
            if (is_file($candidate) && is_readable($candidate)) {
                return true;
            }
        }

        return false;
    }
}
