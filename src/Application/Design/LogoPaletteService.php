<?php

declare(strict_types=1);

namespace App\Application\Design;

final class LogoPaletteService
{
    private ?array $themePalette = null;

    public function __construct(private readonly string $projectDir)
    {
    }

    /** @return list<string> */
    public function getThemePalette(int $count = 4): array
    {
        $palette = $this->themePalette ??= $this->buildThemePalette();

        return array_slice($palette, 0, max(1, $count));
    }

    /** @return array{primary: string, secondary: string, accent: string, success: string, orange: string} */
    public function getSemanticPalette(): array
    {
        $palette = $this->getThemePalette(4);

        return [
            'primary' => $palette[0] ?? '#EA5D29',
            'secondary' => $palette[1] ?? '#2FA7D9',
            'accent' => $palette[2] ?? '#F3C623',
            'success' => $palette[3] ?? '#7AA63A',
            'orange' => $palette[0] ?? '#EA5D29',
        ];
    }

    /** @return array<string, string> */
    public function getAdminChoices(): array
    {
        $choices = [];

        foreach ($this->getThemePalette(4) as $index => $color) {
            $choices[sprintf('Palette logo %d (%s)', $index + 1, $color)] = $color;
        }

        return $choices;
    }

    private function getLogoPath(): string
    {
        return $this->projectDir.'/public/images/logo-rdg.jpg';
    }

    /** @return list<string> */
    private function buildThemePalette(): array
    {
        $colors = $this->extractLogoColors();
        if ($colors === []) {
            return ['#EA5D29', '#2FA7D9', '#F3C623', '#7AA63A'];
        }

        $classified = [
            'orange' => $this->pickByHueRange($colors, 5, 45),
            'blue' => $this->pickByHueRange($colors, 170, 250),
            'yellow' => $this->pickByHueRange($colors, 45, 80),
            'green' => $this->pickByHueRange($colors, 80, 170),
        ];

        $palette = [];
        foreach (['orange', 'blue', 'yellow', 'green'] as $slot) {
            $palette[] = $classified[$slot] ?? $this->nextDistinctColor($colors, $palette);
        }

        return array_values(array_map(
            fn (?string $color, int $index): string => $this->normalizeThemeColor($color ?? $colors[$index % count($colors)]),
            $palette,
            array_keys($palette),
        ));
    }

    /** @return list<string> */
    private function extractLogoColors(): array
    {
        $path = $this->getLogoPath();
        if (!is_file($path) || !function_exists('imagecreatefromjpeg')) {
            return [];
        }

        $image = @imagecreatefromjpeg($path);
        if (!$image instanceof \GdImage) {
            return [];
        }

        $sampled = imagecreatetruecolor(96, 96);
        if (!$sampled instanceof \GdImage) {
            imagedestroy($image);

            return [];
        }

        imagecopyresampled($sampled, $image, 0, 0, 0, 0, 96, 96, imagesx($image), imagesy($image));
        imagedestroy($image);

        $buckets = [];
        for ($y = 0; $y < 96; ++$y) {
            for ($x = 0; $x < 96; ++$x) {
                $rgb = imagecolorat($sampled, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                [$h, $s, $l] = $this->rgbToHsl($r, $g, $b);

                if ($s < 0.18 || $l < 0.16 || $l > 0.9) {
                    continue;
                }

                $bucket = sprintf(
                    '%02X%02X%02X',
                    min(255, (int) round($r / 32) * 32),
                    min(255, (int) round($g / 32) * 32),
                    min(255, (int) round($b / 32) * 32),
                );
                $buckets[$bucket] = ($buckets[$bucket] ?? 0) + (1 + (int) round($s * 4));
            }
        }

        imagedestroy($sampled);
        arsort($buckets);

        $colors = [];
        foreach (array_keys($buckets) as $bucket) {
            $color = '#'.substr($bucket, 0, 6);
            if ($this->isDistinctEnough($color, $colors)) {
                $colors[] = $color;
            }

            if (count($colors) >= 8) {
                break;
            }
        }

        return $colors;
    }

    private function pickByHueRange(array $colors, float $minHue, float $maxHue): ?string
    {
        foreach ($colors as $color) {
            [$h] = $this->hexToHsl($color);
            if ($h >= $minHue && $h <= $maxHue) {
                return $color;
            }
        }

        return null;
    }

    private function nextDistinctColor(array $colors, array $used): string
    {
        foreach ($colors as $color) {
            if ($this->isDistinctEnough($color, $used)) {
                return $color;
            }
        }

        return $colors[0] ?? '#3CB4DF';
    }

    private function isDistinctEnough(string $color, array $existing): bool
    {
        [$h1, $s1, $l1] = $this->hexToHsl($color);

        foreach ($existing as $other) {
            [$h2, $s2, $l2] = $this->hexToHsl($other);
            if (abs($h1 - $h2) < 18 && abs($s1 - $s2) < 0.18 && abs($l1 - $l2) < 0.16) {
                return false;
            }
        }

        return true;
    }

    private function normalizeThemeColor(string $hex): string
    {
        [$h, $s, $l] = $this->hexToHsl($hex);

        $s = max(0.45, min(0.82, $s));
        $l = max(0.38, min(0.58, $l));

        return $this->hslToHex($h, $s, $l);
    }

    /** @return array{0: float, 1: float, 2: float} */
    private function hexToHsl(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return $this->rgbToHsl(
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        );
    }

    /** @return array{0: float, 1: float, 2: float} */
    private function rgbToHsl(int $r, int $g, int $b): array
    {
        $r /= 255;
        $g /= 255;
        $b /= 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $h = 0.0;
        $l = ($max + $min) / 2;
        $d = $max - $min;
        $denominator = 1 - abs(2 * $l - 1);
        $s = abs($d) < 1e-9 || $denominator <= 0.0 ? 0.0 : $d / $denominator;

        if (abs($d) >= 1e-9) {
            if ($max === $r) {
                $h = 60 * fmod((($g - $b) / $d), 6);
            } elseif ($max === $g) {
                $h = 60 * ((($b - $r) / $d) + 2);
            } else {
                $h = 60 * ((($r - $g) / $d) + 4);
            }
        }

        if ($h < 0) {
            $h += 360;
        }

        return [$h, $s, $l];
    }

    private function hslToHex(float $h, float $s, float $l): string
    {
        $c = (1 - abs(2 * $l - 1)) * $s;
        $x = $c * (1 - abs(fmod($h / 60, 2) - 1));
        $m = $l - ($c / 2);

        $r = 0.0;
        $g = 0.0;
        $b = 0.0;

        if ($h < 60) {
            [$r, $g, $b] = [$c, $x, 0];
        } elseif ($h < 120) {
            [$r, $g, $b] = [$x, $c, 0];
        } elseif ($h < 180) {
            [$r, $g, $b] = [0, $c, $x];
        } elseif ($h < 240) {
            [$r, $g, $b] = [0, $x, $c];
        } elseif ($h < 300) {
            [$r, $g, $b] = [$x, 0, $c];
        } else {
            [$r, $g, $b] = [$c, 0, $x];
        }

        return sprintf(
            '#%02X%02X%02X',
            (int) round(($r + $m) * 255),
            (int) round(($g + $m) * 255),
            (int) round(($b + $m) * 255),
        );
    }
}
