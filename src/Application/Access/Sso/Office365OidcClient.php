<?php

declare(strict_types=1);

namespace App\Application\Access\Sso;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class Office365OidcClient
{
    private const SESSION_STATE_KEY = 'sso.oidc.state';
    private const SESSION_NONCE_KEY = 'sso.oidc.nonce';
    /** @var list<string> */
    private array $normalizedAllowedTenantIds;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly RequestStack $requestStack,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $tenantId,
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $scope,
        private readonly string $roleClaim,
        private readonly bool $enabled,
        private readonly bool $requireRoleClaim,
        array $allowedTenantIds = [],
    ) {
        $this->normalizedAllowedTenantIds = array_values(array_unique(array_map(
            static fn (string $tenantId): string => strtolower(trim($tenantId)),
            array_filter(
                $allowedTenantIds,
                static fn (mixed $value): bool => is_string($value) && trim($value) !== ''
            )
        )));
    }

    public function isEnabled(): bool
    {
        return $this->enabled
            && trim($this->tenantId) !== ''
            && trim($this->clientId) !== ''
            && trim($this->clientSecret) !== '';
    }

    public function buildAuthorizationUrl(): string
    {
        $request = $this->requireCurrentRequest();
        $session = $request->getSession();
        if ($session === null) {
            throw new SsoAuthenticationException(
                reasonCode: 'session_unavailable',
                userMessage: 'Impossible d’initialiser la session SSO. Réessayez depuis une nouvelle fenêtre.',
            );
        }

        $state = $this->randomToken();
        $nonce = $this->randomToken();
        $session->set(self::SESSION_STATE_KEY, $state);
        $session->set(self::SESSION_NONCE_KEY, $nonce);

        $query = http_build_query([
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'redirect_uri' => $this->getCallbackUrl(),
            'response_mode' => 'query',
            'scope' => trim($this->scope) !== '' ? $this->scope : 'openid profile email',
            'state' => $state,
            'nonce' => $nonce,
        ]);

        return sprintf('%s/oauth2/v2.0/authorize?%s', $this->authorityBaseUrl(), $query);
    }

    public function authenticateFromCallback(Request $request): SsoIdentity
    {
        if (!$this->isEnabled()) {
            throw new SsoAuthenticationException(
                reasonCode: 'sso_disabled',
                userMessage: 'Le SSO Microsoft 365 n’est pas activé sur ce portail.',
            );
        }

        $session = $request->getSession();
        if ($session === null) {
            throw new SsoAuthenticationException(
                reasonCode: 'session_unavailable',
                userMessage: 'Session expirée pendant la connexion SSO. Merci de recommencer.',
            );
        }

        $state = (string) $request->query->get('state', '');
        $expectedState = (string) $session->get(self::SESSION_STATE_KEY, '');
        $session->remove(self::SESSION_STATE_KEY);

        if ($state === '' || $expectedState === '' || !hash_equals($expectedState, $state)) {
            throw new SsoAuthenticationException(
                reasonCode: 'invalid_state',
                userMessage: 'La validation de sécurité SSO a échoué (state invalide). Recommencez la connexion.',
            );
        }

        if ($request->query->has('error')) {
            $error = (string) $request->query->get('error', 'oauth_error');
            $description = (string) $request->query->get('error_description', 'Erreur OAuth');
            throw new SsoAuthenticationException(
                reasonCode: 'provider_error',
                userMessage: sprintf('Connexion Microsoft refusée (%s).', $error),
                context: [
                    'provider_error' => $error,
                    'provider_error_description' => $description,
                ],
            );
        }

        $code = trim((string) $request->query->get('code', ''));
        if ($code === '') {
            throw new SsoAuthenticationException(
                reasonCode: 'missing_code',
                userMessage: 'Code de retour OAuth manquant. Recommencez la connexion.',
            );
        }

        $tokenPayload = $this->exchangeCodeForToken($code);
        $idToken = (string) ($tokenPayload['id_token'] ?? '');
        if ($idToken === '') {
            throw new SsoAuthenticationException(
                reasonCode: 'missing_id_token',
                userMessage: 'Le token d’identité Microsoft est absent de la réponse OAuth.',
            );
        }

        $expectedNonce = (string) $session->get(self::SESSION_NONCE_KEY, '');
        $session->remove(self::SESSION_NONCE_KEY);

        $claims = $this->validateAndDecodeIdToken($idToken, $expectedNonce);

        $subject = trim((string) ($claims['oid'] ?? $claims['sub'] ?? ''));
        $email = strtolower(trim((string) ($claims['preferred_username'] ?? $claims['email'] ?? $claims['upn'] ?? '')));
        if ($subject === '' || $email === '') {
            throw new SsoAuthenticationException(
                reasonCode: 'identity_claims_missing',
                userMessage: 'Le token Microsoft ne contient pas les informations d’identité minimales (oid/sub + email).',
            );
        }

        $rolesRaw = $claims[$this->roleClaim] ?? [];
        $externalRoles = [];
        if (is_string($rolesRaw) && trim($rolesRaw) !== '') {
            $externalRoles[] = trim($rolesRaw);
        } elseif (is_array($rolesRaw)) {
            foreach ($rolesRaw as $role) {
                if (is_string($role) && trim($role) !== '') {
                    $externalRoles[] = trim($role);
                }
            }
        }
        $externalRoles = array_values(array_unique($externalRoles));

        if ($this->requireRoleClaim && $externalRoles === []) {
            throw new SsoAuthenticationException(
                reasonCode: 'missing_role_claim',
                userMessage: sprintf(
                    'Le token SSO ne contient aucun rôle dans la claim "%s". Contactez l’équipe IT pour vérifier l’attribution des rôles SIG.',
                    $this->roleClaim
                ),
                context: [
                    'role_claim' => $this->roleClaim,
                    'tenant_id' => (string) ($claims['tid'] ?? ''),
                    'subject' => $subject,
                ],
            );
        }

        return new SsoIdentity(
            subject: $subject,
            email: $email,
            firstName: $this->nullableString($claims['given_name'] ?? null),
            lastName: $this->nullableString($claims['family_name'] ?? null),
            displayName: $this->nullableString($claims['name'] ?? null),
            tenantId: (string) ($claims['tid'] ?? ''),
            externalRoles: $externalRoles,
        );
    }

    private function exchangeCodeForToken(string $code): array
    {
        $response = $this->httpClient->request('POST', sprintf('%s/oauth2/v2.0/token', $this->authorityBaseUrl()), [
            'body' => [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $this->getCallbackUrl(),
                'scope' => trim($this->scope) !== '' ? $this->scope : 'openid profile email',
            ],
        ]);

        $payload = $response->toArray(false);
        if ($response->getStatusCode() >= 400) {
            $message = (string) ($payload['error_description'] ?? $payload['error'] ?? 'Erreur token endpoint');
            throw new SsoAuthenticationException(
                reasonCode: 'token_exchange_failed',
                userMessage: 'Échec de l’échange OAuth avec Microsoft.',
                context: [
                    'provider_error' => (string) ($payload['error'] ?? ''),
                    'provider_error_description' => $message,
                    'http_status' => $response->getStatusCode(),
                ],
            );
        }

        return is_array($payload) ? $payload : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateAndDecodeIdToken(string $jwt, string $expectedNonce): array
    {
        [$header, $payload, $signature, $signedContent] = $this->decodeJwt($jwt);

        if (($header['alg'] ?? null) !== 'RS256') {
            throw new SsoAuthenticationException(
                reasonCode: 'invalid_alg',
                userMessage: 'Le token Microsoft utilise un algorithme non supporté.',
            );
        }

        $kid = trim((string) ($header['kid'] ?? ''));
        if ($kid === '') {
            throw new SsoAuthenticationException(
                reasonCode: 'missing_kid',
                userMessage: 'Le token Microsoft est invalide (kid absent).',
            );
        }

        $tenantId = trim((string) ($payload['tid'] ?? ''));
        if ($tenantId === '') {
            throw new SsoAuthenticationException(
                reasonCode: 'missing_tenant',
                userMessage: 'Le token Microsoft ne contient pas le tenant (tid).',
            );
        }

        if (
            $this->normalizedAllowedTenantIds !== []
            && !\in_array(strtolower($tenantId), $this->normalizedAllowedTenantIds, true)
        ) {
            throw new SsoAuthenticationException(
                reasonCode: 'tenant_not_allowed',
                userMessage: 'Le tenant Microsoft de ce compte n’est pas autorisé pour ce portail.',
                context: ['tenant_id' => $tenantId],
            );
        }

        $expectedIssuer = sprintf('https://login.microsoftonline.com/%s/v2.0', $tenantId);
        if (($payload['iss'] ?? null) !== $expectedIssuer) {
            throw new SsoAuthenticationException(
                reasonCode: 'invalid_issuer',
                userMessage: 'Issuer du token Microsoft invalide.',
                context: [
                    'issuer' => (string) ($payload['iss'] ?? ''),
                    'expected_issuer' => $expectedIssuer,
                ],
            );
        }

        if (($payload['aud'] ?? null) !== $this->clientId) {
            throw new SsoAuthenticationException(
                reasonCode: 'invalid_audience',
                userMessage: 'Audience du token Microsoft invalide.',
            );
        }

        $now = time();
        $exp = (int) ($payload['exp'] ?? 0);
        if ($exp <= $now) {
            throw new SsoAuthenticationException(
                reasonCode: 'token_expired',
                userMessage: 'Le token SSO Microsoft est expiré.',
            );
        }

        $nbf = (int) ($payload['nbf'] ?? 0);
        if ($nbf > 0 && $nbf > ($now + 30)) {
            throw new SsoAuthenticationException(
                reasonCode: 'token_not_yet_valid',
                userMessage: 'Le token SSO Microsoft n’est pas encore valide.',
            );
        }

        if ($expectedNonce !== '' && ($payload['nonce'] ?? null) !== $expectedNonce) {
            throw new SsoAuthenticationException(
                reasonCode: 'invalid_nonce',
                userMessage: 'La validation de sécurité SSO a échoué (nonce invalide).',
            );
        }

        $keysResponse = $this->httpClient->request(
            'GET',
            sprintf('https://login.microsoftonline.com/%s/discovery/v2.0/keys', $tenantId)
        );
        $keysPayload = $keysResponse->toArray(false);
        $keys = is_array($keysPayload['keys'] ?? null) ? $keysPayload['keys'] : [];

        $pem = null;
        foreach ($keys as $key) {
            if (!is_array($key) || ($key['kid'] ?? null) !== $kid) {
                continue;
            }

            $x5c = $key['x5c'][0] ?? null;
            if (is_string($x5c) && trim($x5c) !== '') {
                $pem = "-----BEGIN CERTIFICATE-----\n"
                    .chunk_split($x5c, 64, "\n")
                    ."-----END CERTIFICATE-----\n";
                break;
            }
        }

        if ($pem === null) {
            throw new SsoAuthenticationException(
                reasonCode: 'signing_key_not_found',
                userMessage: 'Clé de signature Microsoft introuvable pour ce token.',
                context: ['kid' => $kid, 'tenant_id' => $tenantId],
            );
        }

        $verified = openssl_verify($signedContent, $signature, $pem, OPENSSL_ALGO_SHA256);
        if ($verified !== 1) {
            throw new SsoAuthenticationException(
                reasonCode: 'invalid_signature',
                userMessage: 'Signature du token Microsoft invalide.',
                context: ['kid' => $kid, 'tenant_id' => $tenantId],
            );
        }

        return $payload;
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>, 2: string, 3: string}
     */
    private function decodeJwt(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new SsoAuthenticationException(
                reasonCode: 'invalid_jwt_format',
                userMessage: 'Format du token Microsoft invalide.',
            );
        }

        [$headerPart, $payloadPart, $signaturePart] = $parts;
        $header = json_decode($this->base64UrlDecode($headerPart), true);
        $payload = json_decode($this->base64UrlDecode($payloadPart), true);
        $signature = $this->base64UrlDecode($signaturePart);

        if (!is_array($header) || !is_array($payload)) {
            throw new SsoAuthenticationException(
                reasonCode: 'invalid_jwt_payload',
                userMessage: 'Token Microsoft non lisible.',
            );
        }

        return [$header, $payload, $signature, sprintf('%s.%s', $headerPart, $payloadPart)];
    }

    private function base64UrlDecode(string $value): string
    {
        $base64 = strtr($value, '-_', '+/');
        $padding = strlen($base64) % 4;
        if ($padding > 0) {
            $base64 .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($base64, true);
        if (!is_string($decoded)) {
            throw new SsoAuthenticationException(
                reasonCode: 'invalid_base64url',
                userMessage: 'Token Microsoft mal encodé.',
            );
        }

        return $decoded;
    }

    private function nullableString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function authorityBaseUrl(): string
    {
        return sprintf('https://login.microsoftonline.com/%s', $this->tenantId);
    }

    private function getCallbackUrl(): string
    {
        return $this->urlGenerator->generate('app_sso_callback', [], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    private function randomToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    private function requireCurrentRequest(): Request
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new SsoAuthenticationException(
                reasonCode: 'request_missing',
                userMessage: 'Contexte HTTP indisponible pour le flux SSO.',
            );
        }

        return $request;
    }
}
