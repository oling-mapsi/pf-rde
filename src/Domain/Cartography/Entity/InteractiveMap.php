<?php

declare(strict_types=1);

namespace App\Domain\Cartography\Entity;

use App\Domain\Access\VisibilityScope;
use App\Domain\Common\Entity\Traits\BlameableTrait;
use App\Domain\Common\Entity\Traits\IdentifierTrait;
use App\Domain\Common\Entity\Traits\MetadataTrait;
use App\Domain\Common\Entity\Traits\PublishableTrait;
use App\Domain\Common\Entity\Traits\TimestampableTrait;
use App\Infrastructure\Repository\InteractiveMapRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InteractiveMapRepository::class)]
#[ORM\Table(name: 'interactive_map')]
#[ORM\UniqueConstraint(name: 'uniq_interactive_map_slug', columns: ['slug'])]
#[ORM\HasLifecycleCallbacks]
class InteractiveMap
{
    use IdentifierTrait;
    use TimestampableTrait;
    use PublishableTrait;
    use MetadataTrait;
    use BlameableTrait;

    #[ORM\Column(type: Types::STRING, length: 180)]
    private string $slug = '';

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $summary = null;

    #[ORM\Column(type: Types::FLOAT, options: ['default' => 16.265])]
    private float $defaultCenterLat = 16.265;

    #[ORM\Column(type: Types::FLOAT, options: ['default' => -61.551])]
    private float $defaultCenterLng = -61.551;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 11])]
    private int $defaultZoom = 11;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $degradedModeMessage = null;

    #[ORM\Column(type: Types::STRING, length: 16, options: ['default' => VisibilityScope::PUBLIC])]
    private string $visibilityScope = VisibilityScope::PUBLIC;

    /** @var Collection<int, MapLayer> */
    #[ORM\OneToMany(mappedBy: 'interactiveMap', targetEntity: MapLayer::class, cascade: ['persist', 'remove'])]
    private Collection $layers;

    public function __construct()
    {
        $this->layers = new ArrayCollection();
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

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

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(?string $summary): static
    {
        $this->summary = $summary;

        return $this;
    }

    public function getDefaultCenterLat(): float
    {
        return $this->defaultCenterLat;
    }

    public function setDefaultCenterLat(float $defaultCenterLat): static
    {
        $this->defaultCenterLat = $defaultCenterLat;

        return $this;
    }

    public function getDefaultCenterLng(): float
    {
        return $this->defaultCenterLng;
    }

    public function setDefaultCenterLng(float $defaultCenterLng): static
    {
        $this->defaultCenterLng = $defaultCenterLng;

        return $this;
    }

    public function getDefaultZoom(): int
    {
        return $this->defaultZoom;
    }

    public function setDefaultZoom(int $defaultZoom): static
    {
        $this->defaultZoom = $defaultZoom;

        return $this;
    }

    public function getDegradedModeMessage(): ?string
    {
        return $this->degradedModeMessage;
    }

    public function setDegradedModeMessage(?string $degradedModeMessage): static
    {
        $this->degradedModeMessage = $degradedModeMessage;

        return $this;
    }

    public function getVisibilityScope(): string
    {
        return $this->visibilityScope;
    }

    public function setVisibilityScope(string $visibilityScope): static
    {
        $this->visibilityScope = strtolower(trim($visibilityScope));

        return $this;
    }

    /** @return Collection<int, MapLayer> */
    public function getLayers(): Collection
    {
        return $this->layers;
    }

    public function addLayer(MapLayer $layer): static
    {
        if (!$this->layers->contains($layer)) {
            $this->layers->add($layer);
            $layer->setInteractiveMap($this);
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->title;
    }
}
