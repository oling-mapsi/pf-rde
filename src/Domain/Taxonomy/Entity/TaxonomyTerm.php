<?php

declare(strict_types=1);

namespace App\Domain\Taxonomy\Entity;

use App\Domain\Common\Entity\Traits\IdentifierTrait;
use App\Domain\Common\Entity\Traits\MetadataTrait;
use App\Domain\Common\Entity\Traits\TimestampableTrait;
use App\Infrastructure\Repository\TaxonomyTermRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TaxonomyTermRepository::class)]
#[ORM\Table(name: 'taxonomy_term')]
#[ORM\UniqueConstraint(name: 'uniq_taxonomy_slug', columns: ['taxonomy', 'slug'])]
#[ORM\HasLifecycleCallbacks]
class TaxonomyTerm
{
    public const MAP_THEME_TAXONOMY = 'map_theme';

    public const ICON_CHOICES = [
        'Map - Cartographie' => 'map',
        'Layers - Couches' => 'layers',
        'Database - Donnees' => 'database',
        'Chart - Indicateurs' => 'chart',
        'Business - Activite' => 'business',
        'Briefcase - Metier' => 'briefcase',
        'Building - Institution' => 'building',
        'Handshake - Partenariat' => 'handshake',
        'Digital - Numerique' => 'digital',
        'SI - Systeme d information' => 'si',
        'SIG - Geomatique' => 'sig',
        'Network - Reseau' => 'network',
        'Cloud - Cloud' => 'cloud',
        'Server - Serveur' => 'server',
        'Code - Developpement' => 'code',
        'Route - Reseau routier' => 'route',
        'Roadwork - Travaux' => 'roadwork',
        'Transport - Mobilite' => 'transport',
        'Truck - Camion' => 'truck',
        'Car - Voiture' => 'car',
        'Bus - Bus' => 'bus',
        'Traffic - Trafic' => 'traffic',
        'Bridge - Ouvrage' => 'bridge',
        'Cone - Signalisation' => 'cone',
        'Map Pin - Localisation' => 'map-pin',
        'Satellite - Imagerie' => 'satellite',
        'Compass - Orientation' => 'compass',
        'Clipboard - Reporting' => 'clipboard',
        'Wrench - Maintenance' => 'wrench',
        'Globe - Territoire' => 'globe',
        'Search - Recherche' => 'search',
        'Download - Telechargement' => 'download',
    ];

    use IdentifierTrait;
    use TimestampableTrait;
    use MetadataTrait;

    #[ORM\Column(type: Types::STRING, length: 80)]
    private string $taxonomy = '';

    #[ORM\Column(type: Types::STRING, length: 180)]
    private string $slug = '';

    #[ORM\Column(type: Types::STRING, length: 180)]
    private string $label = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(onDelete: 'SET NULL', nullable: true)]
    private ?self $parent = null;

    /** @var Collection<int, self> */
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    private Collection $children;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $active = true;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    public function getTaxonomy(): string
    {
        return $this->taxonomy;
    }

    public function setTaxonomy(string $taxonomy): static
    {
        $this->taxonomy = $taxonomy;

        return $this;
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

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function getIconKey(): string
    {
        $icon = $this->readMetadataString('iconKey');

        if ($icon === '' || !\in_array($icon, self::allowedIconKeys(), true)) {
            return 'map';
        }

        return $icon;
    }

    public function setIconKey(?string $iconKey): static
    {
        $icon = strtolower(trim((string) $iconKey));
        if ($icon === '' || !\in_array($icon, self::allowedIconKeys(), true)) {
            $icon = 'map';
        }

        return $this->writeMetadataValue('iconKey', $icon);
    }

    public function getColorHex(): string
    {
        return $this->getStoredColorHex() ?? '#3CB4DF';
    }

    public function setColorHex(?string $colorHex): static
    {
        $color = strtoupper(trim((string) $colorHex));
        if ($color === '') {
            $color = '#3CB4DF';
        }
        if (!str_starts_with($color, '#')) {
            $color = '#'.$color;
        }
        if (!preg_match('/^#[0-9A-F]{6}$/', $color)) {
            $color = '#3CB4DF';
        }

        return $this->writeMetadataValue('colorHex', $color);
    }

    public function getStoredColorHex(): ?string
    {
        $color = strtoupper($this->readMetadataString('colorHex'));
        if ($color === '') {
            return null;
        }
        if (!str_starts_with($color, '#')) {
            $color = '#'.$color;
        }
        if (!preg_match('/^#[0-9A-F]{6}$/', $color)) {
            return null;
        }
        if ($color === '#3CB4DF') {
            return null;
        }

        return $color;
    }

    public function isFeaturedOnHomepage(): bool
    {
        $value = $this->readMetadataValue('featuredOnHomepage');

        return \is_bool($value) ? $value : false;
    }

    public function setFeaturedOnHomepage(bool $featuredOnHomepage): static
    {
        return $this->writeMetadataValue('featuredOnHomepage', $featuredOnHomepage);
    }

    public function getPosition(): int
    {
        $value = $this->readMetadataValue('position');
        if (\is_int($value)) {
            return $value;
        }
        if (\is_string($value) && is_numeric($value)) {
            return (int) $value;
        }

        return 0;
    }

    public function setPosition(int $position): static
    {
        return $this->writeMetadataValue('position', $position);
    }

    public function __toString(): string
    {
        return $this->label;
    }

    /** @return list<string> */
    public static function allowedIconKeys(): array
    {
        return array_values(self::ICON_CHOICES);
    }

    private function readMetadataString(string $key): string
    {
        $value = $this->readMetadataValue($key);

        return \is_string($value) ? trim($value) : '';
    }

    private function readMetadataValue(string $key): mixed
    {
        return $this->metadata[$key] ?? null;
    }

    private function writeMetadataValue(string $key, mixed $value): static
    {
        $metadata = $this->metadata ?? [];
        $metadata[$key] = $value;
        $this->metadata = $metadata;

        return $this;
    }
}
