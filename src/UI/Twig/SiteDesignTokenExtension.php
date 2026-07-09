<?php

declare(strict_types=1);

namespace App\UI\Twig;

use App\Application\Content\Service\SiteDesignTokenService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class SiteDesignTokenExtension extends AbstractExtension
{
    public function __construct(private readonly SiteDesignTokenService $siteDesignTokenService)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('app_site_design_styles', [$this, 'getSiteDesignStyles']),
        ];
    }

    /** @return array<string, string> */
    public function getSiteDesignStyles(): array
    {
        return $this->siteDesignTokenService->buildGlobalStyles();
    }
}
