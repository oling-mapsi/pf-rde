<?php

declare(strict_types=1);

namespace App\Application\Cartography\DTO;

use App\Domain\Cartography\Entity\DataSource;
use Symfony\Component\HttpFoundation\Request;

final class DataCatalogSearchCriteria
{
    private const LEGACY_TYPE_ALIASES = [
        'static' => DataSource::TYPE_STATIC_MAP,
        'interactive' => DataSource::TYPE_CARTOGRAPHY_LINK,
    ];

    /** @param list<string> $themes */
    public function __construct(
        public readonly ?string $query,
        public readonly array $themes,
        /** @var list<string> */
        public readonly array $types,
        /** @var list<string> */
        public readonly array $categories,
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
        $categories = self::normalizeStringList($rawParams['category'] ?? []);

        $types = [];
        $validTypes = array_flip(array_keys(DataSource::TYPE_LABELS));
        foreach ($typesRaw as $type) {
            $normalizedType = self::LEGACY_TYPE_ALIASES[$type] ?? $type;
            if (isset($validTypes[$normalizedType])) {
                $types[] = $normalizedType;
            }
        }
        $types = array_values(array_unique($types));

        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = min(24, max(1, (int) $request->query->get('per_page', 9)));

        return new self(
            query: $query !== '' ? $query : null,
            themes: $themes,
            types: $types,
            categories: $categories,
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
