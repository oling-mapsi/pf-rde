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

    #[ORM\Column(type: Types::STRING, length: 16, nullable: true)]
    private ?string $heroDarkOverlayOpacity = null;

    #[ORM\Column(type: Types::STRING, length: 16, nullable: true)]
    private ?string $heroWhiteVeilOpacity = null;

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

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $heroThemeIconBackgroundColor = null;

    #[ORM\Column(type: Types::STRING, length: 16, nullable: true)]
    private ?string $heroThemeIconBackgroundOpacity = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $heroThemeIconPadding = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $heroThemeIconMargin = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $brandPrimaryColor = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $brandSecondaryColor = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $brandAccentColor = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $brandSuccessColor = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $brandOrangeRoadColor = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $textDefaultColor = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $textHeadingColor = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $textMutedColor = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $textInverseColor = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $backgroundDefaultColor = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $backgroundSurfaceAltColor = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $borderDefaultColor = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $borderFocusColor = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $linkColor = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $linkHoverColor = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $buttonPrimaryBackgroundColor = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $buttonPrimaryBorderColor = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $buttonPrimaryTextColor = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $buttonPrimaryBackgroundHoverColor = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $buttonPrimaryBorderHoverColor = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $buttonPrimaryTextHoverColor = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $buttonOutlineBackgroundColor = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $buttonOutlineBorderColor = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $buttonOutlineTextColor = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $buttonOutlineBackgroundHoverColor = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $buttonOutlineBorderHoverColor = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $buttonOutlineTextHoverColor = null;

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

    public function getHeroDarkOverlayOpacity(): ?string
    {
        return $this->heroDarkOverlayOpacity;
    }

    public function setHeroDarkOverlayOpacity(?string $heroDarkOverlayOpacity): static
    {
        $this->heroDarkOverlayOpacity = $heroDarkOverlayOpacity;

        return $this;
    }

    public function getHeroWhiteVeilOpacity(): ?string
    {
        return $this->heroWhiteVeilOpacity;
    }

    public function setHeroWhiteVeilOpacity(?string $heroWhiteVeilOpacity): static
    {
        $this->heroWhiteVeilOpacity = $heroWhiteVeilOpacity;

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

    public function getHeroThemeIconBackgroundColor(): ?string
    {
        return $this->heroThemeIconBackgroundColor;
    }

    public function setHeroThemeIconBackgroundColor(?string $heroThemeIconBackgroundColor): static
    {
        $this->heroThemeIconBackgroundColor = $heroThemeIconBackgroundColor;

        return $this;
    }

    public function getHeroThemeIconBackgroundOpacity(): ?string
    {
        return $this->heroThemeIconBackgroundOpacity;
    }

    public function setHeroThemeIconBackgroundOpacity(?string $heroThemeIconBackgroundOpacity): static
    {
        $this->heroThemeIconBackgroundOpacity = $heroThemeIconBackgroundOpacity;

        return $this;
    }

    public function getHeroThemeIconPadding(): ?string
    {
        return $this->heroThemeIconPadding;
    }

    public function setHeroThemeIconPadding(?string $heroThemeIconPadding): static
    {
        $this->heroThemeIconPadding = $heroThemeIconPadding;

        return $this;
    }

    public function getHeroThemeIconMargin(): ?string
    {
        return $this->heroThemeIconMargin;
    }

    public function setHeroThemeIconMargin(?string $heroThemeIconMargin): static
    {
        $this->heroThemeIconMargin = $heroThemeIconMargin;

        return $this;
    }

    public function getBrandPrimaryColor(): ?string
    {
        return $this->brandPrimaryColor;
    }

    public function setBrandPrimaryColor(?string $brandPrimaryColor): static
    {
        $this->brandPrimaryColor = $brandPrimaryColor;

        return $this;
    }

    public function getBrandSecondaryColor(): ?string
    {
        return $this->brandSecondaryColor;
    }

    public function setBrandSecondaryColor(?string $brandSecondaryColor): static
    {
        $this->brandSecondaryColor = $brandSecondaryColor;

        return $this;
    }

    public function getBrandAccentColor(): ?string
    {
        return $this->brandAccentColor;
    }

    public function setBrandAccentColor(?string $brandAccentColor): static
    {
        $this->brandAccentColor = $brandAccentColor;

        return $this;
    }

    public function getBrandSuccessColor(): ?string
    {
        return $this->brandSuccessColor;
    }

    public function setBrandSuccessColor(?string $brandSuccessColor): static
    {
        $this->brandSuccessColor = $brandSuccessColor;

        return $this;
    }

    public function getBrandOrangeRoadColor(): ?string
    {
        return $this->brandOrangeRoadColor;
    }

    public function setBrandOrangeRoadColor(?string $brandOrangeRoadColor): static
    {
        $this->brandOrangeRoadColor = $brandOrangeRoadColor;

        return $this;
    }

    public function getTextDefaultColor(): ?string
    {
        return $this->textDefaultColor;
    }

    public function setTextDefaultColor(?string $textDefaultColor): static
    {
        $this->textDefaultColor = $textDefaultColor;

        return $this;
    }

    public function getTextHeadingColor(): ?string
    {
        return $this->textHeadingColor;
    }

    public function setTextHeadingColor(?string $textHeadingColor): static
    {
        $this->textHeadingColor = $textHeadingColor;

        return $this;
    }

    public function getTextMutedColor(): ?string
    {
        return $this->textMutedColor;
    }

    public function setTextMutedColor(?string $textMutedColor): static
    {
        $this->textMutedColor = $textMutedColor;

        return $this;
    }

    public function getTextInverseColor(): ?string
    {
        return $this->textInverseColor;
    }

    public function setTextInverseColor(?string $textInverseColor): static
    {
        $this->textInverseColor = $textInverseColor;

        return $this;
    }

    public function getBackgroundDefaultColor(): ?string
    {
        return $this->backgroundDefaultColor;
    }

    public function setBackgroundDefaultColor(?string $backgroundDefaultColor): static
    {
        $this->backgroundDefaultColor = $backgroundDefaultColor;

        return $this;
    }

    public function getBackgroundSurfaceAltColor(): ?string
    {
        return $this->backgroundSurfaceAltColor;
    }

    public function setBackgroundSurfaceAltColor(?string $backgroundSurfaceAltColor): static
    {
        $this->backgroundSurfaceAltColor = $backgroundSurfaceAltColor;

        return $this;
    }

    public function getBorderDefaultColor(): ?string
    {
        return $this->borderDefaultColor;
    }

    public function setBorderDefaultColor(?string $borderDefaultColor): static
    {
        $this->borderDefaultColor = $borderDefaultColor;

        return $this;
    }

    public function getBorderFocusColor(): ?string
    {
        return $this->borderFocusColor;
    }

    public function setBorderFocusColor(?string $borderFocusColor): static
    {
        $this->borderFocusColor = $borderFocusColor;

        return $this;
    }

    public function getLinkColor(): ?string
    {
        return $this->linkColor;
    }

    public function setLinkColor(?string $linkColor): static
    {
        $this->linkColor = $linkColor;

        return $this;
    }

    public function getLinkHoverColor(): ?string
    {
        return $this->linkHoverColor;
    }

    public function setLinkHoverColor(?string $linkHoverColor): static
    {
        $this->linkHoverColor = $linkHoverColor;

        return $this;
    }

    public function getButtonPrimaryBackgroundColor(): ?string
    {
        return $this->buttonPrimaryBackgroundColor;
    }

    public function setButtonPrimaryBackgroundColor(?string $buttonPrimaryBackgroundColor): static
    {
        $this->buttonPrimaryBackgroundColor = $buttonPrimaryBackgroundColor;

        return $this;
    }

    public function getButtonPrimaryBorderColor(): ?string
    {
        return $this->buttonPrimaryBorderColor;
    }

    public function setButtonPrimaryBorderColor(?string $buttonPrimaryBorderColor): static
    {
        $this->buttonPrimaryBorderColor = $buttonPrimaryBorderColor;

        return $this;
    }

    public function getButtonPrimaryTextColor(): ?string
    {
        return $this->buttonPrimaryTextColor;
    }

    public function setButtonPrimaryTextColor(?string $buttonPrimaryTextColor): static
    {
        $this->buttonPrimaryTextColor = $buttonPrimaryTextColor;

        return $this;
    }

    public function getButtonPrimaryBackgroundHoverColor(): ?string
    {
        return $this->buttonPrimaryBackgroundHoverColor;
    }

    public function setButtonPrimaryBackgroundHoverColor(?string $buttonPrimaryBackgroundHoverColor): static
    {
        $this->buttonPrimaryBackgroundHoverColor = $buttonPrimaryBackgroundHoverColor;

        return $this;
    }

    public function getButtonPrimaryBorderHoverColor(): ?string
    {
        return $this->buttonPrimaryBorderHoverColor;
    }

    public function setButtonPrimaryBorderHoverColor(?string $buttonPrimaryBorderHoverColor): static
    {
        $this->buttonPrimaryBorderHoverColor = $buttonPrimaryBorderHoverColor;

        return $this;
    }

    public function getButtonPrimaryTextHoverColor(): ?string
    {
        return $this->buttonPrimaryTextHoverColor;
    }

    public function setButtonPrimaryTextHoverColor(?string $buttonPrimaryTextHoverColor): static
    {
        $this->buttonPrimaryTextHoverColor = $buttonPrimaryTextHoverColor;

        return $this;
    }

    public function getButtonOutlineBackgroundColor(): ?string
    {
        return $this->buttonOutlineBackgroundColor;
    }

    public function setButtonOutlineBackgroundColor(?string $buttonOutlineBackgroundColor): static
    {
        $this->buttonOutlineBackgroundColor = $buttonOutlineBackgroundColor;

        return $this;
    }

    public function getButtonOutlineBorderColor(): ?string
    {
        return $this->buttonOutlineBorderColor;
    }

    public function setButtonOutlineBorderColor(?string $buttonOutlineBorderColor): static
    {
        $this->buttonOutlineBorderColor = $buttonOutlineBorderColor;

        return $this;
    }

    public function getButtonOutlineTextColor(): ?string
    {
        return $this->buttonOutlineTextColor;
    }

    public function setButtonOutlineTextColor(?string $buttonOutlineTextColor): static
    {
        $this->buttonOutlineTextColor = $buttonOutlineTextColor;

        return $this;
    }

    public function getButtonOutlineBackgroundHoverColor(): ?string
    {
        return $this->buttonOutlineBackgroundHoverColor;
    }

    public function setButtonOutlineBackgroundHoverColor(?string $buttonOutlineBackgroundHoverColor): static
    {
        $this->buttonOutlineBackgroundHoverColor = $buttonOutlineBackgroundHoverColor;

        return $this;
    }

    public function getButtonOutlineBorderHoverColor(): ?string
    {
        return $this->buttonOutlineBorderHoverColor;
    }

    public function setButtonOutlineBorderHoverColor(?string $buttonOutlineBorderHoverColor): static
    {
        $this->buttonOutlineBorderHoverColor = $buttonOutlineBorderHoverColor;

        return $this;
    }

    public function getButtonOutlineTextHoverColor(): ?string
    {
        return $this->buttonOutlineTextHoverColor;
    }

    public function setButtonOutlineTextHoverColor(?string $buttonOutlineTextHoverColor): static
    {
        $this->buttonOutlineTextHoverColor = $buttonOutlineTextHoverColor;

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
