<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class SecurityHeadersSubscriber implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire('%app.matomo.enabled%')]
        private readonly bool $matomoEnabled,
        #[Autowire('%app.matomo.base_url%')]
        private readonly string $matomoBaseUrl,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        $scriptSources = ["'self'", "'unsafe-inline'", 'data:', 'https://ga.jspm.io'];
        $connectSources = [
            "'self'",
            'https://tile.openstreetmap.org',
            'https://a.tile.openstreetmap.org',
            'https://b.tile.openstreetmap.org',
            'https://c.tile.openstreetmap.org',
        ];
        $imageSources = ["'self'", 'data:', 'https:', 'blob:'];

        $matomoOrigin = $this->trustedOrigin($this->matomoBaseUrl);
        if ($this->matomoEnabled && $matomoOrigin !== null) {
            $scriptSources[] = $matomoOrigin;
            $connectSources[] = $matomoOrigin;
            $imageSources[] = $matomoOrigin;
        }

        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            'img-src '.implode(' ', array_unique($imageSources)),
            "style-src 'self' 'unsafe-inline'",
            'script-src '.implode(' ', array_unique($scriptSources)),
            'connect-src '.implode(' ', array_unique($connectSources)),
            "worker-src 'self'",
            "child-src 'self'",
            "frame-ancestors 'self'",
        ]).';');
    }

    private function trustedOrigin(string $url): ?string
    {
        $url = trim($url);
        if ($url === '' || str_starts_with($url, '/')) {
            return null;
        }

        $parts = parse_url($url);
        if (!is_array($parts)) {
            return null;
        }

        $scheme = $parts['scheme'] ?? null;
        $host = $parts['host'] ?? null;
        if (!in_array($scheme, ['http', 'https'], true) || !is_string($host) || $host === '') {
            return null;
        }

        $port = isset($parts['port']) ? ':'.$parts['port'] : '';

        return $scheme.'://'.$host.$port;
    }
}
