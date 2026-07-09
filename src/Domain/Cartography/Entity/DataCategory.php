<?php

declare(strict_types=1);

namespace App\Domain\Cartography\Entity;

use App\Domain\Common\Entity\Traits\BlameableTrait;
use App\Domain\Common\Entity\Traits\IdentifierTrait;
use App\Domain\Common\Entity\Traits\PublishableTrait;
use App\Domain\Common\Entity\Traits\TimestampableTrait;
use App\Infrastructure\Repository\DataCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DataCategoryRepository::class)]
#[ORM\Table(name: 'data_category')]
#[ORM\UniqueConstraint(name: 'uniq_data_category_slug', columns: ['slug'])]
#[ORM\Index(name: 'idx_data_category_status_featured_position', columns: ['status', 'featured_on_homepage', 'position'])]
#[ORM\HasLifecycleCallbacks]
class DataCategory
{
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
    use PublishableTrait;
    use BlameableTrait;

    #[Assert\NotBlank]
    #[ORM\Column(type: Types::STRING, length: 160)]
    private string $name = '';

    #[Assert\NotBlank]
    #[ORM\Column(type: Types::STRING, length: 180)]
    private string $slug = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[Assert\Choice(callback: [self::class, 'allowedIconKeys'])]
    #[ORM\Column(type: Types::STRING, length: 64, options: ['default' => 'map'])]
    private string $iconKey = 'map';

    #[Assert\Regex(pattern: '/^#[0-9A-Fa-f]{6}$/', message: 'Veuillez renseigner une couleur HEX valide (ex: #3CB4DF).')]
    #[ORM\Column(type: Types::STRING, length: 7, options: ['default' => '#3CB4DF'])]
    private string $colorHex = '#3CB4DF';

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $featuredOnHomepage = false;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $position = 0;

    /** @var Collection<int, DataSource> */
    #[ORM\ManyToMany(targetEntity: DataSource::class, mappedBy: 'categories')]
    private Collection $sources;

    public function __construct()
    {
        $this->sources = new ArrayCollection();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = trim($name);

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = trim($slug);

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description !== null ? trim($description) : null;

        return $this;
    }

    public function getIconKey(): string
    {
        return $this->iconKey;
    }

    public function setIconKey(string $iconKey): static
    {
        $this->iconKey = strtolower(trim($iconKey));

        return $this;
    }

    public function getColorHex(): string
    {
        return $this->getStoredColorHex() ?? '#3CB4DF';
    }

    public function setColorHex(string $colorHex): static
    {
        $normalized = strtoupper(trim($colorHex));
        if ($normalized === '') {
            $normalized = '#3CB4DF';
        }
        if ($normalized[0] !== '#') {
            $normalized = '#'.$normalized;
        }

        $this->colorHex = $normalized;

        return $this;
    }

    public function getStoredColorHex(): ?string
    {
        $color = strtoupper(trim($this->colorHex));
        if ($color === '') {
            return null;
        }
        if ($color[0] !== '#') {
            $color = '#'.$color;
        }
        if ($color === '#3CB4DF') {
            return null;
        }

        return preg_match('/^#[0-9A-F]{6}$/', $color) === 1 ? $color : null;
    }

    public function isFeaturedOnHomepage(): bool
    {
        return $this->featuredOnHomepage;
    }

    public function setFeaturedOnHomepage(bool $featuredOnHomepage): static
    {
        $this->featuredOnHomepage = $featuredOnHomepage;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    /** @return Collection<int, DataSource> */
    public function getSources(): Collection
    {
        return $this->sources;
    }

    public function addSource(DataSource $source): static
    {
        if (!$this->sources->contains($source)) {
            $this->sources->add($source);
            $source->addCategory($this);
        }

        return $this;
    }

    public function removeSource(DataSource $source): static
    {
        if ($this->sources->removeElement($source)) {
            $source->removeCategory($this);
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }

    /** @return list<string> */
    public static function allowedIconKeys(): array
    {
        return array_values(self::ICON_CHOICES);
    }
}
