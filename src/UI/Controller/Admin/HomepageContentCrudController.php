<?php

declare(strict_types=1);

namespace App\UI\Controller\Admin;

use App\Domain\Content\Entity\HomepageContent;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;

final class HomepageContentCrudController extends AbstractCrudController
{
    private const COLOR_TOKEN_CHOICES = [
        'Bleu principal' => 'var(--color-brand-primary)',
        'Bleu secondaire' => 'var(--color-brand-secondary)',
        'Orange RDG' => 'var(--rdg-orange-road)',
        'Accent jaune' => 'var(--color-brand-accent)',
        'Vert succès' => 'var(--color-success)',
        'Texte standard' => 'var(--color-text-default)',
        'Texte titre' => 'var(--color-text-heading)',
        'Texte atténué' => 'var(--color-text-muted)',
        'Texte inverse' => 'var(--color-text-inverse)',
        'Fond défaut' => 'var(--color-bg-default)',
        'Fond doux' => 'var(--color-bg-surface-alt)',
        'Bordure standard' => 'var(--color-border-default)',
        'Bordure focus' => 'var(--color-border-focus)',
        'Bouton primaire fond' => 'var(--component-button-primary-bg)',
        'Bouton primaire texte' => 'var(--component-button-primary-text)',
        'Bouton outline fond' => 'var(--component-button-outline-bg)',
        'Bouton outline texte' => 'var(--component-button-outline-text)',
    ];

    private const TITLE_SIZE_CHOICES = [
        'H1' => 'var(--font-size-h1)',
        'H2' => 'var(--font-size-h2)',
        'H3' => 'var(--font-size-h3)',
        'H4' => 'var(--font-size-h4)',
    ];

    private const BODY_SIZE_CHOICES = [
        'Texte courant' => 'var(--font-size-body)',
        'H6' => 'var(--font-size-h6)',
        'H5' => 'var(--font-size-h5)',
        'H4' => 'var(--font-size-h4)',
    ];

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
        yield TextareaField::new('heroTitle', 'Titre principal')
            ->setHelp('Un retour à la ligne = une ligne affichée dans le hero.')
            ->setNumOfRows(3)
            ->setColumns(12);
        yield TextareaField::new('heroBaseline', 'Texte d’introduction')->setNumOfRows(4)->setColumns(12);
        yield ImageField::new('heroBackgroundImagePath', 'Image de fond du héros')
            ->setBasePath('/uploads/content')
            ->setUploadDir('public/uploads/content')
            ->setUploadedFileNamePattern('hero-home-[timestamp].[extension]')
            ->setRequired(false)
            ->hideOnIndex()
            ->setHelp('Téléversez une image de fond pour le héros d’accueil.');
        yield ChoiceField::new('heroTitleColor', 'Couleur du titre')->setChoices(self::COLOR_TOKEN_CHOICES)->setRequired(false)->setFormTypeOption('placeholder', 'Défaut du composant')->hideOnIndex()->setColumns(3);
        yield ChoiceField::new('heroBaselineColor', 'Couleur du texte d’introduction')->setChoices(self::COLOR_TOKEN_CHOICES)->setRequired(false)->setFormTypeOption('placeholder', 'Défaut du composant')->hideOnIndex()->setColumns(3);
        yield ChoiceField::new('heroTitleFontSize', 'Taille du titre')->setChoices(self::TITLE_SIZE_CHOICES)->setRequired(false)->setFormTypeOption('placeholder', 'Défaut du composant')->hideOnIndex()->setColumns(3);
        yield ChoiceField::new('heroBaselineFontSize', 'Taille du texte d’introduction')->setChoices(self::BODY_SIZE_CHOICES)->setRequired(false)->setFormTypeOption('placeholder', 'Défaut du composant')->hideOnIndex()->setColumns(3);

        yield FormField::addTab('Recherche et CTA');
        yield TextField::new('primaryCtaLabel', 'Libellé du bouton principal')->hideOnIndex()->setColumns(6);
        yield TextField::new('primaryCtaUrl', 'URL du bouton principal')->hideOnIndex()->setColumns(6);
        yield ChoiceField::new('heroSearchBackgroundColor', 'Fond du formulaire')->setChoices(self::COLOR_TOKEN_CHOICES)->setRequired(false)->setFormTypeOption('placeholder', 'Défaut du composant')->hideOnIndex()->setColumns(3);
        yield ChoiceField::new('heroSearchBorderColor', 'Bordure du formulaire')->setChoices(self::COLOR_TOKEN_CHOICES)->setRequired(false)->setFormTypeOption('placeholder', 'Défaut du composant')->hideOnIndex()->setColumns(3);
        yield ChoiceField::new('heroSearchTextColor', 'Couleur du texte du formulaire')->setChoices(self::COLOR_TOKEN_CHOICES)->setRequired(false)->setFormTypeOption('placeholder', 'Défaut du composant')->hideOnIndex()->setColumns(3);
        yield ChoiceField::new('heroSearchPlaceholderColor', 'Couleur du placeholder')->setChoices(self::COLOR_TOKEN_CHOICES)->setRequired(false)->setFormTypeOption('placeholder', 'Défaut du composant')->hideOnIndex()->setColumns(3);
        yield ChoiceField::new('heroSearchButtonBackgroundColor', 'Fond du bouton de recherche')->setChoices(self::COLOR_TOKEN_CHOICES)->setRequired(false)->setFormTypeOption('placeholder', 'Défaut du composant')->hideOnIndex()->setColumns(3);
        yield ChoiceField::new('heroSearchButtonColor', 'Couleur du bouton de recherche')->setChoices(self::COLOR_TOKEN_CHOICES)->setRequired(false)->setFormTypeOption('placeholder', 'Défaut du composant')->hideOnIndex()->setColumns(3);
        yield ChoiceField::new('heroPrimaryCtaBackgroundColor', 'Fond du CTA principal')->setChoices(self::COLOR_TOKEN_CHOICES)->setRequired(false)->setFormTypeOption('placeholder', 'Défaut du composant')->hideOnIndex()->setColumns(3);
        yield ChoiceField::new('heroPrimaryCtaTextColor', 'Texte du CTA principal')->setChoices(self::COLOR_TOKEN_CHOICES)->setRequired(false)->setFormTypeOption('placeholder', 'Défaut du composant')->hideOnIndex()->setColumns(3);

        yield FormField::addTab('Boutons des thèmes');
        yield TextField::new('heroThemesGap', 'Espacement entre les boutons')->hideOnIndex()->setHelp('Ex: 1rem 2rem ou 1.25rem')->setColumns(4);
        yield TextField::new('heroThemeButtonPadding', 'Padding des boutons')->hideOnIndex()->setHelp('Ex: 0.5rem 0.75rem')->setColumns(4);
        yield TextField::new('heroThemeButtonRadius', 'Rayon des boutons')->hideOnIndex()->setHelp('Ex: 14px, 1rem')->setColumns(4);
        yield ChoiceField::new('heroThemeLabelColor', 'Couleur du texte des boutons')->setChoices(self::COLOR_TOKEN_CHOICES)->setRequired(false)->setFormTypeOption('placeholder', 'Défaut du composant')->hideOnIndex()->setColumns(4);

        yield FormField::addTab('Publication');
        yield TextField::new('status', 'Statut');
        yield DateTimeField::new('publishedAt', 'Date de publication')->hideOnIndex();
    }
}
