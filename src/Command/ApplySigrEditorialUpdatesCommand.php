<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Access\Entity\User;
use App\Domain\Content\Entity\HomepageContent;
use App\Domain\Content\Entity\HomepageSection;
use App\Domain\Content\Entity\News;
use App\Domain\Content\Entity\Page;
use App\Domain\Taxonomy\Entity\TaxonomyTerm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:content:apply-sigr-editorial-updates',
    description: 'Applique les évolutions éditoriales SIGR/FEDER validées pour le portail.',
)]
final class ApplySigrEditorialUpdatesCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $admin = $this->entityManager->getRepository(User::class)->findOneBy([], ['id' => 'ASC']);
        $publishedAt = new \DateTimeImmutable();

        $this->upsertHomepageContent($admin, $publishedAt);
        $this->upsertHomepageSections($admin, $publishedAt);
        $this->upsertPresentationPage($admin, $publishedAt);
        $this->upsertNews($admin, $publishedAt);
        $this->upsertMapThemes();

        $this->entityManager->flush();
        $io->success('Contenus SIGR/FEDER appliqués avec succès.');

        return Command::SUCCESS;
    }

    private function upsertHomepageContent(?User $admin, \DateTimeImmutable $publishedAt): void
    {
        $repository = $this->entityManager->getRepository(HomepageContent::class);
        $homepage = $repository->findOneBy(['name' => 'Accueil principal']) ?? new HomepageContent();

        $homepage
            ->setName('Accueil principal')
            ->setHeroTitle("Bienvenue sur le portail SIG\nde Routes de Guadeloupe")
            ->setHeroBaseline("Une plateforme Open Data et SIG.\nCréé en 2007, le syndicat mixte Routes de Guadeloupe s’inscrit dans une démarche de développement solidaire et équitable pour la gestion, l’entretien et l’exploitation des routes nationales et départementales.")
            ->setSearchIntro('Accéder rapidement au catalogue central des données, cartes et ressources SIG.')
            ->setSearchPlaceholder('Rechercher une thématique, une carte ou un jeu de données')
            ->setPrimaryCtaLabel('Accéder au catalogue')
            ->setPrimaryCtaUrl('/donnees-cartes')
            ->setStatus('published')
            ->setPublishedAt($homepage->getPublishedAt() ?? $publishedAt)
            ->setUpdatedBy($admin);

        if ($homepage->getId() === null) {
            $homepage->setCreatedBy($admin);
            $this->entityManager->persist($homepage);
        }
    }

    private function upsertHomepageSections(?User $admin, \DateTimeImmutable $publishedAt): void
    {
        $sections = [
            [
                'name' => 'Accès rapides SIGR',
                'type' => HomepageSection::TYPE_MANUAL_CARDS,
                'position' => 10,
                'title' => 'Accès rapides',
                'layout' => HomepageSection::LAYOUT_GRID,
                'backgroundStyle' => 'kpi',
                'items' => [
                    ['title' => 'Explorer les thématiques', 'url' => '/thematiques', 'label' => 'Accéder aux thématiques SIGR', 'accent' => 'orange', 'icon' => 'layers'],
                    ['title' => 'Accéder au catalogue', 'url' => '/donnees-cartes', 'label' => 'Rechercher dans le catalogue', 'accent' => 'blue', 'icon' => 'search'],
                    ['title' => 'Demander une carte ou une donnée', 'url' => '/contact', 'label' => 'Préparer une demande de carte ou de donnée', 'accent' => 'yellow', 'icon' => 'clipboard'],
                    ['title' => 'Se connecter', 'url' => '/connexion', 'label' => 'Accéder à l’espace agents et comptes privés', 'accent' => 'green', 'icon' => 'lock'],
                ],
            ],
            [
                'name' => 'Thématiques SIGR',
                'type' => HomepageSection::TYPE_MANUAL_CARDS,
                'position' => 20,
                'title' => 'Thématiques',
                'intro' => 'Les données et cartes sont organisées selon les familles métiers du système d’information géographique routier.',
                'layout' => HomepageSection::LAYOUT_GRID,
                'backgroundStyle' => 'light',
                'ctaLabel' => 'Voir toutes les thématiques',
                'ctaUrl' => '/thematiques',
                'items' => [
                    ['title' => 'Chaussées et accotements', 'text' => 'Données relatives au réseau et aux dépendances immédiates.', 'url' => '/donnees-cartes?theme%5B0%5D=Chauss%C3%A9es%20et%20accotements', 'label' => 'Voir les ressources', 'icon' => 'route'],
                    ['title' => 'Mobilité', 'text' => 'Aménagements cyclables, arrêts de transports en commun et déplacements.', 'url' => '/donnees-cartes?theme%5B0%5D=Mobilit%C3%A9', 'label' => 'Voir les ressources', 'icon' => 'transport'],
                    ['title' => 'Circulation routière', 'text' => 'Vitesses, limites d’agglomération, trafic et informations de circulation.', 'url' => '/donnees-cartes?theme%5B0%5D=Circulation%20routi%C3%A8re', 'label' => 'Voir les ressources', 'icon' => 'traffic'],
                ],
            ],
            [
                'name' => 'À la une',
                'type' => HomepageSection::TYPE_MANUAL_CARDS,
                'position' => 30,
                'title' => 'À la une',
                'intro' => 'Accès directs vers les contenus structurants du portail.',
                'layout' => HomepageSection::LAYOUT_GRID,
                'backgroundStyle' => 'institutional',
                'items' => [
                    ['title' => 'Lancement du portail SIG de Routes de Guadeloupe', 'text' => 'Présentation du service, de la démarche Open Data et des nouveaux parcours de consultation.', 'imagePath' => '/images/hero-guadeloupe-map-v5.png', 'url' => '/pages/presentation-portail', 'label' => 'Découvrir le portail'],
                    ['title' => 'Carte du réseau routier géré par Routes de Guadeloupe', 'text' => 'Accès direct aux cartes et données issues de la thématique Chaussées et accotements.', 'imagePath' => '/images/hero-guadeloupe-map-v4.png', 'url' => '/donnees-cartes?q=réseau routier', 'label' => 'Consulter la carte'],
                    ['title' => 'Vitesse limite autorisée et limites d’agglomération', 'text' => 'Accès aux ressources de la thématique Circulation routière.', 'imagePath' => '/images/hero-guadeloupe-map-v6.png', 'url' => '/donnees-cartes?q=vitesse limites agglomération', 'label' => 'Voir les données'],
                ],
            ],
            [
                'name' => 'Demande de cartes et de données',
                'type' => HomepageSection::TYPE_MESSAGE,
                'position' => 40,
                'title' => 'Solliciter le SIG de Routes de Guadeloupe',
                'body' => '<p>Les parcours de demande de cartographies et de données géographiques seront organisés par profil : agents internes, professionnels disposant d’un compte et utilisateurs grand public.</p><p>Dans l’attente de la refonte complète des formulaires, les demandes peuvent être orientées vers le point de contact du portail.</p>',
                'imagePath' => '/images/hero-guadeloupe-map-v3.png',
                'ctaLabel' => 'Contacter le service',
                'ctaUrl' => '/contact',
                'layout' => HomepageSection::LAYOUT_FEATURE,
                'backgroundStyle' => 'muted',
            ],
            [
                'name' => 'Qui sommes-nous',
                'type' => HomepageSection::TYPE_MESSAGE,
                'position' => 50,
                'title' => 'Qui sommes-nous ?',
                'body' => '<p>Au sein de la Direction des Études, de l’Aménagement et de la Prospective, le service Système d’Information Géographique Routier administre, gère, exploite et diffuse les données géographiques routières du domaine public lié aux routes nationales et départementales.</p>',
                'imagePath' => '/images/logo-rdg.jpg',
                'ctaLabel' => 'Nous sommes Routes de Guadeloupe',
                'ctaUrl' => '/pages/presentation-portail',
                'layout' => HomepageSection::LAYOUT_FEATURE,
                'backgroundStyle' => 'light',
            ],
            [
                'name' => 'Partenaires et FEDER',
                'type' => HomepageSection::TYPE_SPONSOR,
                'position' => 60,
                'title' => 'Partenaires et financements européens',
                'body' => '<p>Le portail s’inscrit dans une opération de transformation numérique du Système d’Information Géographique Routier cofinancée par l’Union européenne via le FEDER.</p>',
                'imagePath' => '/images/bloc-marque-feder.png',
                'ctaLabel' => 'Consulter la rubrique partenaires',
                'ctaUrl' => '/partenaires-financeurs',
                'layout' => HomepageSection::LAYOUT_BANNER,
                'backgroundStyle' => 'muted',
            ],
            [
                'name' => 'Actualités',
                'type' => HomepageSection::TYPE_LATEST_NEWS,
                'position' => 70,
                'title' => 'Actualités',
                'intro' => 'Les dernières informations publiques liées au portail, aux données et aux services SIG.',
                'ctaLabel' => 'Voir toutes les actualités',
                'ctaUrl' => '/actualites',
                'itemLimit' => 3,
                'layout' => HomepageSection::LAYOUT_GRID,
                'backgroundStyle' => 'light',
            ],
        ];

        foreach ($sections as $config) {
            $this->upsertHomepageSection($config, $admin, $publishedAt);
        }

        foreach (['Accès principaux', 'Visualisations à la une', 'Ressources de référence', 'Utiliser les données', 'Message institutionnel', 'Partenaires et financeurs', 'Actualités et communiqués'] as $legacyName) {
            $legacy = $this->entityManager->getRepository(HomepageSection::class)->findOneBy(['name' => $legacyName]);
            if ($legacy instanceof HomepageSection) {
                $legacy->setStatus('draft')->setUpdatedBy($admin);
            }
        }
    }

    /** @param array<string, mixed> $config */
    private function upsertHomepageSection(array $config, ?User $admin, \DateTimeImmutable $publishedAt): void
    {
        $repository = $this->entityManager->getRepository(HomepageSection::class);
        $section = $repository->findOneBy(['name' => $config['name']]) ?? new HomepageSection();

        $section
            ->setName($config['name'])
            ->setType($config['type'])
            ->setPosition($config['position'])
            ->setTitle($config['title'])
            ->setIntro($config['intro'] ?? null)
            ->setBody($config['body'] ?? null)
            ->setImagePath($config['imagePath'] ?? null)
            ->setCtaLabel($config['ctaLabel'] ?? null)
            ->setCtaUrl($config['ctaUrl'] ?? null)
            ->setLayout($config['layout'] ?? HomepageSection::LAYOUT_GRID)
            ->setBackgroundStyle($config['backgroundStyle'] ?? 'light')
            ->setItemLimit($config['itemLimit'] ?? 3)
            ->setItemsConfig(isset($config['items']) ? $this->encodeJson($config['items']) : null)
            ->setStatus('published')
            ->setPublishedAt($section->getPublishedAt() ?? $publishedAt)
            ->setUpdatedBy($admin);

        if ($section->getId() === null) {
            $section->setCreatedBy($admin);
            $this->entityManager->persist($section);
        }
    }

    private function upsertPresentationPage(?User $admin, \DateTimeImmutable $publishedAt): void
    {
        $repository = $this->entityManager->getRepository(Page::class);
        $page = $repository->findOneBy(['slug' => 'presentation-portail']) ?? new Page();

        $page
            ->setSlug('presentation-portail')
            ->setTitle('Nous sommes Routes de Guadeloupe')
            ->setSummary('Présentation de Routes de Guadeloupe, du service SIGR, de la démarche Open Data et des partenaires.')
            ->setContent('<p>Le contenu détaillé de cette page est structuré dans le gabarit public afin de proposer une lecture claire : Routes de Guadeloupe, démarche Open Data, services SIGR et partenaires.</p>')
            ->setStatus('published')
            ->setPublishedAt($page->getPublishedAt() ?? $publishedAt)
            ->setUpdatedBy($admin);

        if ($page->getId() === null) {
            $page->setCreatedBy($admin);
            $this->entityManager->persist($page);
        }
    }

    private function upsertNews(?User $admin, \DateTimeImmutable $publishedAt): void
    {
        $repository = $this->entityManager->getRepository(News::class);
        $news = $repository->findOneBy(['slug' => 'lancement-portail-sigr-routes-guadeloupe']) ?? new News();
        $news
            ->setSlug('lancement-portail-sigr-routes-guadeloupe')
            ->setTitle('Lancement du portail SIG de Routes de Guadeloupe')
            ->setSummary('Un point d’accès public pour consulter les données, cartes et services SIGR.')
            ->setBody('Le portail SIG de Routes de Guadeloupe regroupe les accès au catalogue central, aux thématiques routières et aux premiers parcours de demande de cartes et de données.')
            ->setStatus('published')
            ->setPublishedAt($news->getPublishedAt() ?? $publishedAt)
            ->setUpdatedBy($admin);

        if ($news->getId() === null) {
            $news->setCreatedBy($admin);
            $this->entityManager->persist($news);
        }
    }

    private function upsertMapThemes(): void
    {
        $themes = [
            ['chaussees-et-accotements', 'Chaussées et accotements', 'Données relatives au réseau et aux dépendances immédiates.', 'route', '#FC5000', 10],
            ['mobilite', 'Mobilité', 'Aménagements cyclables, arrêts de transports en commun et déplacements.', 'transport', '#38B4E7', 20],
            ['ouvrages-art', 'Ouvrages d’art', 'Ponts, ouvrages et informations de suivi patrimonial.', 'bridge', '#15366F', 30],
            ['equipements-securite', 'Équipements de sécurité', 'Signalisation et dispositifs de sécurité routière.', 'shield', '#AAAE02', 40],
            ['dependances-vertes-bleues', 'Dépendances vertes et bleues', 'Espaces végétalisés, hydraulique et abords du domaine routier.', 'globe', '#1F8A5B', 50],
            ['circulation-routiere', 'Circulation routière', 'Vitesses, limites d’agglomération, trafic et informations de circulation.', 'traffic', '#FBD002', 60],
            ['milieu-environnant', 'Milieu environnant', 'Contexte territorial, risques et contraintes externes au réseau.', 'map-pin', '#725AC1', 70],
            ['referentiels-croises', 'Référentiels croisés', 'Référentiels transverses pour croiser les analyses.', 'layers', '#2D6CDF', 80],
        ];

        $repository = $this->entityManager->getRepository(TaxonomyTerm::class);
        foreach ($themes as [$slug, $label, $description, $icon, $color, $position]) {
            $term = $repository->findOneBy(['taxonomy' => TaxonomyTerm::MAP_THEME_TAXONOMY, 'slug' => $slug])
                ?? $repository->findOneBy(['taxonomy' => TaxonomyTerm::MAP_THEME_TAXONOMY, 'label' => $label])
                ?? new TaxonomyTerm();

            $term
                ->setTaxonomy(TaxonomyTerm::MAP_THEME_TAXONOMY)
                ->setSlug($slug)
                ->setLabel($label)
                ->setDescription($description)
                ->setIconKey($icon)
                ->setColorHex($color)
                ->setPosition($position)
                ->setFeaturedOnHomepage(true)
                ->setActive(true);

            if ($term->getId() === null) {
                $this->entityManager->persist($term);
            }
        }
    }

    /** @param mixed $value */
    private function encodeJson($value): string
    {
        return (string) json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
