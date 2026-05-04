<?php

declare(strict_types=1);

namespace App\UI\Api;

use App\Application\Cartography\DTO\StaticMapSearchCriteria;
use App\Application\Cartography\Service\StaticMapCatalogService;
use App\Domain\Cartography\Entity\StaticMap;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/static-maps', name: 'api_static_maps_')]
final class StaticMapApiController extends AbstractController
{
    #[Route('', name: 'search', methods: ['GET'])]
    public function search(Request $request, StaticMapCatalogService $catalogService): JsonResponse
    {
        $criteria = StaticMapSearchCriteria::fromRequest($request);
        $catalog = $catalogService->search($criteria);

        return $this->json([
            'items' => array_map($this->normalizeMap(...), $catalog['items']),
            'total' => $catalog['total'],
            'page' => $catalog['page'],
            'perPage' => $catalog['perPage'],
            'totalPages' => $catalog['totalPages'],
            'themes' => $catalog['themes'],
        ]);
    }

    #[Route('/autocomplete', name: 'autocomplete', methods: ['GET'])]
    public function autocomplete(Request $request, StaticMapCatalogService $catalogService): JsonResponse
    {
        $criteria = StaticMapSearchCriteria::fromRequest($request);
        $criteria = new StaticMapSearchCriteria($criteria->query, [], null, 1, 8);
        $catalog = $catalogService->search($criteria);

        $suggestions = array_map(static fn (StaticMap $map): array => [
            'title' => $map->getTitle(),
            'slug' => $map->getSlug(),
            'theme' => $map->getTheme(),
        ], $catalog['items']);

        return $this->json([
            'suggestions' => $suggestions,
        ]);
    }

    private function normalizeMap(StaticMap $map): array
    {
        return [
            'uuid' => $map->getUuid()->toRfc4122(),
            'slug' => $map->getSlug(),
            'title' => $map->getTitle(),
            'summary' => $map->getSummary(),
            'theme' => $map->getTheme(),
            'documentDate' => $map->getDocumentDate()?->format('Y-m-d'),
            'assets' => $map->getAssets()->count(),
            'datasets' => $map->getDatasetResources()->count(),
            'url' => $this->generateUrl('app_static_map_show', ['slug' => $map->getSlug()]),
        ];
    }
}
