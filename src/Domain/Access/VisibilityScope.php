<?php

declare(strict_types=1);

namespace App\Domain\Access;

final class VisibilityScope
{
    public const PUBLIC = 'public';
    public const EXTERNAL = 'external';
    public const INTERNAL = 'internal';

    public const LABELS = [
        self::PUBLIC => 'Public',
        self::EXTERNAL => 'Externe enregistré',
        self::INTERNAL => 'Interne agents/admin',
    ];

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [self::PUBLIC, self::EXTERNAL, self::INTERNAL];
    }
}

