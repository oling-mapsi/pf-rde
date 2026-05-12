<?php

declare(strict_types=1);

namespace App\Domain\Cartography\Entity;

use App\Domain\Access\VisibilityScope;
use App\Domain\Common\Entity\Traits\BlameableTrait;
use App\Domain\Common\Entity\Traits\IdentifierTrait;
use App\Domain\Common\Entity\Traits\MetadataTrait;
use App\Domain\Common\Entity\Traits\PublishableTrait;
use App\Domain\Common\Entity\Traits\TimestampableTrait;
use App\Infrastructure\Repository\DataSourceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: DataSourceRepository::class)]
#[ORM\Table(name: 'data_source')]
#[ORM\UniqueConstraint(name: 'uniq_data_source_slug', columns: ['slug'])]
#[ORM\Index(name: 'idx_data_source_type_status', columns: ['source_type', 'status'])]
#[ORM\Index(name: 'idx_data_source_theme', columns: ['theme'])]
#[ORM\HasLifecycleCallbacks]
class DataSource
{
    use IdentifierTrait;
    use TimestampableTrait;
    use PublishableTrait;
    use MetadataTrait;
    use BlameableTrait;

    public const TYPE_CARTOGRAPHY_LINK = 'cartography_link';
    public const TYPE_WMS = 'wms';
    public const TYPE_WFS = 'wfs';
    public const TYPE_DATA_FILE = 'data_file';
    public const TYPE_STATIC_MAP = 'static_map';

    public const TYPE_LABELS = [
        self::TYPE_CARTOGRAPHY_LINK => 'Lien vers cartographie',
        self::TYPE_WMS => 'WMS',
        self::TYPE_WFS => 'WFS',
        self::TYPE_DATA_FILE => 'Fichier de données',
        self::TYPE_STATIC_MAP => 'Carte statique',
    ];

    public const TYPE_CHOICES = [
        'Lien vers cartographie' => self::TYPE_CARTOGRAPHY_LINK,
        'WMS' => self::TYPE_WMS,
        'WFS' => self::TYPE_WFS,
        'Fichier de données' => self::TYPE_DATA_FILE,
        'Carte statique' => self::TYPE_STATIC_MAP,
    ];

    public const ICON_CHOICES = [
        'Map - Cartographie' => 'map',
        'Layers - Couches' => 'layers',
        'Database - Donnees' => 'database',
        'File - Fichier generique' => 'file',
        'File Excel - Tableur' => 'file-excel',
        'File CSV - Donnees tabulaires' => 'file-csv',
        'File JSON - Donnees structurees' => 'file-json',
        'File PDF - Document' => 'file-pdf',
        'Link - Lien externe' => 'link',
        'Globe - Portail web' => 'globe',
        'Satellite - Imagerie' => 'satellite',
        'Road - Reseau routier' => 'road',
        'Truck - Transport lourd' => 'truck',
        'Bus - Transport collectif' => 'bus',
        'Car - Mobilite' => 'car',
        'Ship - Maritime' => 'ship',
        'Shield - Reglementaire' => 'shield',
        'Chart - Indicateurs' => 'chart-line',
        'Location - Geolocalisation' => 'location-dot',
        'Download - Telechargement' => 'download',
    ];

    #[ORM\Column(type: Types::STRING, length: 180)]
    private string $slug = '';

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $title = '';

