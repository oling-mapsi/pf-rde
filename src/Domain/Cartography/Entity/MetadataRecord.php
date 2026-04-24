<?php

declare(strict_types=1);

namespace App\Domain\Cartography\Entity;

use App\Domain\Common\Entity\Traits\IdentifierTrait;
use App\Domain\Common\Entity\Traits\MetadataTrait;
use App\Domain\Common\Entity\Traits\TimestampableTrait;
use App\Infrastructure\Repository\MetadataRecordRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MetadataRecordRepository::class)]
#[ORM\Table(name: 'metadata_record')]
#[ORM\UniqueConstraint(name: 'uniq_metadata_identifier', columns: ['identifier'])]
#[ORM\HasLifecycleCallbacks]
class MetadataRecord
{
    use IdentifierTrait;
    use TimestampableTrait;
    use MetadataTrait;

    #[ORM\Column(type: Types::STRING, length: 180)]
    private string $identifier = '';

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $abstractText = null;

    /** @var array<int, string>|null */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $keywords = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $rawPayload = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastSyncedAt = null;

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): static
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getAbstractText(): ?string
    {
        return $this->abstractText;
    }

    public function setAbstractText(?string $abstractText): static
    {
        $this->abstractText = $abstractText;

        return $this;
    }

    /** @return array<int, string>|null */
    public function getKeywords(): ?array
    {
        return $this->keywords;
    }

    /** @param array<int, string>|null $keywords */
    public function setKeywords(?array $keywords): static
    {
        $this->keywords = $keywords;

        return $this;
    }

    /** @return array<string, mixed>|null */
    public function getRawPayload(): ?array
    {
        return $this->rawPayload;
    }

    /** @param array<string, mixed>|null $rawPayload */
    public function setRawPayload(?array $rawPayload): static
    {
        $this->rawPayload = $rawPayload;

        return $this;
    }

    public function getLastSyncedAt(): ?\DateTimeImmutable
    {
        return $this->lastSyncedAt;
    }

    public function setLastSyncedAt(?\DateTimeImmutable $lastSyncedAt): static
    {
        $this->lastSyncedAt = $lastSyncedAt;

        return $this;
    }

    public function __toString(): string
    {
        return $this->title;
    }
}
