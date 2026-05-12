<?php

declare(strict_types=1);

namespace App\UI\Controller\Admin;

use App\Domain\Content\Entity\HomepageContent;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;

final class HomepageContentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return HomepageContent::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Héros de la page d’accueil')
            ->setEntityLabelInPlural('Héros de la page d’accueil')
            ->setDefaultSort(['updatedAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addTab('Héros');
        yield TextField::new('name', 'Nom interne');
        yield TextField::new('heroTitle', 'Titre principal')->setColumns(12);
        yield TextareaField::new('heroBaseline', 'Texte d’introduction')->setNumOfRows(4)->setColumns(12);

        yield FormField::addTab('Recherche et CTA');
        yield TextField::new('searchIntro', 'Texte au-dessus de la recherche')->setColumns(6);
        yield TextField::new('searchPlaceholder', 'Placeholder de recherche')->setColumns(6);
        yield TextField::new('primaryCtaLabel', 'Libellé du bouton principal')->hideOnIndex()->setColumns(6);
        yield TextField::new('primaryCtaUrl', 'URL du bouton principal')->hideOnIndex()->setColumns(6);

        yield FormField::addTab('Publication');
        yield TextField::new('status', 'Statut');
        yield DateTimeField::new('publishedAt', 'Date de publication')->hideOnIndex();
    }
}
