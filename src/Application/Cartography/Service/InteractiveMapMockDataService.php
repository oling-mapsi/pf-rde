<?php

declare(strict_types=1);

namespace App\Application\Cartography\Service;

final class InteractiveMapMockDataService
{
    private const DEFAULT_MAP_SLUG = 'visualisation-reseau';

    /**
     * @param list<string> $configuredLayerIds
     *
     * @return array{
     *   map: array{center: array{0: float, 1: float}, zoom: int, minZoom: int, maxZoom: int},
     *   layers: list<array<string, mixed>>,
     *   filters: array{statuses: list<string>, categories: list<string>},
     *   degradedMode: bool,
     *   degradedModeMessage: string
     * }
     */
    public function buildBootstrap(
        string $slug,
        float $defaultCenterLat,
        float $defaultCenterLng,
        int $defaultZoom,
        array $configuredLayerIds = [],
        bool $degradedMode = false,
        ?string $degradedModeMessage = null,
    ): array {
        $dataset = $this->datasetForSlug($slug);
        $layers = $this->filterLayers($dataset['layers'], $configuredLayerIds);
        $filterOptions = $this->buildFilterOptions($dataset['featuresByLayer'], array_column($layers, 'id'));

        return [
            'map' => [
                'center' => [$defaultCenterLng, $defaultCenterLat],
                'zoom' => $defaultZoom,
                'minZoom' => 9,
                'maxZoom' => 17,
            ],
            'layers' => $layers,
            'filters' => $filterOptions,
            'degradedMode' => $degradedMode,
            'degradedModeMessage' => $degradedModeMessage ?? 'Mode dégradé actif: certains services SIG distants ne répondent pas.',
        ];
    }

    /**
     * @return array{type: 'FeatureCollection', features: list<array<string, mixed>>}
     */
    public function getBasemap(string $slug): array
    {
        return [
            'type' => 'FeatureCollection',
            'features' => $this->buildBasemapFeatures($slug),
        ];
    }

    /**
     * @param list<string> $requestedLayerIds
     *
     * @return array{
     *   type: 'FeatureCollection',
     *   features: list<array<string, mixed>>,
     *   total: int,
     *   countsByLayer: array<string, int>
     * }
     */
    public function getFeatures(
        string $slug,
        array $requestedLayerIds = [],
        ?string $status = null,
        ?string $category = null,
        ?string $query = null,
    ): array {
        $dataset = $this->datasetForSlug($slug);
        $layerIds = $this->resolveLayerIds($dataset['layers'], $requestedLayerIds);
        $features = [];
        $countsByLayer = [];

        foreach ($layerIds as $layerId) {
            $countsByLayer[$layerId] = 0;
            $layerFeatures = $dataset['featuresByLayer'][$layerId] ?? [];
            foreach ($layerFeatures as $feature) {
                if (!$this->matchesFilters($feature, $status, $category, $query)) {
                    continue;
                }

                $features[] = $feature;
                ++$countsByLayer[$layerId];
            }
        }

        return [
            'type' => 'FeatureCollection',
            'features' => $features,
            'total' => count($features),
            'countsByLayer' => $countsByLayer,
        ];
    }

    /**
     * @param list<string> $requestedLayerIds
     *
     * @return array{items: list<array<string, mixed>>}
     */
    public function getLegend(
        string $slug,
        array $requestedLayerIds = [],
        ?string $status = null,
        ?string $category = null,
        ?string $query = null,
    ): array {
        $dataset = $this->datasetForSlug($slug);
        $layerIds = $this->resolveLayerIds($dataset['layers'], $requestedLayerIds);
        $layersById = [];
        foreach ($dataset['layers'] as $layer) {
            $layersById[$layer['id']] = $layer;
        }

        $items = [];
        foreach ($layerIds as $layerId) {
            $layer = $layersById[$layerId] ?? null;
            if (!is_array($layer)) {
                continue;
            }

            $layerFeatures = $dataset['featuresByLayer'][$layerId] ?? [];
            $matchingFeatures = array_values(array_filter(
                $layerFeatures,
                fn (array $feature): bool => $this->matchesFilters($feature, $status, $category, $query)
            ));

            $legendItems = $layer['legendItems'] ?? [];
            foreach ($legendItems as $legendItem) {
                $property = (string) ($legendItem['property'] ?? '');
                $value = (string) ($legendItem['value'] ?? '');
                if ($property === '' || $value === '') {
                    continue;
                }

                $count = 0;
                foreach ($matchingFeatures as $feature) {
                    $featureValue = strtolower((string) ($feature['properties'][$property] ?? ''));
                    if ($featureValue === strtolower($value)) {
                        ++$count;
                    }
                }

                $items[] = [
                    'layerId' => $layerId,
                    'layerLabel' => (string) ($layer['label'] ?? $layerId),
                    'label' => (string) ($legendItem['label'] ?? $value),
                    'color' => (string) ($legendItem['color'] ?? '#0E5AA7'),
                    'symbol' => (string) ($legendItem['symbol'] ?? 'line'),
                    'count' => $count,
                ];
            }
        }

        return ['items' => $items];
    }

