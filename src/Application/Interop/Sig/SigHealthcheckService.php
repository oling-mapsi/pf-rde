<?php

declare(strict_types=1);

namespace App\Application\Interop\Sig;

use App\Infrastructure\Repository\MapServiceEndpointRepository;

final class SigHealthcheckService
{
    public function __construct(
        private readonly MapServiceEndpointRepository $endpointRepository,
        private readonly MapServiceProviderInterface $provider,
    ) {
    }

    /** @return list<MapServiceHealth> */
    public function checkAll(): array
    {
        $checks = [];

        foreach ($this->endpointRepository->findBy(['enabled' => true]) as $endpoint) {
            $checks[] = $this->provider->healthcheck(
                new EndpointConfig(
                    $endpoint->getName(),
                    $endpoint->getServiceType(),
                    $endpoint->getBaseUrl(),
                    $endpoint->isEnabled(),
                    $endpoint->getTimeoutMs(),
                )
            );
        }

        return $checks;
    }
}
