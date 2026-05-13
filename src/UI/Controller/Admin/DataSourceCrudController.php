<?php

declare(strict_types=1);

namespace App\UI\Controller\Admin;

use App\Domain\Access\VisibilityScope;
use App\Domain\Cartography\Entity\DataSource;
use App\Infrastructure\Repository\DataSourceRepository;
use App\Infrastructure\Repository\StaticMapRepository;
use App\Infrastructure\Repository\TaxonomyTermRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Asset;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;

final class DataSourceCrudController extends AbstractCrudController
{
    private const STATUS_CHOICES = [
        'Brouillon' => 'draft',
        'Publié' => 'published',
        'Archivé' => 'archived',
    ];

    public function __construct(
        private readonly TaxonomyTermRepository $taxonomyTermRepository,
        private readonly DataSourceRepository $dataSourceRepository,
        private readonly StaticMapRepository $staticMapRepository,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return DataSource::class;
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
            ->setEntityLabelInSingular('Source de données')
            ->setEntityLabelInPlural('Sources de données')
            ->setDefaultSort(['publishedAt' => 'DESC', 'createdAt' => 'DESC'])
            ->setSearchFields(['title', 'summary', 'description', 'theme', 'format', 'sourceUrl', 'categories.name']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('sourceType', 'Type')->setChoices(DataSource::TYPE_CHOICES))
            ->add(ChoiceFilter::new('status', 'Statut')->setChoices(self::STATUS_CHOICES))
            ->add('theme')
            ->add('categories')
            ->add('serviceEndpoint');
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addTab('Identification');
        yield TextField::new('title', 'Titre');
        yield SlugField::new('slug')->setTargetFieldName('title');
        yield ChoiceField::new('sourceType', 'Type')
            ->setChoices(DataSource::TYPE_CHOICES)
            ->setFormTypeOption('attr.data-source-type-selector', 'true')
            ->setHelp('<div class="ds-guide" data-source-guide aria-live="polite"></div>')
            ->renderAsBadges([
                DataSource::TYPE_CARTOGRAPHY_LINK => 'primary',
                DataSource::TYPE_WMS => 'info',
                DataSource::TYPE_WFS => 'success',
                DataSource::TYPE_DATA_FILE => 'warning',
                DataSource::TYPE_STATIC_MAP => 'secondary',
            ]);
        yield ChoiceField::new('iconKey', 'Icône')
            ->setChoices(DataSource::ICON_CHOICES)
            ->setFormTypeOption('placeholder', 'Choisir une icône')
            ->setFormTypeOption('attr.data-icon-selector', 'true')
            ->setHelp('<div class="ds-icon-preview" data-icon-preview aria-live="polite">Sélectionnez une icône pour prévisualiser.</div>')
            ->hideOnIndex();
        yield AssociationField::new('categories', 'Catégories de ressources')
            ->onlyOnIndex();
        yield AssociationField::new('categories', 'Catégories')
            ->autocomplete()
            ->setFormTypeOption('by_reference', false)
            ->hideOnIndex()
            ->setHelp('Associez cette source à une ou plusieurs catégories du catalogue.');
        yield ChoiceField::new('theme', 'Thème cartothèque')
            ->setChoices($this->themeChoices())
            ->setRequired(false)
            ->setFormTypeOption('placeholder', 'Choisir un thème')
            ->renderAsNativeWidget()
            ->hideOnIndex();
        yield ChoiceField::new('visibilityScope', 'Visibilité')
            ->setChoices(array_flip(VisibilityScope::LABELS))
            ->renderAsNativeWidget();
        yield ImageField::new('thumbnailPath', 'Image de base')
            ->setBasePath('/uploads/data-sources')
            ->setUploadDir('public/uploads/data-sources')
            ->setUploadedFileNamePattern('[slug]-[timestamp].[extension]')
            ->setRequired(false)
            ->hideOnIndex()
            ->setHelp('Téléversez une image de couverture pour illustrer la source dans le catalogue.');

        yield FormField::addTab('Contenu');
        yield TextareaField::new('summary', 'Résumé')->hideOnIndex();
        yield TextareaField::new('description', 'Description')->hideOnIndex();
        yield $this->forTypes(
            TextField::new('sourceUrl', 'URL source')
                ->hideOnIndex()
                ->setHelp('URL de service (WMS/WFS), lien externe de consultation ou endpoint API. Pour une source publique, les hôtes localhost et *.local sont refusés.'),
            [
                DataSource::TYPE_CARTOGRAPHY_LINK,
                DataSource::TYPE_WMS,
                DataSource::TYPE_WFS,
                DataSource::TYPE_DATA_FILE,
            ]
        );
        yield $this->forTypes(
            TextField::new('filePath', 'Fichier')
                ->hideOnIndex()
                ->setHelp('Chemin public du fichier à télécharger. Exemples: /files/datasets/comptages.csv ou /files/datasets/ouvrages.gpkg.'),
            [DataSource::TYPE_DATA_FILE, DataSource::TYPE_STATIC_MAP]
        );
        yield $this->forTypes(
            ChoiceField::new('format', 'Format')
                ->setChoices([
                    'Excel (.xlsx)' => 'xlsx',
                    'CSV (.csv)' => 'csv',
                    'JSON (.json)' => 'json',
                    'GeoJSON (.geojson)' => 'geojson',
                    'GeoPackage (.gpkg)' => 'gpkg',
                    'Shapefile (.zip)' => 'shp',
                    'PDF (.pdf)' => 'pdf',
                    'PNG/JPG (image)' => 'image',
                    'WMS' => 'wms',
                    'WFS' => 'wfs',
                    'Web-SIG' => 'websig',
                    'Autre' => 'other',
                ])
                ->renderAsNativeWidget()
                ->hideOnIndex()
                ->setHelp('Choisissez le format principal de consommation de la source.'),
            [
                DataSource::TYPE_CARTOGRAPHY_LINK,
                DataSource::TYPE_WMS,
                DataSource::TYPE_WFS,
                DataSource::TYPE_DATA_FILE,
                DataSource::TYPE_STATIC_MAP,
            ]
        );
        yield TextField::new('license', 'Licence')->hideOnIndex();

        yield FormField::addTab('Liaisons');
        yield $this->forTypes(
            AssociationField::new('linkedStaticMap', 'Carte statique liée')
                ->hideOnIndex()
                ->setHelp('Utilisez ce lien pour rattacher la source à une fiche cartothèque existante.'),
            [DataSource::TYPE_STATIC_MAP]
        );
        yield $this->forTypes(
            AssociationField::new('linkedInteractiveMap', 'Cartographie liée')
                ->hideOnIndex()
                ->setHelp('Rattachez la source à une carte interactive interne du portail.'),
            [DataSource::TYPE_CARTOGRAPHY_LINK]
        );
        yield $this->forTypes(
            AssociationField::new('serviceEndpoint', 'Endpoint WMS/WFS')
                ->hideOnIndex()
                ->setHelp('Sélectionnez un endpoint de service déjà référencé si disponible.'),
            [DataSource::TYPE_WMS, DataSource::TYPE_WFS]
        );

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

    private function forTypes(FieldInterface $field, array $types): FieldInterface
    {
        $field->setFormTypeOption('row_attr', [
            'class' => 'js-data-source-field',
            'data-source-types' => implode(',', $types),
        ]);

        return $field;
    }

    /**
     * @return array<string, string>
     */
    private function themeChoices(): array
    {
        $choices = $this->taxonomyTermRepository->findMapThemeChoicesForSelect();

        foreach ($this->dataSourceRepository->findAvailableThemes(VisibilityScope::all()) as $theme) {
            $theme = trim($theme);
            if ($theme !== '') {
                $choices[$theme] = $theme;
            }
        }

        foreach ($this->staticMapRepository->findAvailableThemes(VisibilityScope::all()) as $theme) {
            $theme = trim($theme);
            if ($theme !== '') {
                $choices[$theme] = $theme;
            }
        }

        ksort($choices, \SORT_NATURAL | \SORT_FLAG_CASE);

        return $choices;
    }
}