    /**
     * @param list<string> $requestedLayerIds
     *
     * @return array{
     *   distanceMeters: int,
     *   coordinates: array{lng: float, lat: float},
     *   feature: array<string, mixed>
     * }|null
     */
    public function getFeatureInfo(
        string $slug,
        float $lng,
        float $lat,
        array $requestedLayerIds = [],
    ): ?array {
        $dataset = $this->datasetForSlug($slug);
        $layerIds = $this->resolveLayerIds($dataset['layers'], $requestedLayerIds);

        $bestFeature = null;
        $bestDistance = null;
        $bestCoordinate = null;

        foreach ($layerIds as $layerId) {
            $layerFeatures = $dataset['featuresByLayer'][$layerId] ?? [];
            foreach ($layerFeatures as $feature) {
                $candidateCoordinate = $this->extractRepresentativeCoordinate($feature['geometry'] ?? null);
                if ($candidateCoordinate === null) {
                    continue;
                }

                $distance = $this->haversineDistanceMeters($lat, $lng, $candidateCoordinate[1], $candidateCoordinate[0]);
                if ($bestDistance === null || $distance < $bestDistance) {
                    $bestDistance = $distance;
                    $bestFeature = $feature;
                    $bestCoordinate = $candidateCoordinate;
                }
            }
        }

        if ($bestFeature === null || $bestDistance === null || $bestCoordinate === null) {
            return null;
        }

        if ($bestDistance > 5500) {
            return null;
        }

        return [
            'distanceMeters' => (int) round($bestDistance),
            'coordinates' => [
                'lng' => (float) $bestCoordinate[0],
                'lat' => (float) $bestCoordinate[1],
            ],
            'feature' => [
                'id' => (string) ($bestFeature['properties']['feature_id'] ?? ''),
                'layerId' => (string) ($bestFeature['properties']['layer_id'] ?? ''),
                'title' => (string) ($bestFeature['properties']['title'] ?? 'Élément cartographique'),
                'status' => (string) ($bestFeature['properties']['status'] ?? 'n/a'),
                'category' => (string) ($bestFeature['properties']['category'] ?? 'n/a'),
                'commune' => (string) ($bestFeature['properties']['commune'] ?? 'n/a'),
                'description' => (string) ($bestFeature['properties']['description'] ?? ''),
                'metadata' => array_filter([
                    'vitesse_kmh' => $bestFeature['properties']['speed_limit'] ?? null,
                    'longueur_km' => $bestFeature['properties']['length_km'] ?? null,
                    'debut' => $bestFeature['properties']['start_date'] ?? null,
                    'fin' => $bestFeature['properties']['end_date'] ?? null,
                    'criticite' => $bestFeature['properties']['severity'] ?? null,
                ], static fn (mixed $value): bool => $value !== null && $value !== ''),
            ],
        ];
    }

    /**
     * @param array<string, mixed>|null $geometry
     *
     * @return array{0: float, 1: float}|null
     */
    private function extractRepresentativeCoordinate(?array $geometry): ?array
    {
        if ($geometry === null) {
            return null;
        }

        $type = (string) ($geometry['type'] ?? '');
        $coordinates = $geometry['coordinates'] ?? null;
        if (!is_array($coordinates)) {
            return null;
        }

        if ($type === 'Point') {
            if (isset($coordinates[0], $coordinates[1])) {
                return [(float) $coordinates[0], (float) $coordinates[1]];
            }

            return null;
        }

        if ($type === 'LineString') {
            $midpointIndex = (int) floor((count($coordinates) - 1) / 2);
            $midpoint = $coordinates[$midpointIndex] ?? null;
            if (is_array($midpoint) && isset($midpoint[0], $midpoint[1])) {
                return [(float) $midpoint[0], (float) $midpoint[1]];
            }

            return null;
        }

        if ($type === 'Polygon') {
            $firstRing = $coordinates[0] ?? null;
            if (!is_array($firstRing) || $firstRing === []) {
                return null;
            }

            $sumLng = 0.0;
            $sumLat = 0.0;
            $count = 0;
            foreach ($firstRing as $vertex) {
                if (!is_array($vertex) || !isset($vertex[0], $vertex[1])) {
                    continue;
                }
                $sumLng += (float) $vertex[0];
                $sumLat += (float) $vertex[1];
                ++$count;
            }

            if ($count === 0) {
                return null;
            }

            return [$sumLng / $count, $sumLat / $count];
        }

        return null;
    }

    private function haversineDistanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000.0;

        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLng = deg2rad($lng2 - $lng1);