    #[ORM\Column(type: Types::STRING, length: 40, options: ['default' => self::TYPE_DATA_FILE])]
    private string $sourceType = self::TYPE_DATA_FILE;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $summary = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING, length: 120, nullable: true)]
    private ?string $theme = null;

    /** @var array<int, string>|null */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $keywords = null;

    #[ORM\Column(type: Types::STRING, length: 512, nullable: true)]
    private ?string $sourceUrl = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $filePath = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $format = null;

    #[ORM\Column(type: Types::STRING, length: 160, nullable: true)]
    private ?string $license = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $thumbnailPath = null;

    #[ORM\Column(type: Types::STRING, length: 64, nullable: true)]
    private ?string $iconKey = null;

    #[ORM\Column(type: Types::STRING, length: 16, options: ['default' => VisibilityScope::PUBLIC])]
    private string $visibilityScope = VisibilityScope::PUBLIC;

    #[ORM\ManyToOne(targetEntity: StaticMap::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL', nullable: true)]
    private ?StaticMap $linkedStaticMap = null;

    #[ORM\ManyToOne(targetEntity: InteractiveMap::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL', nullable: true)]
    private ?InteractiveMap $linkedInteractiveMap = null;

    #[ORM\ManyToOne(targetEntity: MapServiceEndpoint::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL', nullable: true)]
    private ?MapServiceEndpoint $serviceEndpoint = null;

    /** @var Collection<int, DataCategory> */
    #[ORM\ManyToMany(targetEntity: DataCategory::class, inversedBy: 'sources')]
    #[ORM\JoinTable(name: 'data_source_category')]
    #[ORM\JoinColumn(name: 'data_source_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'data_category_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Collection $categories;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
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

    public function getSourceType(): string
    {
        return $this->sourceType;
    }

    public function setSourceType(string $sourceType): static
    {
        $this->sourceType = strtolower($sourceType);

        return $this;
    }

    public function getSourceTypeLabel(): string
    {
        return self::TYPE_LABELS[$this->sourceType] ?? 'Source de données';
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getTheme(): ?string
    {
        return $this->theme;
    }

    public function setTheme(?string $theme): static
    {
        $this->theme = $theme;

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

    public function getSourceUrl(): ?string
    {
        return $this->sourceUrl;
    }

    public function setSourceUrl(?string $sourceUrl): static
    {
        $this->sourceUrl = $sourceUrl;

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

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function setFormat(?string $format): static
    {
        $this->format = $format !== null ? strtolower($format) : null;

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

    public function getThumbnailPath(): ?string
    {
        return $this->thumbnailPath;
    }

    public function setThumbnailPath(?string $thumbnailPath): static
    {
        $this->thumbnailPath = $thumbnailPath;

        return $this;
    }

    public function getIconKey(): ?string
    {
        return $this->iconKey;
    }

    public function setIconKey(?string $iconKey): static
    {
        $this->iconKey = $iconKey;

        return $this;
    }

    public function getLinkedStaticMap(): ?StaticMap
    {
        return $this->linkedStaticMap;
    }

    public function setLinkedStaticMap(?StaticMap $linkedStaticMap): static
    {
        $this->linkedStaticMap = $linkedStaticMap;

        return $this;
    }

    public function getLinkedInteractiveMap(): ?InteractiveMap
    {
        return $this->linkedInteractiveMap;
    }

    public function setLinkedInteractiveMap(?InteractiveMap $linkedInteractiveMap): static
    {
        $this->linkedInteractiveMap = $linkedInteractiveMap;

        return $this;
    }

    public function getServiceEndpoint(): ?MapServiceEndpoint
    {
        return $this->serviceEndpoint;
    }

    public function setServiceEndpoint(?MapServiceEndpoint $serviceEndpoint): static
    {
        $this->serviceEndpoint = $serviceEndpoint;

        return $this;
    }

    /** @return Collection<int, DataCategory> */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(DataCategory $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
            if (!$category->getSources()->contains($this)) {
                $category->addSource($this);
            }
        }

        return $this;
    }

    public function removeCategory(DataCategory $category): static
    {
        if ($this->categories->removeElement($category) && $category->getSources()->contains($this)) {
            $category->removeSource($this);
        }

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

    public function __toString(): string
    {
        return $this->title;
    }

    #[Assert\Callback]
    public function validateByType(ExecutionContextInterface $context): void
    {
        $sourceUrl = trim((string) $this->sourceUrl);
        $filePath = trim((string) $this->filePath);

        if (\in_array($this->sourceType, [self::TYPE_WMS, self::TYPE_WFS], true) && $sourceUrl === '') {
            $context->buildViolation('Une URL de service est obligatoire pour une source WMS/WFS.')
                ->atPath('sourceUrl')
                ->addViolation();
        }

        if ($this->sourceType === self::TYPE_DATA_FILE && $filePath === '') {
            $context->buildViolation('Le chemin de fichier est obligatoire pour une source de type fichier de données.')
                ->atPath('filePath')
                ->addViolation();
        }

        if (
            $sourceUrl !== ''
            && $this->visibilityScope === VisibilityScope::PUBLIC
            && !$this->isPublicAccessibleUrl($sourceUrl)
        ) {
            $context->buildViolation('L’URL source n’est pas publiquement accessible (localhost ou domaine .local interdit pour une source publique).')
                ->atPath('sourceUrl')
                ->addViolation();
        }
    }

    private function isPublicAccessibleUrl(string $url): bool
    {
        if (str_starts_with($url, '/')) {
            return true;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '') {
            return false;
        }

        if ($host === 'localhost' || str_ends_with($host, '.local')) {
            return false;
        }

        return true;
    }
}
