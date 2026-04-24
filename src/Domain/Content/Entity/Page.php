<?php

declare(strict_types=1);

namespace App\Domain\Content\Entity;

use App\Domain\Common\Entity\Traits\BlameableTrait;
use App\Domain\Common\Entity\Traits\IdentifierTrait;
use App\Domain\Common\Entity\Traits\MetadataTrait;
use App\Domain\Common\Entity\Traits\PublishableTrait;
use App\Domain\Common\Entity\Traits\SoftDeletableTrait;
use App\Domain\Common\Entity\Traits\TimestampableTrait;
use App\Infrastructure\Repository\PageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PageRepository::class)]
#[ORM\Table(name: 'page')]
#[ORM\UniqueConstraint(name: 'uniq_page_slug', columns: ['slug'])]
#[ORM\HasLifecycleCallbacks]
class Page
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

    #[ORM\Column(type: Types::TEXT)]
    private string $content = '';

    #[ORM\Column(type: Types::STRING, length: 64, nullable: true)]
    private ?string $legalType = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $systemPage = false;

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

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getLegalType(): ?string
    {
        return $this->legalType;
    }

    public function setLegalType(?string $legalType): static
    {
        $this->legalType = $legalType;

        return $this;
    }

    public function isSystemPage(): bool
    {
        return $this->systemPage;
    }

    public function setSystemPage(bool $systemPage): static
    {
        $this->systemPage = $systemPage;

        return $this;
    }

    public function __toString(): string
    {
        return $this->title;
    }
}
