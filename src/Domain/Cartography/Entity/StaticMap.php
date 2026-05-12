<?php

declare(strict_types=1);

namespace App\Domain\Cartography\Entity;

use App\Domain\Access\VisibilityScope;
use App\Domain\Common\Entity\Traits\BlameableTrait;
use App\Domain\Common\Entity\Traits\IdentifierTrait;
use App\Domain\Common\Entity\Traits\MetadataTrait;
use App\Domain\Common\Entity\Traits\PublishableTrait;
use App\Domain\Common\Entity\Traits\SoftDeletableTrait;
use App\Domain\Common\Entity\Traits\TimestampableTrait;
use App\Infrastructure\Repository\StaticMapRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StaticMapRepository::class)]
#[ORM\Table(name: 'static_map')]
#[ORM\UniqueConstraint(name: 'uniq_static_map_slug', columns: ['slug'])]
#[ORM\Index(name: 'idx_static_map_status', columns: ['status'])]
#[ORM\HasLifecycleCallbacks]
class StaticMap
{
    use IdentifierTrait;
    use TimestampableTrait;
    use PublishableTrait;
    use MetadataTrait;
    use BlameableTrait;
    use SoftDeletableTrait;

    #[ORM\Column(type: Types::STRING, length: 180)]
    private string $slug = '';

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $summary = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING, length: 120, nullable: true)]
    private ?string $theme = null;

    /** @var array<int, string>|null */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $keywords = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $documentDate = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $thumbnailPath = null;

    #[ORM\Column(type: Types::STRING, length: 16, options: ['default' => VisibilityScope::PUBLIC])]
    private string $visibilityScope = VisibilityScope::PUBLIC;

    #[ORM\ManyToOne(targetEntity: MetadataRecord::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL', nullable: true)]
    private ?MetadataRecord $metadataRecord = null;

    /** @var Collection<int, StaticMapAsset> */
    #[ORM\OneToMany(mappedBy: 'staticMap', targetEntity: StaticMapAsset::class, cascade: ['persist', 'remove'])]
    private Collection $assets;

    /** @var Collection<int, DatasetResource> */
    #[ORM\OneToMany(mappedBy: 'staticMap', targetEntity: DatasetResource::class, cascade: ['persist', 'remove'])]
    private Collection $datasetResources;

    public function __construct()
    {
        $this->assets = new ArrayCollection();
        $this->datasetResources = new ArrayCollection();
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

    public function getDocumentDate(): ?\DateTimeImmutable
    {
        return $this->documentDate;
    }

    public function setDocumentDate(?\DateTimeImmutable $documentDate): static
    {
        $this->documentDate = $documentDate;

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

    public function getVisibilityScope(): string
    {
        return $this->visibilityScope;
    }

    public function setVisibilityScope(string $visibilityScope): static
    {
        $this->visibilityScope = strtolower(trim($visibilityScope));

        return $this;
    }

    public function getMetadataRecord(): ?MetadataRecord
    {
        return $this->metadataRecord;
    }

    public function setMetadataRecord(?MetadataRecord $metadataRecord): static
    {
        $this->metadataRecord = $metadataRecord;

        return $this;
    }

    /** @return Collection<int, StaticMapAsset> */
    public function getAssets(): Collection
    {
        return $this->assets;
    }

    public function addAsset(StaticMapAsset $asset): static
    {
        if (!$this->assets->contains($asset)) {
            $this->assets->add($asset);
            $asset->setStaticMap($this);
        }

        return $this;
    }

    /** @return Collection<int, DatasetResource> */
    public function getDatasetResources(): Collection
    {
        return $this->datasetResources;
    }

    public function addDatasetResource(DatasetResource $datasetResource): static
    {
        if (!$this->datasetResources->contains($datasetResource)) {
            $this->datasetResources->add($datasetResource);
            $datasetResource->setStaticMap($this);
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->title;
    }
}
