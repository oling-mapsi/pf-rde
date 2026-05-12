<?php

declare(strict_types=1);

namespace App\UI\Api;

use App\Application\Access\Service\VisibilityScopeResolver;
use App\Application\Cartography\Service\InteractiveMapMockDataService;
use App\Application\Interop\Sig\SigHealthcheckService;
use App\Domain\Cartography\Entity\MapLayer;
use App\Infrastructure\Repository\InteractiveMapRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/interactive-maps', name: 'api_interactive_map_')]
final class InteractiveMapApiController extends AbstractController
{
    public function __construct(
        private readonly VisibilityScopeResolver $visibilityScopeResolver,
        #[Autowire('%env(string:MAP_BASEMAP_TILES_URL)%')]
        private readonly string $basemapTilesUrl,
        #[Autowire('%env(string:MAP_BASEMAP_ATTRIBUTION)%')]
        private readonly string $basemapAttribution,
        #[Autowire('%env(int:MAP_BASEMAP_MAX_ZOOM)%')]
        private readonly int $basemapMaxZoom,
    ) {
    }

    #[Route('/{slug}/bootstrap', name: 'bootstrap', methods: ['GET'])]
    public function bootstrap(
        string $slug,
        InteractiveMapRepository $repository,
        SigHealthcheckService $sigHealthcheckService,
        InteractiveMapMockDataService $dataService,
    ): JsonResponse {
        $map = $repository->findOnePublishedVisibleBySlug(
            $slug,
            $this->visibilityScopeResolver->resolveForUser($this->getUser()),
        );
        if ($map === null) {
            return $this->json(['message' => 'Carte interactive introuvable.'], 404);
        }

        $healthchecks = [];
        $isDegradedMode = false;
        foreach ($sigHealthcheckService->checkAll() as $check) {
            $healthchecks[] = [
                'name' => $check->name,
                'serviceType' => $check->serviceType,
                'available' => $check->available,
                'message' => $check->message,
            ];

            if ($check->available === false) {
                $isDegradedMode = true;
            }
        }

        $configuredLayers = $map->getLayers()->toArray();
        usort(
            $configuredLayers,
            static fn (MapLayer $left, MapLayer $right): int => $left->getRenderOrder() <=> $right->getRenderOrder()
        );
        $configuredLayerIds = array_values(array_filter(
            array_map(static fn (MapLayer $layer): string => strtolower(trim($layer->getName())), $configuredLayers),
            static fn (string $layerName): bool => $layerName !== ''
        ));

        $payload = $dataService->buildBootstrap(
            slug: $slug,
            defaultCenterLat: $map->getDefaultCenterLat(),
            defaultCenterLng: $map->getDefaultCenterLng(),
            defaultZoom: $map->getDefaultZoom(),
            configuredLayerIds: $configuredLayerIds,
            degradedMode: $isDegradedMode,
            degradedModeMessage: $map->getDegradedModeMessage(),
        );
        $payload['map']['basemap'] = [
            'provider' => 'osm',
            'type' => 'raster',
            'tiles' => [$this->basemapTilesUrl],
            'tileSize' => 256,
            'maxZoom' => $this->basemapMaxZoom,
            'attribution' => $this->basemapAttribution,
        ];

        return $this->json([
            ...$payload,
            'healthchecks' => $healthchecks,
        ]);
    }

    #[Route('/{slug}/features', name: 'features', methods: ['GET'])]
    public function features(
        string $slug,
        Request $request,
        InteractiveMapRepository $repository,
        InteractiveMapMockDataService $dataService,
    ): JsonResponse {
        $map = $repository->findOnePublishedVisibleBySlug(
            $slug,
            $this->visibilityScopeResolver->resolveForUser($this->getUser()),
        );
        if ($map === null) {
            return $this->json(['message' => 'Carte interactive introuvable.'], 404);
        }

        $features = $dataService->getFeatures(
            slug: $slug,
            requestedLayerIds: $this->parseLayerIds($request),
            status: $this->nullableFilter($request->query->all()['status'] ?? null),
            category: $this->nullableFilter($request->query->all()['category'] ?? null),
            query: $this->nullableFilter($request->query->all()['q'] ?? null),
        );

        return $this->json($features);
    }

