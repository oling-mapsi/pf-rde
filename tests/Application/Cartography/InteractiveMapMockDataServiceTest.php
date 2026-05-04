<?php

declare(strict_types=1);

namespace App\Tests\Application\Cartography;

use App\Application\Cartography\Service\InteractiveMapMockDataService;
use PHPUnit\Framework\TestCase;

final class InteractiveMapMockDataServiceTest extends TestCase
{
    public function testFeaturesCanBeFilteredByLayerStatusAndQuery(): void
    {
        $service = new InteractiveMapMockDataService();

        $payload = $service->getFeatures(
            slug: 'visualisation-reseau',
            requestedLayerIds: ['travaux_planifies'],
            status: 'in_progress',
            category: null,
            query: 'talus'
        );

        self::assertSame('FeatureCollection', $payload['type']);
        self::assertGreaterThanOrEqual(1, $payload['total']);
        self::assertArrayHasKey('travaux_planifies', $payload['countsByLayer']);

        $firstFeature = $payload['features'][0] ?? null;
        self::assertIsArray($firstFeature);
        self::assertSame('travaux_planifies', $firstFeature['properties']['layer_id'] ?? null);
        self::assertSame('in_progress', $firstFeature['properties']['status'] ?? null);
    }

    public function testLegendReturnsItemsForRequestedLayer(): void
    {
        $service = new InteractiveMapMockDataService();

        $legend = $service->getLegend(
            slug: 'visualisation-reseau',
            requestedLayerIds: ['reseau_principal']
        );

        self::assertIsArray($legend['items']);
        self::assertNotEmpty($legend['items']);
        self::assertSame('reseau_principal', $legend['items'][0]['layerId']);
    }

    public function testFeatureInfoReturnsClosestFeatureNearKnownPoint(): void
    {
        $service = new InteractiveMapMockDataService();

        $featureInfo = $service->getFeatureInfo(
            slug: 'visualisation-reseau',
            lng: -61.512,
            lat: 16.249,
            requestedLayerIds: ['travaux_planifies']
        );

        self::assertNotNull($featureInfo);
        self::assertSame('travaux_planifies', $featureInfo['feature']['layerId']);
        self::assertLessThan(1500, $featureInfo['distanceMeters']);
    }

    public function testBasemapAndOperationalDatasetAreRichEnoughForFullDemo(): void
    {
        $service = new InteractiveMapMockDataService();

        $basemap = $service->getBasemap('visualisation-reseau');
        self::assertSame('FeatureCollection', $basemap['type']);
        self::assertGreaterThan(120, count($basemap['features']));

        $features = $service->getFeatures('visualisation-reseau');
        self::assertGreaterThan(150, $features['total']);
        self::assertGreaterThan(80, $features['countsByLayer']['reseau_principal'] ?? 0);
        self::assertGreaterThan(40, $features['countsByLayer']['travaux_planifies'] ?? 0);
    }
}
