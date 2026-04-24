<?php

declare(strict_types=1);

namespace App\Domain\Cartography\Entity;

use App\Domain\Common\Entity\Traits\IdentifierTrait;
use App\Domain\Common\Entity\Traits\MetadataTrait;
use App\Domain\Common\Entity\Traits\TimestampableTrait;
use App\Infrastructure\Repository\StaticMapAssetRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StaticMapAssetRepository::class)]
#[ORM\Table(name: 'static_map_asset')]
#[ORM\HasLifecycleCallbacks]
class StaticMapAsset
{
    use IdentifierTrait;
    use TimestampableTrait;
    use MetadataTrait;

    #[ORM\ManyToOne(targetEntity: StaticMap::class, inversedBy: 'assets')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?StaticMap $staticMap = null;

    #[ORM\Column(type: Types::STRING, length: 160)]
    private string $label = '';

    #[ORM\Column(type: Types::STRING, length: 32)]
    private string $assetType = 'pdf';

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $filePath = '';

    #[ORM\Column(type: Types::STRING, length: 120, nullable: true)]
    private ?string $mimeType = null;

    #[ORM\Column(type: Types::BIGINT, nullable: true)]
    private ?int $fileSize = null;

    public function getStaticMap(): ?StaticMap
    {
        return $this->staticMap;
    }

    public function setStaticMap(?StaticMap $staticMap): static
    {
        $this->staticMap = $staticMap;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getAssetType(): string
    {
        return $this->assetType;
    }

    public function setAssetType(string $assetType): static
    {
        $this->assetType = $assetType;

        return $this;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function setFilePath(string $filePath): static
    {
        $this->filePath = $filePath;

        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): static
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function setFileSize(?int $fileSize): static
    {
        $this->fileSize = $fileSize;

        return $this;
    }

    public function __toString(): string
    {
        return $this->label;
    }
}
