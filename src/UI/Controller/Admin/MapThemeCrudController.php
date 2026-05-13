<?php

declare(strict_types=1);

namespace App\UI\Controller\Admin;

use App\Domain\Taxonomy\Entity\TaxonomyTerm;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Asset;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ColorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;

final class MapThemeCrudController extends AbstractCrudController
{
    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addCssFile(Asset::new('/admin-assets/icon-reference.css'))
            ->addCssFile(Asset::new('/admin-assets/data-source-form.css')->onlyOnForms())
            ->addJsFile(Asset::new('/admin-assets/data-source-form.js')->onlyOnForms()->defer());
    }

    public static function getEntityFqcn(): string
    {
        return TaxonomyTerm::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Thème cartothèque')
            ->setEntityLabelInPlural('Thèmes cartothèque')
            ->setDefaultSort(['label' => 'ASC'])
            ->setSearchFields(['label', 'slug', 'description']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(BooleanFilter::new('active', 'Actif'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addTab('Thème');
        yield TextField::new('label', 'Nom du thème');
        yield SlugField::new('slug', 'Slug')->setTargetFieldName('label');
        yield TextareaField::new('description', 'Description')->hideOnIndex();
        yield ChoiceField::new('iconKey', 'Icône')
            ->setChoices(TaxonomyTerm::ICON_CHOICES)
            ->setFormTypeOption('placeholder', 'Choisir une icône')
            ->setFormTypeOption('attr.data-icon-selector', 'true')
            ->setHelp('<div class="ds-icon-preview" data-icon-preview aria-live="polite">Sélectionnez une icône pour prévisualiser.</div>');
        yield ColorField::new('colorHex', 'Couleur')->showValue();
        yield IntegerField::new('position', 'Ordre');
        yield BooleanField::new('featuredOnHomepage', 'Mise en avant sous le héros');

        yield FormField::addTab('Publication');
        yield BooleanField::new('active', 'Actif');
    }

    public function createEntity(string $entityFqcn): TaxonomyTerm
    {
        $theme = new TaxonomyTerm();
        $theme->setTaxonomy(TaxonomyTerm::MAP_THEME_TAXONOMY);

        return $theme;
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof TaxonomyTerm) {
            $entityInstance->setTaxonomy(TaxonomyTerm::MAP_THEME_TAXONOMY);
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof TaxonomyTerm) {
            $entityInstance->setTaxonomy(TaxonomyTerm::MAP_THEME_TAXONOMY);
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters,
    ): QueryBuilder {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $rootAliases = $queryBuilder->getRootAliases();
        $alias = $rootAliases[0] ?? 'entity';

        return $queryBuilder
            ->andWhere(sprintf('%s.taxonomy = :taxonomy', $alias))
            ->setParameter('taxonomy', TaxonomyTerm::MAP_THEME_TAXONOMY);
    }
}
