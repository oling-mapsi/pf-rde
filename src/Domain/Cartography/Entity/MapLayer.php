<?php

declare(strict_types=1);

namespace App\Domain\Cartography\Entity;

use App\Domain\Common\Entity\Traits\IdentifierTrait;
use App\Domain\Common\Entity\Traits\MetadataTrait;
use App\Domain\Common\Entity\Traits\TimestampableTrait;
use App\Infrastructure\Repository\MapLayerRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MapLayerRepository::class)]
#[ORM\Table(name: 'map_layer')]
#[ORM\HasLifecycleCallbacks]
class MapLayer
{
    use IdentifierTrait;
    use TimestampableTrait;
    use MetadataTrait;

    #[ORM\ManyToOne(targetEntity: InteractiveMap::class, inversedBy: 'layers')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?InteractiveMap $interactiveMap = null;

    #[ORM\ManyToOne(targetEntity: MapServiceEndpoint::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL', nullable: true)]
    private ?MapServiceEndpoint $endpoint = null;

    #[ORM\Column(type: Types::STRING, length: 120)]
    private string $name = '';

    #[ORM\Column(type: Types::STRING, length: 180)]
    private string $label = '';

    #[ORM\Column(type: Types::STRING, length: 180, nullable: true)]
    private ?string $serviceLayerName = null;

    #[ORM\Column(type: Types::STRING, length: 40, options: ['default' => 'wms'])]
    private string $layerType = 'wms';

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $renderOrder = 0;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $visibleByDefault = true;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $styleConfig = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $filterConfig = null;

    public function getInteractiveMap(): ?InteractiveMap
    {
        return $this->interactiveMap;
    }

    public function setInteractiveMap(?InteractiveMap $interactiveMap): static
    {
        $this->interactiveMap = $interactiveMap;

        return $this;
    }

    public function getEndpoint(): ?MapServiceEndpoint
    {
        return $this->endpoint;
    }

    public function setEndpoint(?MapServiceEndpoint $endpoint): static
    {
        $this->endpoint = $endpoint;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

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

    public function getServiceLayerName(): ?string
    {
        return $this->serviceLayerName;
    }

    public function setServiceLayerName(?string $serviceLayerName): static
    {
        $this->serviceLayerName = $serviceLayerName;

        return $this;
    }

    public function getLayerType(): string
    {
        return $this->layerType;
    }

    public function setLayerType(string $layerType): static
    {
        $this->layerType = strtolower($layerType);

        return $this;
    }

    public function getRenderOrder(): int
    {
        return $this->renderOrder;
    }

    public function setRenderOrder(int $renderOrder): static
    {
        $this->renderOrder = $renderOrder;

        return $this;
    }

    public function isVisibleByDefault(): bool
    {
        return $this->visibleByDefault;
    }

    public function setVisibleByDefault(bool $visibleByDefault): static
    {
        $this->visibleByDefault = $visibleByDefault;

        return $this;
    }

    /** @return array<string, mixed>|null */
    public function getStyleConfig(): ?array
    {
        return $this->styleConfig;
    }

    /** @param array<string, mixed>|null $styleConfig */
    public function setStyleConfig(?array $styleConfig): static
    {
        $this->styleConfig = $styleConfig;

        return $this;
    }

    /** @return array<string, mixed>|null */
    public function getFilterConfig(): ?array
    {
        return $this->filterConfig;
    }

    /** @param array<string, mixed>|null $filterConfig */
    public function setFilterConfig(?array $filterConfig): static
    {
        $this->filterConfig = $filterConfig;

        return $this;
    }

    public function __toString(): string
    {
        return $this->label;
    }
}
