<?php

declare(strict_types=1);

namespace App\Domain\Content\Entity;

use App\Domain\Common\Entity\Traits\BlameableTrait;
use App\Domain\Common\Entity\Traits\IdentifierTrait;
use App\Domain\Common\Entity\Traits\PublishableTrait;
use App\Domain\Common\Entity\Traits\TimestampableTrait;
use App\Infrastructure\Repository\HomepageSectionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HomepageSectionRepository::class)]
#[ORM\Table(name: 'homepage_section')]
#[ORM\Index(name: 'idx_homepage_section_status_position', columns: ['status', 'position'])]
#[ORM\HasLifecycleCallbacks]
class HomepageSection
{
    public const TYPE_MANUAL_CARDS = 'manual_cards';
    public const TYPE_LATEST_NEWS = 'latest_news';
    public const TYPE_FEATURED_RESOURCES = 'featured_resources';
    public const TYPE_QUICK_LINKS = 'quick_links';
    public const TYPE_MESSAGE = 'message';
    public const TYPE_SPONSOR = 'sponsor';
    public const TYPE_DATA_HIGHLIGHTS = 'data_highlights';

    public const LAYOUT_GRID = 'grid';
    public const LAYOUT_FEATURE = 'feature';
    public const LAYOUT_BANNER = 'banner';

    use IdentifierTrait;
    use TimestampableTrait;
    use PublishableTrait;
    use BlameableTrait;

    #[ORM\Column(type: Types::STRING, length: 180)]
    private string $name = '';

    #[ORM\Column(type: Types::STRING, length: 48)]
    private string $type = self::TYPE_MANUAL_CARDS;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $position = 0;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $intro = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $body = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $imagePath = null;

    #[ORM\Column(type: Types::STRING, length: 160, nullable: true)]
    private ?string $ctaLabel = null;

    #[ORM\Column(type: Types::STRING, length: 512, nullable: true)]
    private ?string $ctaUrl = null;

    #[ORM\Column(type: Types::STRING, length: 32, options: ['default' => self::LAYOUT_GRID])]
    private string $layout = self::LAYOUT_GRID;

    #[ORM\Column(type: Types::STRING, length: 32, options: ['default' => 'light'])]
    private string $backgroundStyle = 'light';

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 3])]
    private int $itemLimit = 3;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $itemsConfig = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $filtersConfig = null;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

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

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getIntro(): ?string
    {
        return $this->intro;
    }

    public function setIntro(?string $intro): static
    {
        $this->intro = $intro;

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(?string $body): static
    {
        $this->body = $body;

        return $this;
    }

    public function getImagePath(): ?string
    {
        return $this->imagePath;
    }

    public function setImagePath(?string $imagePath): static
    {
        $this->imagePath = $imagePath;

        return $this;
    }

    public function getCtaLabel(): ?string
    {
        return $this->ctaLabel;
    }

    public function setCtaLabel(?string $ctaLabel): static
    {
        $this->ctaLabel = $ctaLabel;

        return $this;
    }

    public function getCtaUrl(): ?string
    {
        return $this->ctaUrl;
    }

    public function setCtaUrl(?string $ctaUrl): static
    {
        $this->ctaUrl = $ctaUrl;

        return $this;
    }

    public function getLayout(): string
    {
        return $this->layout;
    }

    public function setLayout(string $layout): static
    {
        $this->layout = $layout;

        return $this;
    }

    public function getBackgroundStyle(): string
    {
        return $this->backgroundStyle;
    }

    public function setBackgroundStyle(string $backgroundStyle): static
    {
        $this->backgroundStyle = $backgroundStyle;

        return $this;
    }

    public function getItemLimit(): int
    {
        return $this->itemLimit;
    }

    public function setItemLimit(int $itemLimit): static
    {
        $this->itemLimit = max(1, $itemLimit);

        return $this;
    }

    public function getItemsConfig(): ?string
    {
        return $this->itemsConfig;
    }

    public function setItemsConfig(?string $itemsConfig): static
    {
        $this->itemsConfig = $itemsConfig;

        return $this;
    }

    /** @return list<array<string, mixed>> */
    public function getItemsConfigArray(): array
    {
        $decoded = $this->decodeJson($this->itemsConfig);

        return array_is_list($decoded) ? $decoded : [];
    }

    public function getFiltersConfig(): ?string
    {
        return $this->filtersConfig;
    }

    public function setFiltersConfig(?string $filtersConfig): static
    {
        $this->filtersConfig = $filtersConfig;

        return $this;
    }

    /** @return array<string, mixed> */
    public function getFiltersConfigArray(): array
    {
        $decoded = $this->decodeJson($this->filtersConfig);

        return array_is_list($decoded) ? [] : $decoded;
    }

    /** @return array<mixed> */
    private function decodeJson(?string $json): array
    {
        if ($json === null || trim($json) === '') {
            return [];
        }

        $decoded = json_decode($json, true);

        return \is_array($decoded) ? $decoded : [];
    }

    public function __toString(): string
    {
        return $this->name !== '' ? $this->name : $this->title;
    }
}
