<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

#[AsCommand(
    name: 'app:sso:o365:smoke-test',
    description: 'Vérifie la configuration SSO Office 365 / Entra ID et réalise un test technique unilatéral.',
)]
final class Office365SsoSmokeTestCommand extends Command
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $tenantId,
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $scope,
        private readonly string $roleClaim,
        private readonly bool $enabled,
        /** @var list<string> */
        private readonly array $allowedTenantIds,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'portal-url',
                null,
                InputOption::VALUE_REQUIRED,
                'URL publique du portail à vérifier (ex: https://sigr.routesdeguadeloupe.fr).'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $portalUrl = $this->normalizePortalUrl($input->getOption('portal-url'));
        $callbackUrl = $this->urlGenerator->generate('app_sso_callback', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $discoveryUrl = sprintf(
            'https://login.microsoftonline.com/%s/v2.0/.well-known/openid-configuration',
            rawurlencode($this->tenantId)
        );

        $io->title('Smoke test SSO Office 365');
        $io->table(
            ['Paramètre', 'Valeur'],
            [
                ['SSO activé', $this->enabled ? 'oui' : 'non'],
                ['Tenant ID', $this->tenantId !== '' ? $this->tenantId : '(vide)'],
                ['Client ID', $this->clientId !== '' ? $this->clientId : '(vide)'],
                ['Callback générée', $callbackUrl],
                ['Claim rôle', $this->roleClaim !== '' ? $this->roleClaim : '(vide)'],
                ['Tenant(s) autorisé(s)', $this->allowedTenantIds !== [] ? implode(', ', $this->allowedTenantIds) : '(aucune restriction)'],
                ['Portail vérifié', $portalUrl ?? '(non fourni)'],
            ]
        );

        $missing = $this->missingConfigurationKeys();
        if ($missing !== []) {
            $io->error(sprintf('Configuration SSO incomplète: %s', implode(', ', $missing)));

            return Command::FAILURE;
        }

        $failures = [];
        $warnings = [];

        try {
            $discoveryPayload = $this->requestJson('GET', $discoveryUrl);
            $expectedIssuer = sprintf('https://login.microsoftonline.com/%s/v2.0', $this->tenantId);

            if (($discoveryPayload['issuer'] ?? null) !== $expectedIssuer) {
                $failures[] = sprintf('Issuer découverte inattendu: %s', (string) ($discoveryPayload['issuer'] ?? '(absent)'));
            } else {
                $io->success('Discovery OIDC Microsoft accessible et issuer cohérent.');
            }

            $jwksUri = (string) ($discoveryPayload['jwks_uri'] ?? '');
            if ($jwksUri === '') {
                $failures[] = 'JWKS URI absente dans le document de discovery.';
            } else {
                $jwksResponse = $this->httpClient->request('GET', $jwksUri);
                if ($jwksResponse->getStatusCode() >= 400) {
                    $failures[] = sprintf('JWKS Microsoft inaccessible (HTTP %d).', $jwksResponse->getStatusCode());
                } else {
                    $io->success('Endpoint JWKS Microsoft accessible.');
                }
            }

            $authorizationUrl = $this->buildAuthorizationUrl($callbackUrl, (string) ($discoveryPayload['authorization_endpoint'] ?? ''));
            $authorizationResponse = $this->httpClient->request('GET', $authorizationUrl, [
                'headers' => ['Accept-Language' => 'en-US,en;q=0.9'],
            ]);
            $authorizationBody = $authorizationResponse->getContent(false);

            if ($authorizationResponse->getStatusCode() >= 400) {
                $failures[] = sprintf('Endpoint authorize Microsoft en erreur (HTTP %d).', $authorizationResponse->getStatusCode());
            } elseif (str_contains($authorizationBody, 'AADSTS50011')) {
                $failures[] = 'Redirect URI rejetée par Microsoft (AADSTS50011).';
            } elseif (str_contains($authorizationBody, '<title>Sign in to your account</title>')) {
                $io->success('URL authorize acceptée par Microsoft avec le couple client/callback fourni.');
            } else {
                $warnings[] = 'La page authorize a répondu, mais le contenu ne correspond pas au gabarit de connexion attendu.';
            }

            $tokenEndpoint = (string) ($discoveryPayload['token_endpoint'] ?? '');
            if ($tokenEndpoint === '') {
                $failures[] = 'Token endpoint absent dans le document de discovery.';
            } else {
                $tokenPayload = $this->requestJson('POST', $tokenEndpoint, [
                    'body' => [
                        'client_id' => $this->clientId,
                        'client_secret' => $this->clientSecret,
                        'grant_type' => 'authorization_code',
                        'code' => 'codex-invalid-authorization-code',
                        'redirect_uri' => $callbackUrl,
                        'scope' => trim($this->scope) !== '' ? $this->scope : 'openid profile email',
                    ],
                ]);

                $tokenError = (string) ($tokenPayload['error'] ?? '');
                $tokenDescription = (string) ($tokenPayload['error_description'] ?? '');

                if ($tokenError === 'invalid_client') {
                    $failures[] = 'Client secret refusé par le token endpoint Microsoft.';
                } elseif ($tokenError === '') {
                    $warnings[] = 'Le token endpoint n’a pas renvoyé le format d’erreur attendu pour le test au faux code.';
                } else {
                    $io->success(sprintf(
                        'Token endpoint joignable et authentification client acceptée (retour attendu sur faux code: %s).',
                        $tokenError
                    ));

                    if ($tokenDescription !== '' && !str_contains(strtolower($tokenDescription), 'code')) {
                        $warnings[] = sprintf('Message token inhabituel: %s', $tokenDescription);
                    }
                }
            }
        } catch (\Throwable $exception) {
            $failures[] = sprintf('%s: %s', $exception::class, $exception->getMessage());
        }

        if ($portalUrl !== null) {
            try {
                $portalResponse = $this->httpClient->request('GET', $portalUrl.'/sso/connexion', [
                    'max_redirects' => 0,
                ]);
                $statusCode = $portalResponse->getStatusCode();
                $location = $portalResponse->getHeaders(false)['location'][0] ?? '';

                if ($statusCode >= 300 && $statusCode < 400 && str_contains($location, 'login.microsoftonline.com')) {
                    $io->success('Le portail exposé redirige déjà vers Microsoft sur /sso/connexion.');
                } elseif ($statusCode >= 300 && $statusCode < 400 && str_contains($location, '/connexion')) {
                    $warnings[] = 'Le portail exposé renvoie encore vers /connexion sur /sso/connexion : SSO vraisemblablement non activé côté environnement.';
                } else {
                    $warnings[] = sprintf(
                        'Réponse inhabituelle du portail exposé sur /sso/connexion : HTTP %d, location=%s',
                        $statusCode,
                        $location !== '' ? $location : '(absente)'
                    );
                }
            } catch (\Throwable $exception) {
                $warnings[] = sprintf('Impossible de vérifier le portail exposé: %s', $exception->getMessage());
            }
        }

        foreach ($warnings as $warning) {
            $io->warning($warning);
        }

        if ($failures !== []) {
            $io->error($failures);

            return Command::FAILURE;
        }

        $io->success('Smoke test technique terminé.');

        return Command::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function missingConfigurationKeys(): array
    {
        $missing = [];

        if (!$this->enabled) {
            $missing[] = 'APP_SSO_O365_ENABLED';
        }
        if (trim($this->tenantId) === '') {
            $missing[] = 'APP_SSO_O365_TENANT_ID';
        }
        if (trim($this->clientId) === '') {
            $missing[] = 'APP_SSO_O365_CLIENT_ID';
        }
        if (trim($this->clientSecret) === '') {
            $missing[] = 'APP_SSO_O365_CLIENT_SECRET';
        }

        return $missing;
    }

    private function buildAuthorizationUrl(string $callbackUrl, string $authorizationEndpoint): string
    {
        $baseUrl = trim($authorizationEndpoint);
        if ($baseUrl === '') {
            $baseUrl = sprintf(
                'https://login.microsoftonline.com/%s/oauth2/v2.0/authorize',
                rawurlencode($this->tenantId)
            );
        }

        return $baseUrl.'?'.http_build_query([
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'redirect_uri' => $callbackUrl,
            'response_mode' => 'query',
            'scope' => trim($this->scope) !== '' ? $this->scope : 'openid profile email',
            'state' => 'codex-smoke-state',
            'nonce' => 'codex-smoke-nonce',
        ]);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function requestJson(string $method, string $url, array $options = []): array
    {
        $response = $this->httpClient->request($method, $url, $options);
        $payload = $response->toArray(false);

        return is_array($payload) ? $payload : [];
    }

    private function normalizePortalUrl(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $normalized = rtrim(trim($value), '/');

        return $normalized !== '' ? $normalized : null;
    }
}
