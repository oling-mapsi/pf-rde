<?php

declare(strict_types=1);

namespace App\Application\Cartography\DTO;

use Symfony\Component\HttpFoundation\Request;

final class StaticMapSearchCriteria
{
    public function __construct(
        public readonly ?string $query,
        public readonly ?string $theme,
        public readonly ?int $year,
        public readonly int $page,
        public readonly int $perPage,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        $query = trim((string) $request->query->get('q', ''));
        $theme = trim((string) $request->query->get('theme', ''));
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
            $theme !== '' ? $theme : null,
            $year,
            $page,
            $perPage,
        );
    }
}
