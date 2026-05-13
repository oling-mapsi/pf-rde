<?php

declare(strict_types=1);

namespace App\Application\Access\Service;

final class SiretValidationResult
{
    private function __construct(
        public readonly bool $valid,
        public readonly string $normalizedSiret,
        public readonly ?string $errorMessage = null,
        public readonly ?string $companyName = null,
    ) {
    }

    public static function success(string $normalizedSiret, ?string $companyName = null): self
    {
        return new self(
            valid: true,
            normalizedSiret: $normalizedSiret,
            errorMessage: null,
            companyName: $companyName,
        );
    }

    public static function failure(string $normalizedSiret, string $errorMessage): self
    {
        return new self(
            valid: false,
            normalizedSiret: $normalizedSiret,
            errorMessage: $errorMessage,
        );
    }
}
