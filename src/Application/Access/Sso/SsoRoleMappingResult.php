<?php

declare(strict_types=1);

namespace App\Application\Access\Sso;

final class SsoRoleMappingResult
{
    /**
     * @param list<string> $externalRoles
     * @param list<string> $matchedExternalRoles
     * @param list<string> $unmappedExternalRoles
     * @param list<string> $internalRoles
     */
    public function __construct(
        public readonly array $externalRoles,
        public readonly array $matchedExternalRoles,
        public readonly array $unmappedExternalRoles,
        public readonly array $internalRoles,
    ) {
    }
}

