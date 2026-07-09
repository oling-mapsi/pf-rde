<?php

declare(strict_types=1);

namespace App\Application\Content\Service;

use App\Application\Design\LogoPaletteService;
use App\Domain\Content\Entity\HomepageContent;
use App\Infrastructure\Repository\HomepageContentRepository;

final class SiteDesignTokenService
{
    public function __construct(
        private readonly HomepageContentRepository $homepageContentRepository,
        private readonly LogoPaletteService $logoPaletteService,
    ) {
    }

    /** @return array<string, string> */
    public function buildGlobalStyles(): array
    {
        $homepage = $this->homepageContentRepository->findEditableHomepage();
        $primary = $this->normalizeCssColor($homepage?->getBrandPrimaryColor());
        $secondary = $this->normalizeCssColor($homepage?->getBrandSecondaryColor());
        $accent = $this->normalizeCssColor($homepage?->getBrandAccentColor());
        $success = $this->normalizeCssColor($homepage?->getBrandSuccessColor());
        $orange = $this->normalizeCssColor($homepage?->getBrandOrangeRoadColor());
        $text = $this->normalizeCssColor($homepage?->getTextDefaultColor());
        $textMuted = $this->normalizeCssColor($homepage?->getTextMutedColor());
        $bg = $this->normalizeCssColor($homepage?->getBackgroundDefaultColor());
        $surface = $this->normalizeCssColor($homepage?->getBackgroundSurfaceAltColor());
        $border = $this->normalizeCssColor($homepage?->getBorderDefaultColor());
        $borderFocus = $this->normalizeCssColor($homepage?->getBorderFocusColor());
        $textInverse = $this->normalizeCssColor($homepage?->getTextInverseColor());
        $link = $this->normalizeCssColor($homepage?->getLinkColor());
        $linkHover = $this->normalizeCssColor($homepage?->getLinkHoverColor());
        $buttonPrimaryBg = $this->normalizeCssColor($homepage?->getButtonPrimaryBackgroundColor());
        $buttonPrimaryBorder = $this->normalizeCssColor($homepage?->getButtonPrimaryBorderColor());
        $buttonPrimaryText = $this->normalizeCssColor($homepage?->getButtonPrimaryTextColor());
        $buttonPrimaryBgHover = $this->normalizeCssColor($homepage?->getButtonPrimaryBackgroundHoverColor());
        $buttonPrimaryBorderHover = $this->normalizeCssColor($homepage?->getButtonPrimaryBorderHoverColor());
        $buttonPrimaryTextHover = $this->normalizeCssColor($homepage?->getButtonPrimaryTextHoverColor());
        $buttonOutlineBg = $this->normalizeCssColor($homepage?->getButtonOutlineBackgroundColor());
        $buttonOutlineBorder = $this->normalizeCssColor($homepage?->getButtonOutlineBorderColor());
        $buttonOutlineText = $this->normalizeCssColor($homepage?->getButtonOutlineTextColor());
        $buttonOutlineBgHover = $this->normalizeCssColor($homepage?->getButtonOutlineBackgroundHoverColor());
        $buttonOutlineBorderHover = $this->normalizeCssColor($homepage?->getButtonOutlineBorderHoverColor());
        $buttonOutlineTextHover = $this->normalizeCssColor($homepage?->getButtonOutlineTextHoverColor());
        $semanticPalette = $this->logoPaletteService->getSemanticPalette();
        $themePalette = $this->logoPaletteService->getThemePalette(4);

        $primary ??= $semanticPalette['primary'];
        $secondary ??= $semanticPalette['secondary'];
        $accent ??= $semanticPalette['accent'];
        $success ??= $semanticPalette['success'];
        $orange ??= $semanticPalette['orange'];

        return array_filter([
            '--rdg-blue' => $primary,
            '--rdg-blue-rgb' => $this->normalizeCssRgb($primary),
            '--rdg-sky' => $secondary,
            '--rdg-sky-rgb' => $this->normalizeCssRgb($secondary),
            '--rdg-yellow' => $accent,
            '--rdg-yellow-rgb' => $this->normalizeCssRgb($accent),
            '--rdg-brand-primary' => $primary,
            '--rdg-brand-primary-raw' => $primary,
            '--rdg-brand-secondary' => $secondary,
            '--rdg-brand-accent' => $accent,
            '--rdg-green' => $success,
            '--rdg-green-rgb' => $this->normalizeCssRgb($success),
            '--rdg-orange-road' => $orange,
            '--rdg-orange-road-rgb' => $this->normalizeCssRgb($orange),
            '--rdg-text' => $text,
            '--rdg-text-rgb' => $this->normalizeCssRgb($text),
            '--rdg-muted' => $textMuted,
            '--rdg-bg' => $bg,
            '--rdg-surface' => $surface,
            '--rdg-border' => $border,
            '--color-brand-primary' => $primary,
            '--color-brand-secondary' => $secondary,
            '--color-brand-accent' => $accent,
            '--color-success' => $success,
            '--color-bg-default' => $bg,
            '--color-bg-surface-alt' => $surface,
            '--color-border-default' => $border,
            '--color-border-focus' => $borderFocus,
            '--color-text-default' => $text,
            '--color-text-heading' => $this->normalizeCssColor($homepage?->getTextHeadingColor()),
            '--color-text-muted' => $textMuted,
            '--color-text-subtle' => $textMuted,
            '--color-text-inverse' => $textInverse,
            '--color-link' => $link,
            '--color-link-hover' => $linkHover,
            '--component-button-primary-bg' => $buttonPrimaryBg,
            '--component-button-primary-border' => $buttonPrimaryBorder,
            '--component-button-primary-text' => $buttonPrimaryText,
            '--component-button-primary-bg-hover' => $buttonPrimaryBgHover,
            '--component-button-primary-border-hover' => $buttonPrimaryBorderHover,
            '--component-button-primary-text-hover' => $buttonPrimaryTextHover,
            '--component-button-outline-bg' => $buttonOutlineBg,
            '--component-button-outline-border' => $buttonOutlineBorder,
            '--component-button-outline-text' => $buttonOutlineText,
            '--component-button-outline-bg-hover' => $buttonOutlineBgHover,
            '--component-button-outline-border-hover' => $buttonOutlineBorderHover,
            '--component-button-outline-text-hover' => $buttonOutlineTextHover,
            '--color-theme-1' => $themePalette[0] ?? null,
            '--color-theme-1-rgb' => $this->normalizeCssRgb($themePalette[0] ?? null),
            '--color-theme-2' => $themePalette[1] ?? null,
            '--color-theme-2-rgb' => $this->normalizeCssRgb($themePalette[1] ?? null),
            '--color-theme-3' => $themePalette[2] ?? null,
            '--color-theme-3-rgb' => $this->normalizeCssRgb($themePalette[2] ?? null),
            '--color-theme-4' => $themePalette[3] ?? null,
            '--color-theme-4-rgb' => $this->normalizeCssRgb($themePalette[3] ?? null),
        ]);
    }

