<?php

declare(strict_types=1);

namespace App\Infrastructure\Interop\Sig\Provider;

use App\Application\Interop\Sig\EndpointConfig;
use App\Application\Interop\Sig\MapServiceHealth;
use App\Application\Interop\Sig\MapServiceProviderInterface;

final class MockMapServiceProvider implements MapServiceProviderInterface
{
    public function healthcheck(EndpointConfig $endpoint): MapServiceHealth
    {
        if (!$endpoint->enabled) {
            return new MapServiceHealth($endpoint->name, $endpoint->serviceType, false, 'Endpoint disabled by configuration.');
        }

        if (str_contains(strtolower($endpoint->baseUrl), 'down')) {
            return new MapServiceHealth($endpoint->name, $endpoint->serviceType, false, 'Mock unavailable endpoint.');
        }

        return new MapServiceHealth($endpoint->name, $endpoint->serviceType, true, 'Mock endpoint available.');
    }
}
