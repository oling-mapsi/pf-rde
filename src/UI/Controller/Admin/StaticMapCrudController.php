<?php

declare(strict_types=1);

namespace App\UI\Controller\Admin;

use App\Domain\Access\VisibilityScope;
use App\Domain\Cartography\Entity\StaticMap;
use App\Infrastructure\Repository\DataSourceRepository;
use App\Infrastructure\Repository\StaticMapRepository;
use App\Infrastructure\Repository\TaxonomyTermRepository;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class StaticMapCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly TaxonomyTermRepository $taxonomyTermRepository,
        private readonly DataSourceRepository $dataSourceRepository,
        private readonly StaticMapRepository $staticMapRepository,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return StaticMap::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('title');
        yield SlugField::new('slug')->setTargetFieldName('title');
        yield ChoiceField::new('theme', 'Thème cartothèque')
            ->setChoices($this->themeChoices())
            ->setRequired(false)
            ->setFormTypeOption('placeholder', 'Choisir un thème')
            ->renderAsNativeWidget()
            ->hideOnIndex();
        yield TextareaField::new('summary')->hideOnIndex();
        yield TextareaField::new('description')->hideOnIndex();
        yield DateField::new('documentDate')->hideOnIndex();
        yield TextField::new('thumbnailPath')->hideOnIndex();
        yield ChoiceField::new('visibilityScope', 'Visibilité')
            ->setChoices(array_flip(VisibilityScope::LABELS));
        yield TextField::new('status');
        yield DateTimeField::new('publishedAt')->hideOnIndex();
    }

    /**
     * @return array<string, string>
     */
    private function themeChoices(): array
    {
        $choices = $this->taxonomyTermRepository->findMapThemeChoicesForSelect();

        foreach ($this->staticMapRepository->findAvailableThemes(VisibilityScope::all()) as $theme) {
            $theme = trim($theme);
            if ($theme !== '') {
                $choices[$theme] = $theme;
            }
        }

        foreach ($this->dataSourceRepository->findAvailableThemes(VisibilityScope::all()) as $theme) {
            $theme = trim($theme);
            if ($theme !== '') {
                $choices[$theme] = $theme;
            }
        }

        ksort($choices, \SORT_NATURAL | \SORT_FLAG_CASE);

        return $choices;
    }
}