    private function normalizeCssColor(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^#[0-9a-fA-F]{3,8}$/', $value) === 1) {
            return $value;
        }

        if (preg_match('/^(?:rgb|rgba|hsl|hsla)\([\d\s.,%+-]+\)$/', $value) === 1) {
            return $value;
        }

        return null;
    }

    private function normalizeCssRgb(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if (preg_match('/^#([0-9a-fA-F]{6})$/', $value, $matches) === 1) {
            $hex = $matches[1];

            return sprintf(
                '%d, %d, %d',
                hexdec(substr($hex, 0, 2)),
                hexdec(substr($hex, 2, 2)),
                hexdec(substr($hex, 4, 2)),
            );
        }

        if (preg_match('/^#([0-9a-fA-F]{3})$/', $value, $matches) === 1) {
            $hex = $matches[1];

            return sprintf(
                '%d, %d, %d',
                hexdec(str_repeat($hex[0], 2)),
                hexdec(str_repeat($hex[1], 2)),
                hexdec(str_repeat($hex[2], 2)),
            );
        }

        if (preg_match('/^rgba?\(\s*(\d{1,3})\s*[, ]\s*(\d{1,3})\s*[, ]\s*(\d{1,3})/i', $value, $matches) === 1) {
            return sprintf('%d, %d, %d', (int) $matches[1], (int) $matches[2], (int) $matches[3]);
        }

        return null;
    }
}
