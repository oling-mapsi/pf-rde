<?php

declare(strict_types=1);

namespace App\Tests\Application\Cartography;

use App\Application\Cartography\DTO\StaticMapSearchCriteria;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class StaticMapSearchCriteriaTest extends TestCase
{
    public function testFromRequestParsesValues(): void
    {
        $request = new Request([
            'q' => 'reseau',
            'theme' => 'Mobilite',
            'year' => '2025',
            'page' => '3',
            'per_page' => '12',
        ]);

        $criteria = StaticMapSearchCriteria::fromRequest($request);

        self::assertSame('reseau', $criteria->query);
        self::assertSame(['Mobilite'], $criteria->themes);
        self::assertSame(2025, $criteria->year);
        self::assertSame(3, $criteria->page);
        self::assertSame(12, $criteria->perPage);
    }

    public function testFromRequestAppliesBounds(): void
    {
        $request = new Request([
            'year' => '1900',
            'page' => '-5',
            'per_page' => '999',
        ]);

        $criteria = StaticMapSearchCriteria::fromRequest($request);

        self::assertNull($criteria->year);
        self::assertSame(1, $criteria->page);
        self::assertSame(24, $criteria->perPage);
    }
}
