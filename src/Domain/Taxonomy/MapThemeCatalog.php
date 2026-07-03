<?php

declare(strict_types=1);

namespace App\Domain\Taxonomy;

use Symfony\Component\String\Slugger\AsciiSlugger;

final class MapThemeCatalog
{
    /**
     * @return list<array{
     *   slug: string,
     *   label: string,
     *   description: string,
     *   icon: string,
     *   color: string,
     *   position: int
     * }>
     */
    public static function definitions(): array
    {
        return [
            ['slug' => 'chaussees-et-accotements', 'label' => 'Chaussées et accotements', 'description' => 'Données relatives au réseau et aux dépendances immédiates.', 'icon' => 'route', 'color' => '#FC5000', 'position' => 10],
            ['slug' => 'mobilite', 'label' => 'Mobilité', 'description' => 'Aménagements cyclables, arrêts de transports en commun et déplacements.', 'icon' => 'transport', 'color' => '#38B4E7', 'position' => 20],
            ['slug' => 'ouvrages-art', 'label' => 'Ouvrages d’art', 'description' => 'Ponts, ouvrages et informations de suivi patrimonial.', 'icon' => 'bridge', 'color' => '#15366F', 'position' => 30],
            ['slug' => 'equipements-securite', 'label' => 'Équipements de sécurité', 'description' => 'Signalisation et dispositifs de sécurité routière.', 'icon' => 'shield', 'color' => '#AAAE02', 'position' => 40],
            ['slug' => 'dependances-vertes-bleues', 'label' => 'Dépendances vertes et bleues', 'description' => 'Espaces végétalisés, hydraulique et abords du domaine routier.', 'icon' => 'globe', 'color' => '#1F8A5B', 'position' => 50],
            ['slug' => 'circulation-routiere', 'label' => 'Circulation routière', 'description' => 'Vitesses, limites d’agglomération, trafic et informations de circulation.', 'icon' => 'traffic', 'color' => '#FBD002', 'position' => 60],
            ['slug' => 'milieu-environnant', 'label' => 'Milieu environnant', 'description' => 'Contexte territorial, risques et contraintes externes au réseau.', 'icon' => 'map-pin', 'color' => '#725AC1', 'position' => 70],
            ['slug' => 'referentiels-croises', 'label' => 'Référentiels croisés', 'description' => 'Référentiels transverses pour croiser les analyses.', 'icon' => 'layers', 'color' => '#2D6CDF', 'position' => 80],
        ];
    }

    /** @return list<string> */
    public static function featuredSlugs(): array
    {
        return array_column(self::definitions(), 'slug');
    }

    public static function labelForSlug(string $slug): string
    {
        foreach (self::definitions() as $definition) {
            if ($definition['slug'] === $slug) {
                return $definition['label'];
            }
        }

        return $slug;
    }

    /**
     * @return array{
     *   slug: string,
     *   label: string,
     *   description: string,
     *   icon: string,
     *   color: string,
     *   position: int
     * }|null
     */
    public static function definitionForLabel(string $label): ?array
    {
        $normalized = self::normalizeKey($label);

        foreach (self::definitions() as $definition) {
            if (self::normalizeKey($definition['label']) === $normalized) {
                return $definition;
            }
        }

        return null;
    }

    public static function normalizeLabel(string $label): string
    {
        $normalized = self::normalizeKey($label);

        return match ($normalized) {
            'mobilite' => 'Mobilité',
            'territoire' => 'Milieu environnant',
            'patrimoine' => 'Ouvrages d’art',
            'securite-routiere' => 'Équipements de sécurité',
            default => self::definitionForLabel($label)['label'] ?? preg_replace('/\s+/', ' ', trim($label)) ?? trim($label),
        };
    }

    private static function normalizeKey(string $label): string
    {
        $clean = preg_replace('/\s+/', ' ', trim($label)) ?? trim($label);
        $slug = (new AsciiSlugger())->slug($clean)->lower()->toString();

        return $slug;
    }
}
