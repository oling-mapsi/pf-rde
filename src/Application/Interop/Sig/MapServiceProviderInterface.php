<?php

declare(strict_types=1);

namespace App\Application\Interop\Sig;

interface MapServiceProviderInterface
{
    public function healthcheck(EndpointConfig $endpoint): MapServiceHealth;
}
