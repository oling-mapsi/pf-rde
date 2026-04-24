<?php

declare(strict_types=1);

namespace App\Application\Interop\Sig;

final class EndpointConfig
{
    public function __construct(
        public readonly string $name,
        public readonly string $serviceType,
        public readonly string $baseUrl,
        public readonly bool $enabled,
        public readonly int $timeoutMs,
    ) {
    }
}
