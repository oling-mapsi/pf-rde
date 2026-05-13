<?php

declare(strict_types=1);

namespace App\UI\Controller\Admin;

use App\Domain\Cartography\Entity\DataCategory;
use EasyCorp\Bundle\EasyAdminBundle\Config\Asset;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ColorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;

final class DataCategoryCrudController extends AbstractCrudController
{
    private const STATUS_CHOICES = [
        'Brouillon' => 'draft',
        'Publié' => 'published',
        'Archivé' => 'archived',
    ];

    public static function getEntityFqcn(): string
    {
        return DataCategory::class;
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addCssFile(Asset::new('/admin-assets/icon-reference.css'))
            ->addCssFile(Asset::new('/admin-assets/data-source-form.css')->onlyOnForms())
            ->addJsFile(Asset::new('/admin-assets/data-source-form.js')->onlyOnForms()->defer());
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Catégorie de ressource')
            ->setEntityLabelInPlural('Catégories de ressources')
            ->setDefaultSort(['position' => 'ASC', 'name' => 'ASC'])
            ->setSearchFields(['name', 'slug', 'description']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(BooleanFilter::new('featuredOnHomepage', 'Mise en avant accueil'))
            ->add(ChoiceFilter::new('status', 'Statut')->setChoices(self::STATUS_CHOICES));
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addTab('Catégorie');
        yield TextField::new('name', 'Nom de la catégorie');
        yield SlugField::new('slug', 'Slug')->setTargetFieldName('name');
        yield TextareaField::new('description', 'Description')->hideOnIndex();
        yield ChoiceField::new('iconKey', 'Icône')
            ->setChoices(DataCategory::ICON_CHOICES)
            ->setFormTypeOption('placeholder', 'Choisir une icône')
            ->setFormTypeOption('attr.data-icon-selector', 'true')
            ->setHelp('<div class="ds-icon-preview" data-icon-preview aria-live="polite">Sélectionnez une icône pour prévisualiser.</div>');
        yield ColorField::new('colorHex', 'Couleur')->showValue();
        yield IntegerField::new('position', 'Ordre');
        yield BooleanField::new('featuredOnHomepage', 'Mise en avant accueil');

        yield FormField::addTab('Liaisons');
        yield AssociationField::new('sources', 'Sources associées')
            ->autocomplete()
            ->setFormTypeOption('by_reference', false)
            ->setHelp('Associez les sources de données rattachées à cette catégorie.')
            ->hideOnIndex();

        yield FormField::addTab('Publication');
        yield ChoiceField::new('status', 'Statut')
            ->setChoices(self::STATUS_CHOICES)
            ->renderAsBadges([
                'draft' => 'secondary',
                'published' => 'success',
                'archived' => 'dark',
            ]);
        yield DateTimeField::new('publishedAt', 'Publication')->hideOnIndex();
    }
}
