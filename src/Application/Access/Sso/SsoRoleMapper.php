<?php

declare(strict_types=1);

namespace App\Application\Access\Sso;

final class SsoRoleMapper
{
    /** @var array<string, string|list<string>> */
    private array $normalizedRoleMap;

    /**
     * @param array<string, string|list<string>> $roleMap
     */
    public function __construct(
        array $roleMap,
        private readonly string $defaultInternalRole,
        private readonly bool $allowDefaultFallback,
    ) {
        $normalized = [];
        foreach ($roleMap as $externalRole => $mapping) {
            if (!is_string($externalRole) || trim($externalRole) === '') {
                continue;
            }
            $normalized[strtoupper(trim($externalRole))] = $mapping;
        }

        $this->normalizedRoleMap = $normalized;
    }

    /**
     * @param list<string> $externalRoles
     *
     * @return list<string>
     */
    public function mapToInternalRoles(array $externalRoles): array
    {
        return $this->mapDetailed($externalRoles)->internalRoles;
    }

    /**
     * @param list<string> $externalRoles
     */
    public function mapDetailed(array $externalRoles): SsoRoleMappingResult
    {
        $internalRoles = [];
        $matchedExternalRoles = [];
        $unmappedExternalRoles = [];
        $normalizedExternalRoles = [];

        foreach ($externalRoles as $externalRole) {
            if (!is_string($externalRole)) {
                continue;
            }

            $normalizedExternalRole = strtoupper(trim($externalRole));
            if ($normalizedExternalRole === '') {
                continue;
            }
            $normalizedExternalRoles[] = $normalizedExternalRole;

            $mapped = $this->normalizedRoleMap[$normalizedExternalRole] ?? null;
            if (is_string($mapped) && trim($mapped) !== '') {
                $internalRoles[] = strtoupper(trim($mapped));
                $matchedExternalRoles[] = $normalizedExternalRole;
                continue;
            }

            if (is_array($mapped)) {
                $hasValue = false;
                foreach ($mapped as $value) {
                    if (is_string($value) && trim($value) !== '') {
                        $internalRoles[] = strtoupper(trim($value));
                        $hasValue = true;
                    }
                }

                if ($hasValue) {
                    $matchedExternalRoles[] = $normalizedExternalRole;
                    continue;
                }
            }

            $unmappedExternalRoles[] = $normalizedExternalRole;
        }

        if ($this->allowDefaultFallback && $internalRoles === [] && trim($this->defaultInternalRole) !== '') {
            $internalRoles[] = strtoupper(trim($this->defaultInternalRole));
        }

        $normalizedExternalRoles = array_values(array_unique($normalizedExternalRoles));
        sort($normalizedExternalRoles);
        $matchedExternalRoles = array_values(array_unique($matchedExternalRoles));
        sort($matchedExternalRoles);
        $unmappedExternalRoles = array_values(array_unique($unmappedExternalRoles));
        sort($unmappedExternalRoles);
        $internalRoles = array_values(array_unique($internalRoles));
        sort($internalRoles);

        return new SsoRoleMappingResult(
            externalRoles: $normalizedExternalRoles,
            matchedExternalRoles: $matchedExternalRoles,
            unmappedExternalRoles: $unmappedExternalRoles,
            internalRoles: $internalRoles,
        );
    }
}
