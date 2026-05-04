<?php

declare(strict_types=1);

namespace App\Application\Cartography\DTO;

use Symfony\Component\HttpFoundation\Request;

final class StaticMapSearchCriteria
{
    /** @param list<string> $themes */
    public function __construct(
        public readonly ?string $query,
        public readonly array $themes,
        public readonly ?int $year,
        public readonly int $page,
        public readonly int $perPage,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        $query = trim((string) $request->query->get('q', ''));
        $queryParams = $request->query->all();
        $themeParam = $queryParams['theme'] ?? [];
        $themesRaw = is_array($themeParam) ? $themeParam : [$themeParam];
        $themes = [];
        foreach ($themesRaw as $themeRaw) {
            $theme = trim((string) $themeRaw);
            if ($theme !== '') {
                $themes[] = $theme;
            }
        }
        $themes = array_values(array_unique($themes));
        $yearRaw = trim((string) $request->query->get('year', ''));

        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = min(24, max(1, (int) $request->query->get('per_page', 9)));

        $year = null;
        if ($yearRaw !== '' && ctype_digit($yearRaw)) {
            $parsed = (int) $yearRaw;
            if ($parsed >= 2000 && $parsed <= 2100) {
                $year = $parsed;
            }
        }

        return new self(
            $query !== '' ? $query : null,
            $themes,
            $year,
            $page,
            $perPage,
        );
    }
}
