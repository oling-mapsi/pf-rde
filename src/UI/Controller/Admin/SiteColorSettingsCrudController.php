<?php

declare(strict_types=1);

namespace App\UI\Controller\Admin;

use App\Domain\Content\Entity\HomepageContent;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ColorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;

final class SiteColorSettingsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return HomepageContent::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Couleurs du site')
            ->setEntityLabelInPlural('Couleurs du site')
            ->setPageTitle(Crud::PAGE_EDIT, 'Couleurs du site')
            ->setPageTitle(Crud::PAGE_NEW, 'Couleurs du site');
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addTab('Palette globale');
        yield FormField::addFieldset('Couleurs de marque');
        yield ColorField::new('brandPrimaryColor', 'Bleu principal')->showValue()->hideOnIndex()->setColumns(3);
        yield ColorField::new('brandSecondaryColor', 'Bleu secondaire')->showValue()->hideOnIndex()->setColumns(3);
        yield ColorField::new('brandAccentColor', 'Accent jaune')->showValue()->hideOnIndex()->setColumns(3);
        yield ColorField::new('brandSuccessColor', 'Vert succès')->showValue()->hideOnIndex()->setColumns(3);
        yield ColorField::new('brandOrangeRoadColor', 'Orange RDG')->showValue()->hideOnIndex()->setColumns(3);

        yield FormField::addFieldset('Textes');
        yield ColorField::new('textHeadingColor', 'Titres')->showValue()->hideOnIndex()->setColumns(3);
        yield ColorField::new('textDefaultColor', 'Paragraphes / texte courant')->showValue()->hideOnIndex()->setColumns(3);
        yield ColorField::new('textMutedColor', 'Texte atténué')->showValue()->hideOnIndex()->setColumns(3);
        yield ColorField::new('textInverseColor', 'Texte inverse')->showValue()->hideOnIndex()->setColumns(3);

        yield FormField::addFieldset('Fonds et bordures');
        yield ColorField::new('backgroundDefaultColor', 'Fond principal')->showValue()->hideOnIndex()->setColumns(3);
        yield ColorField::new('backgroundSurfaceAltColor', 'Fond secondaire')->showValue()->hideOnIndex()->setColumns(3);
        yield ColorField::new('borderDefaultColor', 'Bordure standard')->showValue()->hideOnIndex()->setColumns(3);
        yield ColorField::new('borderFocusColor', 'Bordure focus / accent actif')->showValue()->hideOnIndex()->setColumns(3);

        yield FormField::addFieldset('Liens');
        yield ColorField::new('linkColor', 'Lien standard')->showValue()->hideOnIndex()->setColumns(3);
        yield ColorField::new('linkHoverColor', 'Lien au survol')->showValue()->hideOnIndex()->setColumns(3);

        yield FormField::addFieldset('Bouton primaire');
        yield ColorField::new('buttonPrimaryBackgroundColor', 'Fond')->showValue()->hideOnIndex()->setColumns(3);
        yield ColorField::new('buttonPrimaryBorderColor', 'Bordure')->showValue()->hideOnIndex()->setColumns(3);
        yield ColorField::new('buttonPrimaryTextColor', 'Texte')->showValue()->hideOnIndex()->setColumns(3);
        yield ColorField::new('buttonPrimaryBackgroundHoverColor', 'Fond au survol')->showValue()->hideOnIndex()->setColumns(3);
        yield ColorField::new('buttonPrimaryBorderHoverColor', 'Bordure au survol')->showValue()->hideOnIndex()->setColumns(3);
        yield ColorField::new('buttonPrimaryTextHoverColor', 'Texte au survol')->showValue()->hideOnIndex()->setColumns(3);

        yield FormField::addFieldset('Bouton outline');
        yield ColorField::new('buttonOutlineBackgroundColor', 'Fond')->showValue()->hideOnIndex()->setColumns(3);
        yield ColorField::new('buttonOutlineBorderColor', 'Bordure')->showValue()->hideOnIndex()->setColumns(3);
        yield ColorField::new('buttonOutlineTextColor', 'Texte')->showValue()->hideOnIndex()->setColumns(3);
        yield ColorField::new('buttonOutlineBackgroundHoverColor', 'Fond au survol')->showValue()->hideOnIndex()->setColumns(3);
        yield ColorField::new('buttonOutlineBorderHoverColor', 'Bordure au survol')->showValue()->hideOnIndex()->setColumns(3);
        yield ColorField::new('buttonOutlineTextHoverColor', 'Texte au survol')->showValue()->hideOnIndex()->setColumns(3);
    }
}
