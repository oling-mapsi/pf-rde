<?php

declare(strict_types=1);

namespace App\Application\Cartography\DTO;

use Symfony\Component\HttpFoundation\Request;

final class DataCatalogSearchCriteria
{
    /** @param list<string> $themes */
    public function __construct(
        public readonly ?string $query,
        public readonly array $themes,
        /** @var list<'static'|'interactive'> */
        public readonly array $types,
        public readonly int $page,
        public readonly int $perPage,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        $query = trim((string) $request->query->get('q', ''));
        $rawParams = $request->query->all();

        $themes = self::normalizeStringList($rawParams['theme'] ?? []);
        $typesRaw = self::normalizeStringList($rawParams['type'] ?? []);

        $types = [];
        foreach ($typesRaw as $type) {
            if ($type === 'static' || $type === 'interactive') {
                $types[] = $type;
            }
        }
        $types = array_values(array_unique($types));

        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = min(24, max(1, (int) $request->query->get('per_page', 9)));

        return new self(
            query: $query !== '' ? $query : null,
            themes: $themes,
            types: $types,
            page: $page,
            perPage: $perPage,
        );
    }

    /**
     * @return list<string>
     */
    private static function normalizeStringList(mixed $rawValue): array
    {
        $values = is_array($rawValue) ? $rawValue : [$rawValue];
        $normalized = [];
        foreach ($values as $value) {
            $candidate = trim((string) $value);
            if ($candidate !== '') {
                $normalized[] = $candidate;
            }
        }

        return array_values(array_unique($normalized));
    }
}
