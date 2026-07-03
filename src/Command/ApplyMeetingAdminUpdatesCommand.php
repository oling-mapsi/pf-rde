<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Access\Entity\User;
use App\Domain\Content\Entity\HomepageSection;
use App\Domain\Content\Entity\Page;
use App\Domain\Taxonomy\MapThemeCatalog;
use App\Domain\Taxonomy\Entity\TaxonomyTerm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:content:apply-meeting-admin-updates',
    description: 'Aligne les contenus administrables de la home avec les arbitrages de réunion.',
)]
final class ApplyMeetingAdminUpdatesCommand extends Command
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

        $this->upsertEditablePages($admin, $publishedAt);
        $this->alignHomepageSections($admin, $publishedAt);
        $this->alignFeaturedThemes();

        $this->entityManager->flush();
        $io->success('Contenus administrables alignés pour validation locale.');

        return Command::SUCCESS;
    }

    private function upsertEditablePages(?User $admin, \DateTimeImmutable $publishedAt): void
    {
        $this->upsertPage(
            'sig-innova',
            'SIG-Innova',
            'Page éditable dédiée à SIG-Innova.',
            '<p>Cette page est prête à être complétée depuis l’administration.</p>',
            $admin,
            $publishedAt,
        );

        $this->upsertPage(
            'partenaires',
            'Partenaires',
            'Page éditable dédiée aux partenaires du portail SIGR.',
            '<p>Cette page est prête à être complétée depuis l’administration.</p>',
            $admin,
            $publishedAt,
        );
    }

    private function upsertPage(string $slug, string $title, string $summary, string $content, ?User $admin, \DateTimeImmutable $publishedAt): void
    {
        $repository = $this->entityManager->getRepository(Page::class);
        $page = $repository->findOneBy(['slug' => $slug]) ?? new Page();

        $page
            ->setSlug($slug)
            ->setTitle($title)
            ->setSummary($summary)
            ->setContent($content)
            ->setLegalType(null)
            ->setSystemPage(false)
            ->setStatus('published')
            ->setPublishedAt($page->getPublishedAt() ?? $publishedAt)
            ->setUpdatedBy($admin);

        if ($page->getId() === null) {
            $page->setCreatedBy($admin);
            $this->entityManager->persist($page);
        }
    }

    private function alignHomepageSections(?User $admin, \DateTimeImmutable $publishedAt): void
    {
        foreach ([
            'Accès rapides SIGR',
            'Accès principaux',
            'Thématiques SIGR',
            'Visualisations à la une',
            'Ressources de référence',
            'Utiliser les données',
            'Message institutionnel',
            'Partenaires et financeurs',
            'Actualités',
            'Actualités et communiqués',
        ] as $draftName) {
            $section = $this->entityManager->getRepository(HomepageSection::class)->findOneBy(['name' => $draftName]);
            if ($section instanceof HomepageSection) {
                $section->setStatus('draft')->setUpdatedBy($admin);
            }
        }

        $this->upsertSection([
            'lookupNames' => ['Actualités accueil', 'À la une'],
            'name' => 'Actualités accueil',
            'type' => HomepageSection::TYPE_LATEST_NEWS,
            'position' => 30,
            'title' => 'Actualités',
            'intro' => 'Les informations récentes de Routes de Guadeloupe et du portail SIGR.',
            'ctaLabel' => 'Voir toutes les actualités',
            'ctaUrl' => '/actualites',
            'itemLimit' => 3,
            'layout' => HomepageSection::LAYOUT_GRID,
            'backgroundStyle' => 'light',
        ], $admin, $publishedAt);

        $this->upsertSection([
            'lookupNames' => ['Solliciter le système d’information', 'Demande de cartes et de données'],
            'name' => 'Solliciter le système d’information',
            'type' => HomepageSection::TYPE_REQUEST_GATEWAY,
            'position' => 40,
            'title' => 'Solliciter le système d’information de Routes de Guadeloupe',
            'intro' => 'Choisissez le bon accès selon votre besoin : contact simple, demande de carte ou accès professionnel.',
            'layout' => HomepageSection::LAYOUT_GRID,
            'backgroundStyle' => 'muted',
            'items' => [
                [
                    'title' => 'Contacter le service',
                    'text' => 'Une question, un besoin d’orientation ou une demande générale auprès du service SIGR.',
                    'url' => '/contact',
                    'label' => 'Contacter',
                    'icon' => 'users',
                    'accent' => 'blue',
                ],
                [
                    'title' => 'Demander une carte',
                    'text' => 'Formuler une demande de carte ou de données en tant qu’utilisateur non connecté.',
                    'url' => '/demande/cartes-donnees',
                    'label' => 'Faire une demande',
                    'icon' => 'map',
                    'accent' => 'orange',
                ],
                [
                    'title' => 'Accès professionnel',
                    'text' => 'Se connecter à son compte professionnel pour suivre les demandes et accéder aux services privés.',
                    'url' => '/connexion',
                    'label' => 'Se connecter',
                    'icon' => 'lock',
                    'accent' => 'green',
                ],
            ],
        ], $admin, $publishedAt);

        $this->publishExistingSection('Qui sommes-nous', 50, $admin, $publishedAt);
        $this->publishExistingSection('Partenaires et FEDER', 60, $admin, $publishedAt);
    }

    /** @param array<string, mixed> $config */
    private function upsertSection(array $config, ?User $admin, \DateTimeImmutable $publishedAt): void
    {
        $repository = $this->entityManager->getRepository(HomepageSection::class);
        $section = null;
        foreach ($config['lookupNames'] as $name) {
            $section = $repository->findOneBy(['name' => $name]);
            if ($section instanceof HomepageSection) {
                break;
            }
        }
        $section ??= new HomepageSection();

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
            ->setFiltersConfig($config['filtersConfig'] ?? null)
            ->setStatus('published')
            ->setPublishedAt($section->getPublishedAt() ?? $publishedAt)
            ->setUpdatedBy($admin);

        if ($section->getId() === null) {
            $section->setCreatedBy($admin);
            $this->entityManager->persist($section);
        }
    }

    private function publishExistingSection(string $name, int $position, ?User $admin, \DateTimeImmutable $publishedAt): void
    {
        $section = $this->entityManager->getRepository(HomepageSection::class)->findOneBy(['name' => $name]);
        if (!$section instanceof HomepageSection) {
            return;
        }

        $section
            ->setPosition($position)
            ->setStatus('published')
            ->setPublishedAt($section->getPublishedAt() ?? $publishedAt)
            ->setUpdatedBy($admin);
    }

    private function alignFeaturedThemes(): void
    {
        $featuredSlugs = MapThemeCatalog::featuredSlugs();

        $repository = $this->entityManager->getRepository(TaxonomyTerm::class);
        $themes = $repository->findActiveMapThemes();
        foreach ($themes as $theme) {
            $position = array_search($theme->getSlug(), $featuredSlugs, true);
            if ($position === false) {
                continue;
            }

            $theme
                ->setFeaturedOnHomepage(true)
                ->setPosition(((int) $position + 1) * 10);
        }
    }

    /** @param mixed $value */
    private function encodeJson($value): string
    {
        return (string) json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
