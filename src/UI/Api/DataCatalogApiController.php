<?php

declare(strict_types=1);

namespace App\UI\Api;

use App\Application\Access\Service\VisibilityScopeResolver;
use App\Application\Cartography\DTO\DataCatalogSearchCriteria;
use App\Application\Cartography\Service\DataCatalogService;
use App\Domain\Cartography\Entity\DataSource;
use App\Domain\Cartography\Entity\InteractiveMap;
use App\Domain\Cartography\Entity\StaticMap;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/catalog', name: 'api_catalog_')]
final class DataCatalogApiController extends AbstractController
{
    public function __construct(private readonly VisibilityScopeResolver $visibilityScopeResolver)
    {
    }

    #[Route('/autocomplete', name: 'autocomplete', methods: ['GET'])]
    public function autocomplete(Request $request, DataCatalogService $catalogService): JsonResponse
    {
        $query = trim((string) $request->query->get('q', ''));
        if (mb_strlen($query) < 2) {
            return $this->json(['suggestions' => []]);
        }

        $baseCriteria = DataCatalogSearchCriteria::fromRequest($request);

        $criteria = new DataCatalogSearchCriteria(
            query: $query,
            themes: $baseCriteria->themes,
            types: $baseCriteria->types,
            categories: $baseCriteria->categories,
            page: 1,
            perPage: 8,
        );
        $catalog = $catalogService->search(
            $criteria,
            $this->visibilityScopeResolver->resolveForUser($this->getUser()),
        );

        $suggestions = array_map(function (array $item): array {
            $entity = $item['entity'];
            $type = $item['type'];

            if ($item['kind'] === 'interactive_map' && $entity instanceof InteractiveMap) {
                return [
                    'title' => $entity->getTitle(),
                    'type' => DataSource::TYPE_CARTOGRAPHY_LINK,
                    'typeLabel' => DataSource::TYPE_LABELS[DataSource::TYPE_CARTOGRAPHY_LINK],
                    'theme' => null,
                    'url' => $this->generateUrl('app_interactive_map_show', ['slug' => $entity->getSlug()]),
                ];
            }

            if ($item['kind'] === 'static_map' && $entity instanceof StaticMap) {
                return [
                    'title' => $entity->getTitle(),
                    'type' => DataSource::TYPE_STATIC_MAP,
                    'typeLabel' => DataSource::TYPE_LABELS[DataSource::TYPE_STATIC_MAP],
                    'theme' => $entity->getTheme(),
                    'url' => $this->generateUrl('app_static_map_show', ['slug' => $entity->getSlug()]),
                ];
            }

            if ($entity instanceof DataSource) {
                $url = $this->generateUrl('app_data_source_show', ['slug' => $entity->getSlug()]);

                if ($entity->getLinkedInteractiveMap() !== null) {
                    $url = $this->generateUrl('app_interactive_map_show', ['slug' => $entity->getLinkedInteractiveMap()->getSlug()]);
                } elseif ($entity->getLinkedStaticMap() !== null) {
                    $url = $this->generateUrl('app_static_map_show', ['slug' => $entity->getLinkedStaticMap()->getSlug()]);
                } elseif ($entity->getSourceUrl() !== null && trim($entity->getSourceUrl()) !== '') {
                    $url = trim($entity->getSourceUrl());
                    if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://') && !str_starts_with($url, '/')) {
                        $url = '/'.$url;
                    }
                } elseif ($entity->getFilePath() !== null && trim($entity->getFilePath()) !== '') {
                    $url = trim($entity->getFilePath());
                    if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://') && !str_starts_with($url, '/')) {
                        $url = '/'.$url;
                    }
                } elseif ($entity->getServiceEndpoint() !== null && $entity->getServiceEndpoint()->getBaseUrl() !== null) {
                    $url = (string) $entity->getServiceEndpoint()->getBaseUrl();
                }

                return [
                    'title' => $entity->getTitle(),
                    'type' => $type,
                    'typeLabel' => DataSource::TYPE_LABELS[$type] ?? 'Source de données',
                    'theme' => $entity->getTheme(),
                    'url' => $url,
                ];
            }

            return [
                'title' => 'Ressource',
                'type' => DataSource::TYPE_DATA_FILE,
                'typeLabel' => 'Ressource',
                'theme' => null,
                'url' => $this->generateUrl('app_data_catalog'),
            ];
        }, $catalog['items']);

        return $this->json([
            'suggestions' => $suggestions,
        ]);
    }
}
