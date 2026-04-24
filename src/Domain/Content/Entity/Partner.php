<?php

declare(strict_types=1);

namespace App\Domain\Content\Entity;

use App\Domain\Common\Entity\Traits\BlameableTrait;
use App\Domain\Common\Entity\Traits\IdentifierTrait;
use App\Domain\Common\Entity\Traits\MetadataTrait;
use App\Domain\Common\Entity\Traits\PublishableTrait;
use App\Domain\Common\Entity\Traits\TimestampableTrait;
use App\Infrastructure\Repository\PartnerRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PartnerRepository::class)]
#[ORM\Table(name: 'partner')]
#[ORM\UniqueConstraint(name: 'uniq_partner_slug', columns: ['slug'])]
#[ORM\HasLifecycleCallbacks]
class Partner
{
    use IdentifierTrait;
    use TimestampableTrait;
    use PublishableTrait;
    use MetadataTrait;
    use BlameableTrait;

    #[ORM\Column(type: Types::STRING, length: 180)]
    private string $slug = '';

    #[ORM\Column(type: Types::STRING, length: 180)]
    private string $name = '';

    #[ORM\Column(type: Types::STRING, length: 32, options: ['default' => 'partner'])]
    private string $kind = 'partner';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING, length: 512, nullable: true)]
    private ?string $websiteUrl = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $logoPath = null;

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

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

    public function getKind(): string
    {
        return $this->kind;
    }

    public function setKind(string $kind): static
    {
        $this->kind = $kind;

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

    public function getWebsiteUrl(): ?string
    {
        return $this->websiteUrl;
    }

    public function setWebsiteUrl(?string $websiteUrl): static
    {
        $this->websiteUrl = $websiteUrl;

        return $this;
    }

    public function getLogoPath(): ?string
    {
        return $this->logoPath;
    }

    public function setLogoPath(?string $logoPath): static
    {
        $this->logoPath = $logoPath;

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
