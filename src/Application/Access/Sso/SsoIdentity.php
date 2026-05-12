<?php

declare(strict_types=1);

namespace App\Application\Access\Sso;

final class SsoIdentity
{
    /**
     * @param list<string> $externalRoles
     */
    public function __construct(
        public readonly string $subject,
        public readonly string $email,
        public readonly ?string $firstName,
        public readonly ?string $lastName,
        public readonly ?string $displayName,
        public readonly string $tenantId,
        public readonly array $externalRoles,
    ) {
    }
}