        $a = sin($deltaLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($deltaLng / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * @param list<array<string, mixed>> $allLayers
     * @param list<string> $configuredLayerIds
     *
     * @return list<array<string, mixed>>
     */
    private function filterLayers(array $allLayers, array $configuredLayerIds): array
    {
        if ($configuredLayerIds === []) {
            return array_values($allLayers);
        }

        $configuredLookup = array_flip($configuredLayerIds);
        $layers = array_values(array_filter(
            $allLayers,
            static fn (array $layer): bool => isset($configuredLookup[(string) ($layer['id'] ?? '')])
        ));

        return $layers === [] ? array_values($allLayers) : $layers;
    }

    /**
     * @param array<string, list<array<string, mixed>>> $featuresByLayer
     * @param list<string> $layerIds
     *
     * @return array{statuses: list<string>, categories: list<string>}
     */
    private function buildFilterOptions(array $featuresByLayer, array $layerIds): array
    {
        $statuses = [];
        $categories = [];

        foreach ($layerIds as $layerId) {
            $features = $featuresByLayer[$layerId] ?? [];
            foreach ($features as $feature) {
                $status = strtolower(trim((string) ($feature['properties']['status'] ?? '')));
                if ($status !== '') {
                    $statuses[$status] = true;
                }

                $category = strtolower(trim((string) ($feature['properties']['category'] ?? '')));
                if ($category !== '') {
                    $categories[$category] = true;
                }
            }
        }

        $statusValues = array_keys($statuses);
        $categoryValues = array_keys($categories);
        sort($statusValues);
        sort($categoryValues);

        return [
            'statuses' => array_values($statusValues),
            'categories' => array_values($categoryValues),
        ];
    }

    /**
     * @param list<array<string, mixed>> $allLayers
     * @param list<string> $requestedLayerIds
     *
     * @return list<string>
     */
    private function resolveLayerIds(array $allLayers, array $requestedLayerIds): array
    {
        $availableLayerIds = array_map(static fn (array $layer): string => (string) ($layer['id'] ?? ''), $allLayers);
        $availableLookup = array_flip($availableLayerIds);

        if ($requestedLayerIds === []) {
            return array_values(array_filter($availableLayerIds, static fn (string $value): bool => $value !== ''));
        }

        $resolved = [];
        foreach ($requestedLayerIds as $layerId) {
            $normalizedId = trim(strtolower($layerId));
            if ($normalizedId === '' || !isset($availableLookup[$normalizedId])) {
                continue;
            }

            $resolved[] = $normalizedId;
        }

        return $resolved === []
            ? array_values(array_filter($availableLayerIds, static fn (string $value): bool => $value !== ''))
            : array_values(array_unique($resolved));
    }

    /**
     * @param array<string, mixed> $feature
     */
    private function matchesFilters(array $feature, ?string $status, ?string $category, ?string $query): bool
    {
        $properties = $feature['properties'] ?? [];
        if (!is_array($properties)) {
            return false;
        }

        if ($status !== null && $status !== '') {
            $featureStatus = strtolower((string) ($properties['status'] ?? ''));
            if ($featureStatus !== strtolower($status)) {
                return false;
            }
        }

        if ($category !== null && $category !== '') {
            $featureCategory = strtolower((string) ($properties['category'] ?? ''));
            if ($featureCategory !== strtolower($category)) {
                return false;
            }
        }

        if ($query !== null && trim($query) !== '') {
            $needle = strtolower(trim($query));
            $haystacks = [
                (string) ($properties['title'] ?? ''),
                (string) ($properties['description'] ?? ''),
                (string) ($properties['commune'] ?? ''),
                (string) ($properties['category'] ?? ''),
                (string) ($properties['status'] ?? ''),
            ];

            $found = false;
            foreach ($haystacks as $haystack) {
                if (str_contains(strtolower($haystack), $needle)) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{
     *   layers: list<array<string, mixed>>,
     *   featuresByLayer: array<string, list<array<string, mixed>>>
     * }
     */
    private function datasetForSlug(string $slug): array
    {
        $roadNetworkFeatures = $this->buildRoadNetworkFeatures();
        $worksiteFeatures = $this->buildWorksiteFeatures($roadNetworkFeatures);

        $dataset = [
            'layers' => [
                [
                    'id' => 'reseau_principal',
                    'label' => 'Réseau routier principal',
                    'geometryType' => 'line',
                    'stylePreset' => 'roadNetwork',
                    'visibleByDefault' => true,
                    'legendItems' => [
                        [
                            'property' => 'category',
                            'value' => 'nationale',
                            'label' => 'Routes nationales',
                            'color' => '#0E5AA7',
                            'symbol' => 'line',
                        ],
                        [
                            'property' => 'category',
                            'value' => 'departementale',
                            'label' => 'Routes départementales',
                            'color' => '#2FA7D9',
                            'symbol' => 'line',
                        ],
                        [
                            'property' => 'category',
                            'value' => 'communale',
                            'label' => 'Routes communales',
                            'color' => '#7AA63A',
                            'symbol' => 'line',
                        ],
                    ],
                ],
                [
                    'id' => 'travaux_planifies',
                    'label' => 'Travaux, incidents et points de vigilance',
                    'geometryType' => 'point',
                    'stylePreset' => 'worksites',
                    'visibleByDefault' => true,
                    'legendItems' => [
                        [
                            'property' => 'status',
                            'value' => 'planned',
                            'label' => 'Planifié',
                            'color' => '#F3C623',
                            'symbol' => 'circle',
                        ],
                        [
                            'property' => 'status',
                            'value' => 'in_progress',
                            'label' => 'En cours',
                            'color' => '#E57A22',
                            'symbol' => 'circle',
                        ],
                        [
                            'property' => 'status',
                            'value' => 'done',
                            'label' => 'Clôturé',
                            'color' => '#7AA63A',
                            'symbol' => 'circle',
                        ],
                    ],
                ],
            ],
            'featuresByLayer' => [
                'reseau_principal' => $roadNetworkFeatures,
                'travaux_planifies' => $worksiteFeatures,
            ],
        ];

        if ($slug === self::DEFAULT_MAP_SLUG) {
            return $dataset;
        }

        return $dataset;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildRoadNetworkFeatures(): array
    {
        $features = [
            $this->buildRoadNetworkFeature(
                id: 'road_rn1_001',
                title: 'RN1 - Axe Les Abymes / Pointe-à-Pitre',
                status: 'open',
                category: 'nationale',
                commune: 'Les Abymes',
                speedLimit: 70,
                lengthKm: 8.4,
                description: 'Section principale de desserte urbaine avec trafic dense.',
                coordinates: [[-61.531, 16.24], [-61.514, 16.246], [-61.491, 16.255], [-61.468, 16.266]]
            ),
            $this->buildRoadNetworkFeature(
                id: 'road_rd26_002',
                title: 'RD26 - Baie-Mahault / Lamentin',
                status: 'open',
                category: 'departementale',
                commune: 'Baie-Mahault',
                speedLimit: 80,
                lengthKm: 12.1,
                description: 'Liaison logistique pour la zone de Jarry.',
                coordinates: [[-61.635, 16.244], [-61.618, 16.251], [-61.602, 16.257], [-61.579, 16.263]]
            ),
            $this->buildRoadNetworkFeature(
                id: 'road_rc14_003',
                title: 'RC14 - Basse-Terre / Saint-Claude',
                status: 'open',
                category: 'communale',
                commune: 'Saint-Claude',
                speedLimit: 50,
                lengthKm: 6.2,
                description: 'Voie de desserte de pente, vigilance en saison humide.',
                coordinates: [[-61.745, 16.038], [-61.738, 16.048], [-61.725, 16.057], [-61.709, 16.066]]
            ),
            $this->buildRoadNetworkFeature(
                id: 'road_rn5_004',
                title: 'RN5 - Le Gosier / Sainte-Anne',
                status: 'open',
                category: 'nationale',
                commune: 'Le Gosier',
                speedLimit: 70,
                lengthKm: 14.9,
                description: 'Axe côtier avec forte fréquentation touristique.',
                coordinates: [[-61.422, 16.312], [-61.399, 16.317], [-61.372, 16.322], [-61.351, 16.329]]
            ),
            $this->buildRoadNetworkFeature(
                id: 'road_rd118_005',
                title: 'RD118 - Liaison Le Gosier / Grande-Terre',
                status: 'open',
                category: 'departementale',
                commune: 'Le Gosier',
                speedLimit: 60,
                lengthKm: 7.5,
                description: 'Section avec giratoires successifs et flux pendulaire.',
                coordinates: [[-61.455, 16.289], [-61.441, 16.301], [-61.427, 16.309], [-61.413, 16.319]]
            ),
            $this->buildRoadNetworkFeature(
                id: 'road_rn2_006',
                title: 'RN2 - Vieux-Habitants / Basse-Terre',
                status: 'open',
                category: 'nationale',
                commune: 'Vieux-Habitants',
                speedLimit: 70,
                lengthKm: 18.3,
                description: 'Dorsale côtière ouest de Basse-Terre.',
                coordinates: [[-61.772, 16.066], [-61.758, 16.052], [-61.744, 16.038], [-61.732, 16.015], [-61.719, 15.998]]
            ),
            $this->buildRoadNetworkFeature(
                id: 'road_rn3_007',
                title: 'RN3 - Capesterre-Belle-Eau / Petit-Bourg',
                status: 'open',
                category: 'nationale',
                commune: 'Capesterre-Belle-Eau',
                speedLimit: 80,
                lengthKm: 20.6,
                description: 'Axe structurant est avec trafic de transit régional.',
                coordinates: [[-61.585, 16.055], [-61.572, 16.093], [-61.559, 16.131], [-61.541, 16.177], [-61.521, 16.224]]
            ),
            $this->buildRoadNetworkFeature(
                id: 'road_rd123_008',
                title: 'RD123 - Petit-Canal / Port-Louis',
                status: 'open',
                category: 'departementale',
                commune: 'Petit-Canal',
                speedLimit: 60,
                lengthKm: 11.8,
                description: 'Axe de desserte nord Grande-Terre.',
                coordinates: [[-61.488, 16.383], [-61.46, 16.38], [-61.431, 16.373], [-61.402, 16.367]]
            ),
            $this->buildRoadNetworkFeature(
                id: 'road_rd105_009',
                title: 'RD105 - Moule / Saint-François',
                status: 'open',
                category: 'departementale',
                commune: 'Le Moule',
                speedLimit: 70,
                lengthKm: 16.2,
                description: 'Axe touristique et agricole en façade atlantique.',
                coordinates: [[-61.36, 16.333], [-61.333, 16.321], [-61.304, 16.307], [-61.272, 16.294]]
            ),
            $this->buildRoadNetworkFeature(
                id: 'road_rn9_010',
                title: 'RN9 - Grand-Bourg / Capesterre-de-Marie-Galante',
                status: 'open',
                category: 'nationale',
                commune: 'Grand-Bourg',
                speedLimit: 60,
                lengthKm: 17.4,
                description: 'Axe principal de Marie-Galante entre les deux pôles.',
                coordinates: [[-61.301, 15.874], [-61.281, 15.891], [-61.263, 15.903], [-61.243, 15.921], [-61.228, 15.94]]
            ),
            $this->buildRoadNetworkFeature(
                id: 'road_rd201_011',
                title: 'RD201 - La Désirade est-ouest',
                status: 'open',
                category: 'departementale',
                commune: 'La Désirade',
                speedLimit: 50,
                lengthKm: 8.9,
                description: 'Liaison principale de la Désirade jusqu’au phare.',
                coordinates: [[-61.094, 16.321], [-61.074, 16.321], [-61.053, 16.322], [-61.029, 16.321]]
            ),
            $this->buildRoadNetworkFeature(
                id: 'road_rd301_012',
                title: 'RD301 - Terre-de-Haut boucle littorale',
                status: 'open',
                category: 'communale',
                commune: 'Terre-de-Haut',
                speedLimit: 40,
                lengthKm: 4.7,
                description: 'Boucle de desserte locale des Saintes.',
                coordinates: [[-61.571, 15.867], [-61.559, 15.872], [-61.548, 15.876], [-61.539, 15.882]]
            ),
        ];

        $corridorDefinitions = [
            ['prefix' => 'bt_coast', 'title' => 'Basse-Terre côte ouest', 'start' => [-61.776, 16.146], 'end' => [-61.724, 15.994], 'segments' => 12, 'communes' => ['Deshaies', 'Pointe-Noire', 'Bouillante', 'Vieux-Habitants', 'Basse-Terre'], 'category' => 'departementale'],
            ['prefix' => 'bt_north', 'title' => 'Basse-Terre nord', 'start' => [-61.742, 16.235], 'end' => [-61.626, 16.217], 'segments' => 9, 'communes' => ['Lamentin', 'Sainte-Rose', 'Petit-Bourg'], 'category' => 'nationale'],
            ['prefix' => 'bt_east', 'title' => 'Basse-Terre est', 'start' => [-61.624, 16.221], 'end' => [-61.585, 16.054], 'segments' => 10, 'communes' => ['Petit-Bourg', 'Goyave', 'Capesterre-Belle-Eau'], 'category' => 'departementale'],
            ['prefix' => 'gt_north', 'title' => 'Grande-Terre nord', 'start' => [-61.523, 16.381], 'end' => [-61.34, 16.354], 'segments' => 12, 'communes' => ['Anse-Bertrand', 'Port-Louis', 'Petit-Canal', 'Le Moule'], 'category' => 'departementale'],
            ['prefix' => 'gt_center', 'title' => 'Grande-Terre central', 'start' => [-61.548, 16.269], 'end' => [-61.287, 16.274], 'segments' => 13, 'communes' => ['Les Abymes', 'Morne-à-l-Eau', 'Le Moule', 'Saint-François'], 'category' => 'nationale'],
            ['prefix' => 'gt_south', 'title' => 'Grande-Terre sud', 'start' => [-61.493, 16.209], 'end' => [-61.271, 16.225], 'segments' => 11, 'communes' => ['Le Gosier', 'Sainte-Anne', 'Saint-François'], 'category' => 'departementale'],
            ['prefix' => 'gt_cross_1', 'title' => 'Grande-Terre transversale A', 'start' => [-61.53, 16.332], 'end' => [-61.448, 16.214], 'segments' => 7, 'communes' => ['Morne-à-l-Eau', 'Les Abymes', 'Le Gosier'], 'category' => 'communale'],
            ['prefix' => 'gt_cross_2', 'title' => 'Grande-Terre transversale B', 'start' => [-61.41, 16.33], 'end' => [-61.351, 16.223], 'segments' => 7, 'communes' => ['Le Moule', 'Sainte-Anne'], 'category' => 'communale'],
            ['prefix' => 'mg_main', 'title' => 'Marie-Galante principal', 'start' => [-61.309, 15.876], 'end' => [-61.216, 15.943], 'segments' => 7, 'communes' => ['Grand-Bourg', 'Capesterre-de-Marie-Galante'], 'category' => 'nationale'],
            ['prefix' => 'mg_cross', 'title' => 'Marie-Galante transversal', 'start' => [-61.286, 15.964], 'end' => [-61.233, 15.87], 'segments' => 5, 'communes' => ['Saint-Louis', 'Grand-Bourg'], 'category' => 'departementale'],
            ['prefix' => 'de_main', 'title' => 'La Désirade principal', 'start' => [-61.101, 16.319], 'end' => [-61.03, 16.321], 'segments' => 5, 'communes' => ['La Désirade'], 'category' => 'departementale'],
            ['prefix' => 'ls_main', 'title' => 'Les Saintes principal', 'start' => [-61.587, 15.864], 'end' => [-61.539, 15.88], 'segments' => 5, 'communes' => ['Terre-de-Haut', 'Terre-de-Bas'], 'category' => 'communale'],
        ];

        foreach ($corridorDefinitions as $definition) {
            $features = array_merge(
                $features,
                $this->buildSegmentedCorridorFeatures(
                    prefix: (string) $definition['prefix'],
                    titlePrefix: (string) $definition['title'],
                    start: $definition['start'],
                    end: $definition['end'],
                    segments: (int) $definition['segments'],
                    communes: $definition['communes'],
                    category: (string) $definition['category']
                )
            );
        }

        return $features;
    }

    /**
     * @param list<array<string, mixed>> $roadNetworkFeatures
     *
     * @return list<array<string, mixed>>
     */
    private function buildWorksiteFeatures(array $roadNetworkFeatures): array
    {
        $features = [
            $this->buildWorksiteFeature(
                id: 'event_wrk_001',
                title: 'Réfection chaussée RN1 - tranche 2',
                status: 'in_progress',
                category: 'chantier',
                commune: 'Les Abymes',
                startDate: '2026-04-10',
                endDate: '2026-05-12',
                severity: 'modérée',
                description: 'Alternat ponctuel et réduction de vitesse à 50 km/h.',
                coordinates: [-61.512, 16.249]
            ),
            $this->buildWorksiteFeature(
                id: 'event_wrk_002',
                title: 'Entretien ouvrages hydrauliques RD26',
                status: 'planned',
                category: 'maintenance',
                commune: 'Baie-Mahault',
                startDate: '2026-05-04',
                endDate: '2026-05-06',
                severity: 'faible',
                description: 'Intervention nocturne sans coupure complète de circulation.',
                coordinates: [-61.598, 16.258]
            ),
            $this->buildWorksiteFeature(
                id: 'event_wrk_003',
                title: 'Purge talus RC14',
                status: 'in_progress',
                category: 'securisation',
                commune: 'Saint-Claude',
                startDate: '2026-04-21',
                endDate: '2026-04-29',
                severity: 'élevée',
                description: 'Circulation alternée avec plage de fermeture ponctuelle.',
                coordinates: [-61.707, 16.063]
            ),
            $this->buildWorksiteFeature(
                id: 'event_inc_004',
                title: 'Incident circulation RN5',
                status: 'done',
                category: 'incident',
                commune: 'Sainte-Anne',
                startDate: '2026-04-14',
                endDate: '2026-04-14',
                severity: 'modérée',
                description: 'Accident matériel résolu, retour à la normale.',
                coordinates: [-61.395, 16.319]
            ),
            $this->buildWorksiteFeature(
                id: 'event_wrk_005',
                title: 'Renouvellement signalisation RD118',
                status: 'planned',
                category: 'maintenance',
                commune: 'Le Gosier',
                startDate: '2026-05-18',
                endDate: '2026-05-22',
                severity: 'faible',
                description: 'Pose de panneaux et reprise marquage horizontal.',
                coordinates: [-61.444, 16.304]
            ),
            $this->buildWorksiteFeature(
                id: 'event_wrk_006',
                title: 'Inspection pont urbain',
                status: 'done',
                category: 'inspection',
                commune: 'Pointe-à-Pitre',
                startDate: '2026-04-08',
                endDate: '2026-04-08',
                severity: 'faible',
                description: 'Inspection périodique finalisée, aucune restriction en cours.',
                coordinates: [-61.483, 16.271]
            ),
        ];

        $statusCycle = ['planned', 'in_progress', 'done', 'in_progress', 'planned'];
        $categoryCycle = ['chantier', 'maintenance', 'inspection', 'incident', 'securisation'];
        $severityCycle = ['faible', 'modérée', 'élevée', 'modérée'];
        $titleCycle = ['Reprise enrobé', 'Maintenance drainage', 'Inspection ouvrage', 'Signalisation temporaire', 'Sécurisation talus'];

        $maxGenerated = min(58, count($roadNetworkFeatures));
        for ($index = 0; $index < $maxGenerated; ++$index) {
            $road = $roadNetworkFeatures[$index];
            $coordinates = $this->extractRepresentativeCoordinate($road['geometry'] ?? null);
            if ($coordinates === null) {
                continue;
            }

            $status = $statusCycle[$index % count($statusCycle)];
            $category = $categoryCycle[$index % count($categoryCycle)];
            $severity = $severityCycle[$index % count($severityCycle)];
            $titlePrefix = $titleCycle[$index % count($titleCycle)];
            $commune = (string) ($road['properties']['commune'] ?? 'Guadeloupe');
            $roadTitle = (string) ($road['properties']['title'] ?? 'axe routier');

            $month = 4 + intdiv($index, 24);
            $startDay = (($index * 3) % 26) + 2;
            $duration = 2 + ($index % 6);
            $endDay = min($startDay + $duration, 28);
            $startDate = sprintf('2026-%02d-%02d', min($month, 8), $startDay);
            $endDate = sprintf('2026-%02d-%02d', min($month, 8), $endDay);

            $features[] = $this->buildWorksiteFeature(
                id: sprintf('event_auto_%03d', $index + 1),
                title: sprintf('%s - secteur %s', $titlePrefix, $commune),
                status: $status,
                category: $category,
                commune: $commune,
                startDate: $startDate,
                endDate: $endDate,
                severity: $severity,
                description: sprintf('Opération simulée sur %s.', $roadTitle),
                coordinates: [
                    $this->roundCoordinate($coordinates[0] + (($index % 3) - 1) * 0.0024),
                    $this->roundCoordinate($coordinates[1] + (($index % 4) - 1.5) * 0.0018),
                ]
            );
        }

        return $features;
    }

    /**
     * @param list<array{0: float, 1: float}> $coordinates
     *
     * @return array<string, mixed>
     */
    private function buildRoadNetworkFeature(
        string $id,
        string $title,
        string $status,
        string $category,
        string $commune,
        int $speedLimit,
        float $lengthKm,
        string $description,
        array $coordinates,
    ): array {
        return [
            'type' => 'Feature',
            'geometry' => [
                'type' => 'LineString',
                'coordinates' => $coordinates,
            ],
            'properties' => [
                'feature_id' => $id,
                'layer_id' => 'reseau_principal',
                'title' => $title,
                'status' => $status,
                'category' => $category,
                'commune' => $commune,
                'speed_limit' => $speedLimit,
                'length_km' => $lengthKm,
                'description' => $description,
            ],
        ];
    }

    /**
     * @param array{0: float, 1: float} $coordinates
     *
     * @return array<string, mixed>
     */
    private function buildWorksiteFeature(
        string $id,
        string $title,
        string $status,
        string $category,
        string $commune,
        string $startDate,
        string $endDate,
        string $severity,
        string $description,
        array $coordinates,
    ): array {
        return [
            'type' => 'Feature',
            'geometry' => ['type' => 'Point', 'coordinates' => $coordinates],
            'properties' => [
                'feature_id' => $id,
                'layer_id' => 'travaux_planifies',
                'title' => $title,
                'status' => $status,
                'category' => $category,
                'commune' => $commune,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'severity' => $severity,
                'description' => $description,
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildBasemapFeatures(string $slug): array
    {
        $landFeatures = [
            $this->buildBasemapPolygon(
                island: 'Basse-Terre',
                coordinates: [[
                    [-61.805, 15.965], [-61.778, 16.013], [-61.764, 16.058], [-61.754, 16.106], [-61.745, 16.148],
                    [-61.724, 16.206], [-61.69, 16.255], [-61.643, 16.276], [-61.603, 16.228], [-61.603, 16.158],
                    [-61.618, 16.096], [-61.636, 16.044], [-61.676, 15.998], [-61.723, 15.971], [-61.771, 15.948], [-61.805, 15.965],
                ]]
            ),
            $this->buildBasemapPolygon(
                island: 'Grande-Terre',
                coordinates: [[
                    [-61.633, 16.176], [-61.593, 16.225], [-61.551, 16.258], [-61.503, 16.29], [-61.447, 16.322],
                    [-61.391, 16.343], [-61.332, 16.343], [-61.283, 16.323], [-61.241, 16.285], [-61.228, 16.225],
                    [-61.268, 16.178], [-61.33, 16.157], [-61.391, 16.136], [-61.458, 16.126], [-61.528, 16.126],
                    [-61.592, 16.139], [-61.633, 16.176],
                ]]
            ),
            $this->buildBasemapPolygon(
                island: 'Marie-Galante',
                coordinates: [[
                    [-61.341, 15.844], [-61.316, 15.868], [-61.307, 15.9], [-61.302, 15.934], [-61.275, 15.965],
                    [-61.239, 15.975], [-61.212, 15.952], [-61.198, 15.918], [-61.205, 15.884], [-61.228, 15.858],
                    [-61.262, 15.837], [-61.307, 15.833], [-61.341, 15.844],
                ]]
            ),
            $this->buildBasemapPolygon(
                island: 'La Désirade',
                coordinates: [[
                    [-61.113, 16.314], [-61.097, 16.322], [-61.078, 16.327], [-61.055, 16.327],
                    [-61.036, 16.324], [-61.02, 16.317], [-61.039, 16.313], [-61.059, 16.312], [-61.084, 16.311], [-61.103, 16.311], [-61.113, 16.314],
                ]]
            ),
            $this->buildBasemapPolygon(
                island: 'Terre-de-Haut',
                coordinates: [[
                    [-61.592, 15.86], [-61.582, 15.867], [-61.57, 15.872], [-61.556, 15.878], [-61.542, 15.881],
                    [-61.534, 15.878], [-61.54, 15.868], [-61.553, 15.86], [-61.569, 15.856], [-61.583, 15.856], [-61.592, 15.86],
                ]]
            ),
            $this->buildBasemapPolygon(
                island: 'Terre-de-Bas',
                coordinates: [[
                    [-61.655, 15.834], [-61.644, 15.838], [-61.632, 15.842], [-61.62, 15.842], [-61.611, 15.838],
                    [-61.615, 15.831], [-61.626, 15.827], [-61.639, 15.826], [-61.65, 15.828], [-61.655, 15.834],
                ]]
            ),
        ];

        $roadFeatures = [];
        foreach ($this->buildRoadNetworkFeatures() as $feature) {
            $category = (string) ($feature['properties']['category'] ?? 'communale');
            $roadClass = match ($category) {
                'nationale' => 'trunk',
                'departementale' => 'primary',
                default => 'secondary',
            };
            $roadFeatures[] = [
                'type' => 'Feature',
                'geometry' => $feature['geometry'],
                'properties' => [
                    'basemap_type' => 'road',
                    'road_class' => $roadClass,
                    'name' => (string) ($feature['properties']['title'] ?? 'Axe routier'),
                ],
            ];
        }

        $places = [
            ['name' => 'Basse-Terre', 'coordinates' => [-61.732, 15.999]],
            ['name' => 'Pointe-à-Pitre', 'coordinates' => [-61.535, 16.243]],
            ['name' => 'Les Abymes', 'coordinates' => [-61.51, 16.27]],
            ['name' => 'Baie-Mahault', 'coordinates' => [-61.588, 16.262]],
            ['name' => 'Le Lamentin', 'coordinates' => [-61.631, 16.272]],
            ['name' => 'Le Gosier', 'coordinates' => [-61.48, 16.207]],
            ['name' => 'Sainte-Anne', 'coordinates' => [-61.381, 16.226]],
            ['name' => 'Saint-François', 'coordinates' => [-61.272, 16.252]],
            ['name' => 'Le Moule', 'coordinates' => [-61.348, 16.332]],
            ['name' => 'Petit-Bourg', 'coordinates' => [-61.59, 16.19]],
            ['name' => 'Grand-Bourg', 'coordinates' => [-61.272, 15.883]],
            ['name' => 'Capesterre-de-Marie-Galante', 'coordinates' => [-61.226, 15.92]],
            ['name' => 'La Désirade', 'coordinates' => [-61.06, 16.322]],
            ['name' => 'Terre-de-Haut', 'coordinates' => [-61.58, 15.86]],
        ];
        $placeFeatures = array_map(
            static fn (array $place): array => [
                'type' => 'Feature',
                'geometry' => ['type' => 'Point', 'coordinates' => $place['coordinates']],
                'properties' => [
                    'basemap_type' => 'place',
                    'place_class' => 'city',
                    'name' => $place['name'],
                ],
            ],
            $places
        );

        $features = array_merge($landFeatures, $roadFeatures, $placeFeatures);
        if ($slug !== self::DEFAULT_MAP_SLUG) {
            return $features;
        }

        return $features;
    }

    /**
     * @param list<list<array{0: float, 1: float}>> $coordinates
     *
     * @return array<string, mixed>
     */
    private function buildBasemapPolygon(string $island, array $coordinates): array
    {
        return [
            'type' => 'Feature',
            'properties' => [
                'basemap_type' => 'land',
                'island' => $island,
            ],
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => $coordinates,
            ],
        ];
    }

    /**
     * @param array{0: float, 1: float} $start
     * @param array{0: float, 1: float} $end
     * @param list<string> $communes
     *
     * @return list<array<string, mixed>>
     */
    private function buildSegmentedCorridorFeatures(
        string $prefix,
        string $titlePrefix,
        array $start,
        array $end,
        int $segments,
        array $communes,
        string $category,
    ): array {
        $features = [];
        $safeSegments = max(2, $segments);
        $safeCommunes = $communes === [] ? ['Guadeloupe'] : $communes;
        $speedLimit = match ($category) {
            'nationale' => 80,
            'departementale' => 70,
            default => 50,
        };
        $description = sprintf('Simulation corridor %s sur le réseau %s.', $titlePrefix, $category);

        for ($index = 0; $index < $safeSegments; ++$index) {
            $t0 = $index / $safeSegments;
            $t1 = ($index + 1) / $safeSegments;

            $startLng = $this->interpolateCoordinate((float) $start[0], (float) $end[0], $t0) + (sin(($index + 1) * 0.9) * 0.0017);
            $startLat = $this->interpolateCoordinate((float) $start[1], (float) $end[1], $t0) + (cos(($index + 1) * 0.8) * 0.0014);
            $endLng = $this->interpolateCoordinate((float) $start[0], (float) $end[0], $t1) + (sin(($index + 2) * 0.85) * 0.0017);
            $endLat = $this->interpolateCoordinate((float) $start[1], (float) $end[1], $t1) + (cos(($index + 2) * 0.83) * 0.0014);
            $midLng = ($startLng + $endLng) / 2 + (sin(($index + 1) * 1.13) * 0.0009);
            $midLat = ($startLat + $endLat) / 2 + (cos(($index + 1) * 1.07) * 0.0008);

            $coordinates = [
                [$this->roundCoordinate($startLng), $this->roundCoordinate($startLat)],
                [$this->roundCoordinate($midLng), $this->roundCoordinate($midLat)],
                [$this->roundCoordinate($endLng), $this->roundCoordinate($endLat)],
            ];
            $distanceMeters = $this->haversineDistanceMeters(
                $coordinates[0][1],
                $coordinates[0][0],
                $coordinates[2][1],
                $coordinates[2][0]
            );
            $lengthKm = round(max(0.8, $distanceMeters / 1000), 1);

            $features[] = $this->buildRoadNetworkFeature(
                id: sprintf('road_%s_%03d', $prefix, $index + 1),
                title: sprintf('%s - troncon %02d', $titlePrefix, $index + 1),
                status: $index % 9 === 0 ? 'restricted' : 'open',
                category: $category,
                commune: (string) $safeCommunes[$index % count($safeCommunes)],
                speedLimit: $speedLimit,
                lengthKm: $lengthKm,
                description: $description,
                coordinates: $coordinates
            );
        }

        return $features;
    }

    private function interpolateCoordinate(float $start, float $end, float $ratio): float
    {
        return $start + (($end - $start) * $ratio);
    }

    private function roundCoordinate(float $value): float
    {
        return round($value, 6);
    }
}
