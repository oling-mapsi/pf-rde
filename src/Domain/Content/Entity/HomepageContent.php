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

    #[ORM\Column(type: Types::STRING, length: 512, nullable: true)]
    private ?string $heroBackgroundImagePath = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $heroTitleColor = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $heroBaselineColor = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $heroTitleFontSize = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $heroBaselineFontSize = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $heroSearchBackgroundColor = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $heroSearchTextColor = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $heroSearchPlaceholderColor = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $heroSearchButtonColor = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $heroSearchButtonBackgroundColor = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $heroSearchBorderColor = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $heroPrimaryCtaTextColor = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $heroPrimaryCtaBackgroundColor = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $heroThemesGap = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $heroThemeButtonRadius = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $heroThemeButtonPadding = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $heroThemeLabelColor = null;

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

    public function getHeroBackgroundImagePath(): ?string
    {
        return $this->heroBackgroundImagePath;
    }

    public function setHeroBackgroundImagePath(?string $heroBackgroundImagePath): static
    {
        $this->heroBackgroundImagePath = $heroBackgroundImagePath;

        return $this;
    }

    public function getHeroTitleColor(): ?string
    {
        return $this->heroTitleColor;
    }

    public function setHeroTitleColor(?string $heroTitleColor): static
    {
        $this->heroTitleColor = $heroTitleColor;

        return $this;
    }

    public function getHeroBaselineColor(): ?string
    {
        return $this->heroBaselineColor;
    }

    public function setHeroBaselineColor(?string $heroBaselineColor): static
    {
        $this->heroBaselineColor = $heroBaselineColor;

        return $this;
    }

    public function getHeroTitleFontSize(): ?string
    {
        return $this->heroTitleFontSize;
    }

    public function setHeroTitleFontSize(?string $heroTitleFontSize): static
    {
        $this->heroTitleFontSize = $heroTitleFontSize;

        return $this;
    }

    public function getHeroBaselineFontSize(): ?string
    {
        return $this->heroBaselineFontSize;
    }

    public function setHeroBaselineFontSize(?string $heroBaselineFontSize): static
    {
        $this->heroBaselineFontSize = $heroBaselineFontSize;

        return $this;
    }

    public function getHeroSearchBackgroundColor(): ?string
    {
        return $this->heroSearchBackgroundColor;
    }

    public function setHeroSearchBackgroundColor(?string $heroSearchBackgroundColor): static
    {
        $this->heroSearchBackgroundColor = $heroSearchBackgroundColor;

        return $this;
    }

    public function getHeroSearchTextColor(): ?string
    {
        return $this->heroSearchTextColor;
    }

    public function setHeroSearchTextColor(?string $heroSearchTextColor): static
    {
        $this->heroSearchTextColor = $heroSearchTextColor;

        return $this;
    }

    public function getHeroSearchPlaceholderColor(): ?string
    {
        return $this->heroSearchPlaceholderColor;
    }

    public function setHeroSearchPlaceholderColor(?string $heroSearchPlaceholderColor): static
    {
        $this->heroSearchPlaceholderColor = $heroSearchPlaceholderColor;

        return $this;
    }

    public function getHeroSearchButtonColor(): ?string
    {
        return $this->heroSearchButtonColor;
    }

    public function setHeroSearchButtonColor(?string $heroSearchButtonColor): static
    {
        $this->heroSearchButtonColor = $heroSearchButtonColor;

        return $this;
    }

    public function getHeroSearchButtonBackgroundColor(): ?string
    {
        return $this->heroSearchButtonBackgroundColor;
    }

    public function setHeroSearchButtonBackgroundColor(?string $heroSearchButtonBackgroundColor): static
    {
        $this->heroSearchButtonBackgroundColor = $heroSearchButtonBackgroundColor;

        return $this;
    }

    public function getHeroSearchBorderColor(): ?string
    {
        return $this->heroSearchBorderColor;
    }

    public function setHeroSearchBorderColor(?string $heroSearchBorderColor): static
    {
        $this->heroSearchBorderColor = $heroSearchBorderColor;

        return $this;
    }

    public function getHeroPrimaryCtaTextColor(): ?string
    {
        return $this->heroPrimaryCtaTextColor;
    }

    public function setHeroPrimaryCtaTextColor(?string $heroPrimaryCtaTextColor): static
    {
        $this->heroPrimaryCtaTextColor = $heroPrimaryCtaTextColor;

        return $this;
    }

    public function getHeroPrimaryCtaBackgroundColor(): ?string
    {
        return $this->heroPrimaryCtaBackgroundColor;
    }

    public function setHeroPrimaryCtaBackgroundColor(?string $heroPrimaryCtaBackgroundColor): static
    {
        $this->heroPrimaryCtaBackgroundColor = $heroPrimaryCtaBackgroundColor;

        return $this;
    }

    public function getHeroThemesGap(): ?string
    {
        return $this->heroThemesGap;
    }

    public function setHeroThemesGap(?string $heroThemesGap): static
    {
        $this->heroThemesGap = $heroThemesGap;

        return $this;
    }

    public function getHeroThemeButtonRadius(): ?string
    {
        return $this->heroThemeButtonRadius;
    }

    public function setHeroThemeButtonRadius(?string $heroThemeButtonRadius): static
    {
        $this->heroThemeButtonRadius = $heroThemeButtonRadius;

        return $this;
    }

    public function getHeroThemeButtonPadding(): ?string
    {
        return $this->heroThemeButtonPadding;
    }

    public function setHeroThemeButtonPadding(?string $heroThemeButtonPadding): static
    {
        $this->heroThemeButtonPadding = $heroThemeButtonPadding;

        return $this;
    }

    public function getHeroThemeLabelColor(): ?string
    {
        return $this->heroThemeLabelColor;
    }

    public function setHeroThemeLabelColor(?string $heroThemeLabelColor): static
    {
        $this->heroThemeLabelColor = $heroThemeLabelColor;

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
