<?php

declare(strict_types=1);

namespace App\UI\Admin;

final class IconKeyPresenter
{
    /** @var array<string, string> */
    private const ICON_MAP = [
        'map' => 'fa-map',
        'layers' => 'fa-layer-group',
        'database' => 'fa-database',
        'chart' => 'fa-chart-line',
        'business' => 'fa-building',
        'briefcase' => 'fa-briefcase',
        'building' => 'fa-city',
        'handshake' => 'fa-handshake',
        'digital' => 'fa-laptop-code',
        'si' => 'fa-network-wired',
        'sig' => 'fa-map-location-dot',
        'network' => 'fa-diagram-project',
        'cloud' => 'fa-cloud',
        'server' => 'fa-server',
        'code' => 'fa-code',
        'route' => 'fa-route',
        'roadwork' => 'fa-triangle-exclamation',
        'transport' => 'fa-truck-fast',
        'file' => 'fa-file',
        'file-excel' => 'fa-file-excel',
        'file-csv' => 'fa-file-csv',
        'file-json' => 'fa-file-code',
        'file-pdf' => 'fa-file-pdf',
        'link' => 'fa-link',
        'globe' => 'fa-globe',
        'satellite' => 'fa-satellite',
        'road' => 'fa-road',
        'truck' => 'fa-truck',
        'bus' => 'fa-bus',
        'car' => 'fa-car',
        'ship' => 'fa-ship',
        'bridge' => 'fa-bridge',
        'cone' => 'fa-traffic-cone',
        'traffic' => 'fa-traffic-light',
        'map-pin' => 'fa-map-pin',
        'compass' => 'fa-compass',
        'wrench' => 'fa-wrench',
        'clipboard' => 'fa-clipboard-list',
        'shield' => 'fa-shield-alt',
        'chart-line' => 'fa-chart-line',
        'location-dot' => 'fa-location-dot',
        'users' => 'fa-users',
        'download' => 'fa-download',
        'search' => 'fa-magnifying-glass',
    ];

    /**
     * @param array<string, string> $choices
     */
    public static function renderHtml(?string $iconKey, array $choices): string
    {
        $key = strtolower(trim((string) $iconKey));
        if ($key === '') {
            return '<span class="ea-icon-choice ea-icon-choice--empty">-</span>';
        }

        $labelByKey = array_flip($choices);
        $label = $labelByKey[$key] ?? self::fallbackLabel($key);
        $iconClass = self::ICON_MAP[$key] ?? 'fa-circle';

        return sprintf(
            '<span class="ea-icon-choice"><i class="fa %s" aria-hidden="true"></i><span>%s</span></span>',
            htmlspecialchars($iconClass, \ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($label, \ENT_QUOTES, 'UTF-8'),
        );
    }

    private static function fallbackLabel(string $key): string
    {
        return ucfirst(str_replace('-', ' ', $key));
    }
}