    #[Route('/{slug}/basemap', name: 'basemap', methods: ['GET'])]
    public function basemap(
        string $slug,
        InteractiveMapRepository $repository,
        InteractiveMapMockDataService $dataService,
    ): JsonResponse {
        $map = $repository->findOnePublishedVisibleBySlug(
            $slug,
            $this->visibilityScopeResolver->resolveForUser($this->getUser()),
        );
        if ($map === null) {
            return $this->json(['message' => 'Carte interactive introuvable.'], 404);
        }

        return $this->json($dataService->getBasemap($slug));
    }

    #[Route('/{slug}/legend', name: 'legend', methods: ['GET'])]
    public function legend(
        string $slug,
        Request $request,
        InteractiveMapRepository $repository,
        InteractiveMapMockDataService $dataService,
    ): JsonResponse {
        $map = $repository->findOnePublishedVisibleBySlug(
            $slug,
            $this->visibilityScopeResolver->resolveForUser($this->getUser()),
        );
        if ($map === null) {
            return $this->json(['message' => 'Carte interactive introuvable.'], 404);
        }

        $legend = $dataService->getLegend(
            slug: $slug,
            requestedLayerIds: $this->parseLayerIds($request),
            status: $this->nullableFilter($request->query->all()['status'] ?? null),
            category: $this->nullableFilter($request->query->all()['category'] ?? null),
            query: $this->nullableFilter($request->query->all()['q'] ?? null),
        );

        return $this->json($legend);
    }

    #[Route('/{slug}/feature-info', name: 'feature_info', methods: ['GET'])]
    public function featureInfo(
        string $slug,
        Request $request,
        InteractiveMapRepository $repository,
        InteractiveMapMockDataService $dataService,
    ): JsonResponse {
        $map = $repository->findOnePublishedVisibleBySlug(
            $slug,
            $this->visibilityScopeResolver->resolveForUser($this->getUser()),
        );
        if ($map === null) {
            return $this->json(['message' => 'Carte interactive introuvable.'], 404);
        }

        $rawParams = $request->query->all();
        $lng = is_scalar($rawParams['lng'] ?? null) ? (float) $rawParams['lng'] : 0.0;
        $lat = is_scalar($rawParams['lat'] ?? null) ? (float) $rawParams['lat'] : 0.0;

        if ($lng < -180 || $lng > 180 || $lat < -90 || $lat > 90) {
            return $this->json(['message' => 'Coordonnées invalides.'], 400);
        }

        $featureInfo = $dataService->getFeatureInfo(
            slug: $slug,
            lng: $lng,
            lat: $lat,
            requestedLayerIds: $this->parseLayerIds($request),
        );

        if ($featureInfo === null) {
            return $this->json(['feature' => null]);
        }

        return $this->json($featureInfo);
    }

    /**
     * @return list<string>
     */
    private function parseLayerIds(Request $request): array
    {
        $rawParams = $request->query->all();
        $values = [];

        $layers = $rawParams['layers'] ?? null;
        if (is_array($layers)) {
            foreach ($layers as $layerValue) {
                if (!is_scalar($layerValue)) {
                    continue;
                }
                $values[] = (string) $layerValue;
            }
        } elseif (is_scalar($layers)) {
            $values = array_merge($values, explode(',', (string) $layers));
        }

        $layer = $rawParams['layer'] ?? null;
        if (is_scalar($layer)) {
            $values[] = (string) $layer;
        }

        $normalized = [];
        foreach ($values as $value) {
            $candidate = strtolower(trim($value));
            if ($candidate === '') {
                continue;
            }
            $normalized[] = $candidate;
        }

        return array_values(array_unique($normalized));
    }

    private function nullableFilter(mixed $rawValue): ?string
    {
        if (!is_scalar($rawValue)) {
            return null;
        }

        $value = trim((string) $rawValue);

        return $value === '' ? null : $value;
    }
}
