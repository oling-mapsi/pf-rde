<?php

declare(strict_types=1);

namespace App\UI\Controller\Admin;

use App\Domain\Content\Entity\HomepageSection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Asset;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class HomepageSectionCrudController extends AbstractCrudController
{
    public function __construct(private readonly CsrfTokenManagerInterface $csrfTokenManager)
    {
    }

    public static function getEntityFqcn(): string
    {
        return HomepageSection::class;
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addJsFile(Asset::new('/vendor/tinymce/tinymce.min.js')->onlyOnForms())
            ->addCssFile(Asset::new('/admin-assets/page-rich-text.css')->onlyOnForms())
            ->addJsFile(Asset::new('/admin-assets/page-rich-text.js')->onlyOnForms()->defer());
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Section d’accueil')
            ->setEntityLabelInPlural('Sections d’accueil')
            ->setDefaultSort(['position' => 'ASC'])
            ->setPaginatorPageSize(50);
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addTab('Structure');
        yield TextField::new('name', 'Nom interne')->setColumns(6);
        yield ChoiceField::new('type', 'Type de section')
            ->setChoices([
                'Cartes libres configurées' => HomepageSection::TYPE_MANUAL_CARDS,
                'Actualités récentes' => HomepageSection::TYPE_LATEST_NEWS,
                'Ressources du catalogue' => HomepageSection::TYPE_FEATURED_RESOURCES,
                'Liens rapides' => HomepageSection::TYPE_QUICK_LINKS,
                'Message éditorial' => HomepageSection::TYPE_MESSAGE,
                'Sponsor / partenaire' => HomepageSection::TYPE_SPONSOR,
                'Chiffres clés data' => HomepageSection::TYPE_DATA_HIGHLIGHTS,
                'Sollicitation SIGR' => HomepageSection::TYPE_REQUEST_GATEWAY,
            ])
            ->setColumns(6);
        yield IntegerField::new('position', 'Ordre')->setColumns(4);
        yield ChoiceField::new('layout', 'Mise en page')
            ->setChoices([
                'Grille de cartes' => HomepageSection::LAYOUT_GRID,
                'Mise en avant image + texte' => HomepageSection::LAYOUT_FEATURE,
                'Bannière pleine largeur' => HomepageSection::LAYOUT_BANNER,
            ])
            ->setColumns(4);
        yield ChoiceField::new('backgroundStyle', 'Ambiance')
            ->setChoices([
                'Clair' => 'light',
                'Fond secondaire' => 'muted',
                'Institutionnel' => 'institutional',
                'Accès rapides' => 'kpi',
            ])
            ->setColumns(4);

        yield FormField::addTab('Contenu WYSIWYG');
        yield TextField::new('title', 'Titre public')->setColumns(12);
        yield TextareaField::new('intro', 'Introduction courte')->hideOnIndex()->setNumOfRows(3)->setColumns(12);
        yield TextareaField::new('body', 'Contenu riche')
            ->hideOnIndex()
            ->setNumOfRows(18)
            ->setColumns(12)
            ->setHelp('Éditeur avancé PF : tableaux, images, colonnes et blocs éditoriaux réutilisables.')
            ->setFormTypeOption('attr.data-rich-text-upload-url', $this->generateUrl('admin_page_content_image_upload'))
            ->setFormTypeOption('attr.data-rich-text-upload-token', (string) $this->csrfTokenManager->getToken('page_content_image_upload'))
            ->setFormTypeOption('attr.data-admin-rich-text', 'site');
        yield TextField::new('imagePath', 'Image')->hideOnIndex()->setHelp('Chemin public ou URL, ex. /images/hero-guadeloupe-map-v5.png')->setColumns(6);
        yield TextField::new('ctaLabel', 'Libellé du bouton')->hideOnIndex()->setColumns(3);
        yield TextField::new('ctaUrl', 'URL du bouton')->hideOnIndex()->setColumns(3);

        yield FormField::addTab('Source dynamique');
        yield IntegerField::new('itemLimit', 'Nombre d’éléments')->setColumns(3);
        yield CodeEditorField::new('filtersConfig', 'Filtres JSON')->hideOnIndex()
            ->setLanguage('js')
            ->setHelp('Pour les ressources : {"theme":"Mobilité"} ou {"query":"travaux"}.')
            ->setColumns(12);
        yield CodeEditorField::new('itemsConfig', 'Cartes manuelles JSON')->hideOnIndex()
            ->setLanguage('js')
            ->setHelp('[{"title":"Titre","text":"Texte","imagePath":"/images/...","url":"/page","label":"Lire","icon":"map","accent":"orange"}]')
            ->setColumns(12);

        yield FormField::addTab('Publication');
        yield TextField::new('status', 'Statut');
        yield DateTimeField::new('publishedAt', 'Date de publication')->hideOnIndex();
    }
}
