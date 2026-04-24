<?php

declare(strict_types=1);

namespace App\Tests\Application\Interop;

use App\Application\Interop\Sig\EndpointConfig;
use App\Infrastructure\Interop\Sig\Provider\MockMapServiceProvider;
use PHPUnit\Framework\TestCase;

final class MockMapServiceProviderTest extends TestCase
{
    public function testHealthyEndpointReturnsAvailable(): void
    {
        $provider = new MockMapServiceProvider();

        $health = $provider->healthcheck(new EndpointConfig('WMS', 'wms', 'https://mock-up.local', true, 3000));

        self::assertTrue($health->available);
    }

    public function testDownEndpointReturnsUnavailable(): void
    {
        $provider = new MockMapServiceProvider();

        $health = $provider->healthcheck(new EndpointConfig('WFS', 'wfs', 'https://service-down.local', true, 3000));

        self::assertFalse($health->available);
    }
}
