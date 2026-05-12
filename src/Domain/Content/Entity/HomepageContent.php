<?php

declare(strict_types=1);

namespace App\Domain\Content\Entity;

use App\Domain\Common\Entity\Traits\BlameableTrait;
use App\Domain\Common\Entity\Traits\IdentifierTrait;
use App\Domain\Common\Entity\Traits\PublishableTrait;
use App\Domain\Common\Entity\Traits\TimestampableTrait;
use App\Infrastructure\Repository\HomepageContentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HomepageContentRepository::class)]
#[ORM\Table(name: 'homepage_content')]
#[ORM\HasLifecycleCallbacks]
class HomepageContent
{
    use IdentifierTrait;
    use TimestampableTrait;
    use PublishableTrait;
    use BlameableTrait;

    #[ORM\Column(type: Types::STRING, length: 120)]
    private string $name = 'Accueil principal';

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $heroTitle = 'La plateforme Open Data et SIG de Routes de Guadeloupe';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $heroBaseline = 'Plateforme de référence pour la cartographie routière de la Guadeloupe. Cartothèque statique, cartes interactives, information usagers et services agents.';

    #[ORM\Column(type: Types::STRING, length: 180, nullable: true)]
    private ?string $searchIntro = 'Explorer les ressources du portail';

    #[ORM\Column(type: Types::STRING, length: 180, nullable: true)]
    private ?string $searchPlaceholder = 'Rechercher une carte, un jeu de données ou une ressource SIG';

    #[ORM\Column(type: Types::STRING, length: 180, nullable: true)]
    private ?string $primaryCtaLabel = null;

    #[ORM\Column(type: Types::STRING, length: 512, nullable: true)]
    private ?string $primaryCtaUrl = null;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getHeroTitle(): string
    {
        return $this->heroTitle;
    }

    public function setHeroTitle(string $heroTitle): static
    {
        $this->heroTitle = $heroTitle;

        return $this;
    }

    public function getHeroBaseline(): ?string
    {
        return $this->heroBaseline;
    }

    public function setHeroBaseline(?string $heroBaseline): static
    {
        $this->heroBaseline = $heroBaseline;

        return $this;
    }

    public function getSearchIntro(): ?string
    {
        return $this->searchIntro;
    }

    public function setSearchIntro(?string $searchIntro): static
    {
        $this->searchIntro = $searchIntro;

        return $this;
    }

    public function getSearchPlaceholder(): ?string
    {
        return $this->searchPlaceholder;
    }

    public function setSearchPlaceholder(?string $searchPlaceholder): static
    {
        $this->searchPlaceholder = $searchPlaceholder;

        return $this;
    }

    public function getPrimaryCtaLabel(): ?string
    {
        return $this->primaryCtaLabel;
    }

    public function setPrimaryCtaLabel(?string $primaryCtaLabel): static
    {
        $this->primaryCtaLabel = $primaryCtaLabel;

        return $this;
    }

    public function getPrimaryCtaUrl(): ?string
    {
        return $this->primaryCtaUrl;
    }

    public function setPrimaryCtaUrl(?string $primaryCtaUrl): static
    {
        $this->primaryCtaUrl = $primaryCtaUrl;

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
