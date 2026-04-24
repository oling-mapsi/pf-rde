<?php

declare(strict_types=1);

namespace App\Application\Interop\Sig;

final class MapServiceHealth
{
    public function __construct(
        public readonly string $name,
        public readonly string $serviceType,
        public readonly bool $available,
        public readonly ?string $message = null,
    ) {
    }
}
