<?php

declare(strict_types=1);

namespace App\UI\Controller\Admin;

final class AdminColorPalette
{
    public const TOKEN_CHOICES = [
        'Bleu principal' => 'var(--color-brand-primary)',
        'Bleu secondaire' => 'var(--color-brand-secondary)',
        'Accent jaune' => 'var(--color-brand-accent)',
        'Vert succès' => 'var(--color-success)',
        'Orange RDG' => 'var(--rdg-orange-road)',
        'Titres' => 'var(--color-text-heading)',
        'Paragraphes / texte courant' => 'var(--color-text-default)',
        'Texte atténué' => 'var(--color-text-muted)',
        'Texte inverse' => 'var(--color-text-inverse)',
        'Fond principal' => 'var(--color-bg-default)',
        'Fond secondaire' => 'var(--color-bg-surface-alt)',
        'Bordure standard' => 'var(--color-border-default)',
        'Bordure focus / accent actif' => 'var(--color-border-focus)',
        'Lien standard' => 'var(--color-link)',
        'Lien au survol' => 'var(--color-link-hover)',
        'Bouton primaire fond' => 'var(--component-button-primary-bg)',
        'Bouton primaire bordure' => 'var(--component-button-primary-border)',
        'Bouton primaire texte' => 'var(--component-button-primary-text)',
        'Bouton primaire fond au survol' => 'var(--component-button-primary-bg-hover)',
        'Bouton primaire bordure au survol' => 'var(--component-button-primary-border-hover)',
        'Bouton primaire texte au survol' => 'var(--component-button-primary-text-hover)',
        'Bouton outline fond' => 'var(--component-button-outline-bg)',
        'Bouton outline bordure' => 'var(--component-button-outline-border)',
        'Bouton outline texte' => 'var(--component-button-outline-text)',
        'Bouton outline fond au survol' => 'var(--component-button-outline-bg-hover)',
        'Bouton outline bordure au survol' => 'var(--component-button-outline-border-hover)',
        'Bouton outline texte au survol' => 'var(--component-button-outline-text-hover)',
    ];
}
