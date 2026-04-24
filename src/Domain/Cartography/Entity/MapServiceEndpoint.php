<?php

declare(strict_types=1);

namespace App\Domain\Cartography\Entity;

use App\Domain\Common\Entity\Traits\IdentifierTrait;
use App\Domain\Common\Entity\Traits\MetadataTrait;
use App\Domain\Common\Entity\Traits\TimestampableTrait;
use App\Infrastructure\Repository\MapServiceEndpointRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MapServiceEndpointRepository::class)]
#[ORM\Table(name: 'map_service_endpoint')]
#[ORM\HasLifecycleCallbacks]
class MapServiceEndpoint
{
    use IdentifierTrait;
    use TimestampableTrait;
    use MetadataTrait;

    #[ORM\Column(type: Types::STRING, length: 180)]
    private string $name = '';

    #[ORM\Column(type: Types::STRING, length: 32)]
    private string $serviceType = 'wms';

    #[ORM\Column(type: Types::STRING, length: 512)]
    private string $baseUrl = '';

    #[ORM\Column(type: Types::STRING, length: 32, options: ['default' => 'none'])]
    private string $authType = 'none';

    #[ORM\Column(type: Types::STRING, length: 160, nullable: true)]
    private ?string $credentialsRef = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $enabled = true;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 3000])]
    private int $timeoutMs = 3000;

    #[ORM\Column(type: Types::STRING, length: 16, options: ['default' => 'unknown'])]
    private string $healthStatus = 'unknown';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastCheckedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $lastError = null;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getServiceType(): string
    {
        return $this->serviceType;
    }

    public function setServiceType(string $serviceType): static
    {
        $this->serviceType = strtolower($serviceType);

        return $this;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function setBaseUrl(string $baseUrl): static
    {
        $this->baseUrl = $baseUrl;

        return $this;
    }

    public function getAuthType(): string
    {
        return $this->authType;
    }

    public function setAuthType(string $authType): static
    {
        $this->authType = strtolower($authType);

        return $this;
    }

    public function getCredentialsRef(): ?string
    {
        return $this->credentialsRef;
    }

    public function setCredentialsRef(?string $credentialsRef): static
    {
        $this->credentialsRef = $credentialsRef;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): static
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getTimeoutMs(): int
    {
        return $this->timeoutMs;
    }

    public function setTimeoutMs(int $timeoutMs): static
    {
        $this->timeoutMs = $timeoutMs;

        return $this;
    }

    public function getHealthStatus(): string
    {
        return $this->healthStatus;
    }

    public function setHealthStatus(string $healthStatus): static
    {
        $this->healthStatus = $healthStatus;

        return $this;
    }

    public function getLastCheckedAt(): ?\DateTimeImmutable
    {
        return $this->lastCheckedAt;
    }

    public function setLastCheckedAt(?\DateTimeImmutable $lastCheckedAt): static
    {
        $this->lastCheckedAt = $lastCheckedAt;

        return $this;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function setLastError(?string $lastError): static
    {
        $this->lastError = $lastError;

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
