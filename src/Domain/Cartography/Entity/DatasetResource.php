<?php

declare(strict_types=1);

namespace App\Domain\Cartography\Entity;

use App\Domain\Common\Entity\Traits\IdentifierTrait;
use App\Domain\Common\Entity\Traits\MetadataTrait;
use App\Domain\Common\Entity\Traits\TimestampableTrait;
use App\Infrastructure\Repository\DatasetResourceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DatasetResourceRepository::class)]
#[ORM\Table(name: 'dataset_resource')]
#[ORM\HasLifecycleCallbacks]
class DatasetResource
{
    use IdentifierTrait;
    use TimestampableTrait;
    use MetadataTrait;

    #[ORM\ManyToOne(targetEntity: StaticMap::class, inversedBy: 'datasetResources')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?StaticMap $staticMap = null;

    #[ORM\Column(type: Types::STRING, length: 160)]
    private string $label = '';

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $format = '';

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $external = false;

    #[ORM\Column(type: Types::STRING, length: 512, nullable: true)]
    private ?string $url = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $filePath = null;

    #[ORM\Column(type: Types::STRING, length: 160, nullable: true)]
    private ?string $license = null;

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

    public function getFormat(): string
    {
        return $this->format;
    }

    public function setFormat(string $format): static
    {
        $this->format = strtolower($format);

        return $this;
    }

    public function isExternal(): bool
    {
        return $this->external;
    }

    public function setExternal(bool $external): static
    {
        $this->external = $external;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(?string $filePath): static
    {
        $this->filePath = $filePath;

        return $this;
    }

    public function getLicense(): ?string
    {
        return $this->license;
    }

    public function setLicense(?string $license): static
    {
        $this->license = $license;

        return $this;
    }

    public function __toString(): string
    {
        return $this->label;
    }
}
