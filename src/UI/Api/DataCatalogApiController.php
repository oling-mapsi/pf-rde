<?php

declare(strict_types=1);

namespace App\UI\Api;

use App\Application\Cartography\DTO\DataCatalogSearchCriteria;
use App\Application\Cartography\Service\DataCatalogService;
use App\Domain\Cartography\Entity\InteractiveMap;
use App\Domain\Cartography\Entity\StaticMap;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/catalog', name: 'api_catalog_')]
final class DataCatalogApiController extends AbstractController
{
    #[Route('/autocomplete', name: 'autocomplete', methods: ['GET'])]
    public function autocomplete(Request $request, DataCatalogService $catalogService): JsonResponse
    {
        $query = trim((string) $request->query->get('q', ''));
        if (mb_strlen($query) < 2) {
            return $this->json(['suggestions' => []]);
        }

        $rawParams = $request->query->all();
        $themeValues = $rawParams['theme'] ?? [];
        $typeValues = $rawParams['type'] ?? [];
        $themes = is_array($themeValues) ? array_values(array_filter(array_map('strval', $themeValues))) : [];
        $types = is_array($typeValues) ? array_values(array_filter(array_map('strval', $typeValues))) : [];
        $types = array_values(array_filter($types, static fn (string $type): bool => in_array($type, ['static', 'interactive'], true)));

        $criteria = new DataCatalogSearchCriteria(
            query: $query,
            themes: $themes,
            types: $types,
            page: 1,
            perPage: 8,
        );
        $catalog = $catalogService->search($criteria);

        $suggestions = array_map(function (array $item): array {
            $entity = $item['entity'];
            $type = $item['type'];

            if ($type === 'interactive' && $entity instanceof InteractiveMap) {
                return [
                    'title' => $entity->getTitle(),
                    'type' => 'interactive',
                    'typeLabel' => 'Carte interactive',
                    'theme' => null,
                    'url' => $this->generateUrl('app_interactive_map_show', ['slug' => $entity->getSlug()]),
                ];
            }

            if ($entity instanceof StaticMap) {
                return [
                    'title' => $entity->getTitle(),
                    'type' => 'static',
                    'typeLabel' => 'Donnée cartothèque',
                    'theme' => $entity->getTheme(),
                    'url' => $this->generateUrl('app_static_map_show', ['slug' => $entity->getSlug()]),
                ];
            }

            return [
                'title' => 'Ressource',
                'type' => 'static',
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
