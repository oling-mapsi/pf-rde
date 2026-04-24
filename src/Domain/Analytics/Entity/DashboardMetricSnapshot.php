<?php

declare(strict_types=1);

namespace App\Domain\Analytics\Entity;

use App\Domain\Common\Entity\Traits\IdentifierTrait;
use App\Domain\Common\Entity\Traits\MetadataTrait;
use App\Domain\Common\Entity\Traits\TimestampableTrait;
use App\Infrastructure\Repository\DashboardMetricSnapshotRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DashboardMetricSnapshotRepository::class)]
#[ORM\Table(name: 'dashboard_metric_snapshot')]
#[ORM\Index(name: 'idx_dashboard_metric_key_recorded', columns: ['metric_key', 'recorded_at'])]
#[ORM\HasLifecycleCallbacks]
class DashboardMetricSnapshot
{
    use IdentifierTrait;
    use TimestampableTrait;
    use MetadataTrait;

    #[ORM\Column(type: Types::STRING, length: 120, name: 'metric_key')]
    private string $metricKey = '';

    #[ORM\Column(type: Types::STRING, length: 80, nullable: true)]
    private ?string $scope = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'recorded_at')]
    private \DateTimeImmutable $recordedAt;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?string $valueNumeric = null;

    #[ORM\Column(type: Types::BIGINT, nullable: true)]
    private ?int $valueInteger = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $payload = null;

    public function __construct()
    {
        $this->recordedAt = new \DateTimeImmutable();
    }

    public function getMetricKey(): string
    {
        return $this->metricKey;
    }

    public function setMetricKey(string $metricKey): static
    {
        $this->metricKey = $metricKey;

        return $this;
    }

    public function getScope(): ?string
    {
        return $this->scope;
    }

    public function setScope(?string $scope): static
    {
        $this->scope = $scope;

        return $this;
    }

    public function getRecordedAt(): \DateTimeImmutable
    {
        return $this->recordedAt;
    }

    public function setRecordedAt(\DateTimeImmutable $recordedAt): static
    {
        $this->recordedAt = $recordedAt;

        return $this;
    }

    public function getValueNumeric(): ?string
    {
        return $this->valueNumeric;
    }

    public function setValueNumeric(?string $valueNumeric): static
    {
        $this->valueNumeric = $valueNumeric;

        return $this;
    }

    public function getValueInteger(): ?int
    {
        return $this->valueInteger;
    }

    public function setValueInteger(?int $valueInteger): static
    {
        $this->valueInteger = $valueInteger;

        return $this;
    }

    /** @return array<string, mixed>|null */
    public function getPayload(): ?array
    {
        return $this->payload;
    }

    /** @param array<string, mixed>|null $payload */
    public function setPayload(?array $payload): static
    {
        $this->payload = $payload;

        return $this;
    }
}
