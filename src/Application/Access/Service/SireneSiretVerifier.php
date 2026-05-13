<?php

declare(strict_types=1);

namespace App\Application\Access\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class SireneSiretVerifier
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly bool $enabled,
        private readonly string $endpointTemplate,
        private readonly string $apiToken,
        private readonly float $timeoutSeconds = 8.0,
    ) {
    }

    public function validate(string $rawSiret): SiretValidationResult
    {
        $normalizedSiret = preg_replace('/\D+/', '', $rawSiret) ?? '';

        if ($normalizedSiret === '' || strlen($normalizedSiret) !== 14) {
            return SiretValidationResult::failure($normalizedSiret, 'Le SIRET doit contenir exactement 14 chiffres.');
        }

        if (!$this->isLuhnValid($normalizedSiret)) {
            return SiretValidationResult::failure($normalizedSiret, 'Le SIRET est invalide (clé de contrôle incorrecte).');
        }

        if (!$this->enabled) {
            return SiretValidationResult::failure(
                $normalizedSiret,
                'La vérification SIRENE est indisponible. Contactez le support.'
            );
        }

        if (trim($this->apiToken) === '') {
            return SiretValidationResult::failure(
                $normalizedSiret,
                'Le service de vérification SIRENE n’est pas configuré (token manquant).'
            );
        }

        $url = str_replace('{siret}', $normalizedSiret, $this->endpointTemplate);

        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => sprintf('Bearer %s', $this->apiToken),
                ],
                'timeout' => max(1.0, $this->timeoutSeconds),
            ]);

            $status = $response->getStatusCode();
            $payload = $response->toArray(false);

            if ($status === 404) {
                return SiretValidationResult::failure($normalizedSiret, 'SIRET introuvable dans la base SIRENE.');
            }

            if ($status >= 400) {
                $this->logger->warning('SIRENE verification failed', [
                    'siret' => $normalizedSiret,
                    'status' => $status,
                    'payload' => is_array($payload) ? $payload : null,
                ]);

                return SiretValidationResult::failure(
                    $normalizedSiret,
                    'Impossible de vérifier le SIRET sur SIRENE. Réessayez plus tard.'
                );
            }

            $companyName = $this->extractCompanyName(is_array($payload) ? $payload : []);

            return SiretValidationResult::success($normalizedSiret, $companyName);
        } catch (TransportExceptionInterface|DecodingExceptionInterface $exception) {
            $this->logger->error('SIRENE verification request failed', [
                'siret' => $normalizedSiret,
                'message' => $exception->getMessage(),
            ]);

            return SiretValidationResult::failure(
                $normalizedSiret,
                'Service SIRENE temporairement indisponible. Réessayez plus tard.'
            );
        }
    }

    private function isLuhnValid(string $value): bool
    {
        $sum = 0;
        $shouldDouble = false;

        for ($index = strlen($value) - 1; $index >= 0; --$index) {
            $digit = (int) $value[$index];
            if ($shouldDouble) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
            $shouldDouble = !$shouldDouble;
        }

        return $sum % 10 === 0;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function extractCompanyName(array $payload): ?string
    {
        $etablissement = $payload['etablissement'] ?? null;
        if (!is_array($etablissement)) {
            return null;
        }

        $uniteLegale = $etablissement['uniteLegale'] ?? null;
        if (!is_array($uniteLegale)) {
            return null;
        }

        $nameCandidates = [
            $uniteLegale['denominationUniteLegale'] ?? null,
            $uniteLegale['nomUniteLegale'] ?? null,
            $uniteLegale['nomUsageUniteLegale'] ?? null,
        ];

        foreach ($nameCandidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return null;
    }
}
