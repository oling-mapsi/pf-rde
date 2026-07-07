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
        $this->upsertLegalPages($admin, $publishedAt);
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

    private function upsertLegalPages(?User $admin, \DateTimeImmutable $publishedAt): void
    {
        $this->upsertSystemPage(
            'mentions-legales',
            'Mentions légales',
            'legal_mentions',
            <<<'HTML'
<p>Le portail SIG de Routes de Guadeloupe est un service public numérique destiné à la consultation, à la diffusion et à la valorisation de données géographiques, cartographiques et documentaires liées au réseau routier du territoire.</p>
<h2>Éditeur du site</h2>
<p>Le présent portail est édité par Routes de Guadeloupe, établissement compétent pour la gestion, l’exploitation, l’aménagement et la valorisation du patrimoine routier relevant de son périmètre d’intervention.</p>
<p>Pour toute demande relative au fonctionnement du portail, aux contenus publiés ou à l’exercice de vos droits, vous pouvez utiliser le formulaire de contact mis à disposition sur le site.</p>
<h2>Hébergement</h2>
<p>L’hébergement technique du portail est assuré par un prestataire mandaté pour garantir la disponibilité, la sécurité et la maintenance de l’infrastructure applicative.</p>
<h2>Propriété intellectuelle</h2>
<p>La structure générale du site, les textes, éléments graphiques, photographies, bases documentaires, cartes, traitements visuels, logos, icônes et contenus diffusés sont protégés par les dispositions applicables en matière de propriété intellectuelle.</p>
<p>Sauf mention contraire, toute reproduction, représentation, adaptation, extraction ou réutilisation totale ou partielle sans autorisation préalable est interdite. Les contenus explicitement publiés comme réutilisables au titre de l’open data ou d’une licence particulière demeurent soumis aux conditions de cette licence.</p>
<h2>Données et responsabilité</h2>
<p>Routes de Guadeloupe s’efforce d’assurer l’exactitude et l’actualisation des informations diffusées sur le portail. Malgré ce soin, certaines données peuvent être incomplètes, évoluer dans le temps ou comporter des imprécisions.</p>
<p>Les informations publiées sont fournies à titre informatif. Elles ne sauraient se substituer à une validation opérationnelle, technique, réglementaire ou juridique préalable à toute décision, étude ou intervention.</p>
<p>Routes de Guadeloupe ne pourra être tenue responsable des dommages directs ou indirects résultant de l’usage du portail, de l’interprétation des données mises à disposition, d’une indisponibilité temporaire du service ou de la présence de liens vers des ressources externes.</p>
<h2>Liens hypertextes</h2>
<p>Le portail peut contenir des liens vers des sites tiers. Routes de Guadeloupe n’exerce aucun contrôle sur ces contenus externes et décline toute responsabilité quant à leur disponibilité, leur sécurité ou leur politique de confidentialité.</p>
<h2>Accessibilité et amélioration continue</h2>
<p>Routes de Guadeloupe s’inscrit dans une démarche d’amélioration continue de la qualité éditoriale, de l’accessibilité numérique, de la protection des données et de la fiabilité des services proposés sur le portail.</p>
HTML,
            $admin,
            $publishedAt,
            null,
        );

        $this->upsertSystemPage(
            'politique-confidentialite',
            'Politique de confidentialité',
            'privacy',
            <<<'HTML'
<p>Routes de Guadeloupe attache une importance particulière à la protection des données à caractère personnel traitées dans le cadre du portail SIG. La présente politique a pour objet de vous informer de manière claire sur les traitements susceptibles d’être mis en œuvre lorsque vous naviguez sur le site ou utilisez ses formulaires.</p>
<h2>Principes généraux</h2>
<p>Les traitements de données sont limités à ce qui est strictement nécessaire au fonctionnement du portail, à la gestion des demandes adressées au service, à la sécurisation des accès et, le cas échéant, à la production de statistiques anonymisées lorsque vous y avez consenti.</p>
<h2>Données susceptibles d’être collectées</h2>
<ul>
    <li>données d’identification et de contact transmises volontairement dans les formulaires ;</li>
    <li>contenu des messages, demandes de cartes ou demandes de données ;</li>
    <li>données techniques nécessaires au fonctionnement du service, à la sécurité et à la traçabilité ;</li>
    <li>données liées à l’authentification pour les accès privés ou agents ;</li>
    <li>préférences de consentement relatives aux cookies.</li>
</ul>
<h2>Finalités des traitements</h2>
<ul>
    <li>répondre aux demandes de contact, d’information ou d’accompagnement ;</li>
    <li>instruire les demandes de cartes, de données ou d’accès à des services ;</li>
    <li>administrer les comptes et sécuriser l’accès aux espaces authentifiés ;</li>
    <li>assurer le bon fonctionnement, la maintenance et la sécurité du portail ;</li>
    <li>mesurer l’usage du site dans le respect de vos choix de consentement.</li>
</ul>
<h2>Base légale</h2>
<p>Les traitements reposent, selon les cas, sur l’exécution d’une mission d’intérêt public, sur l’intérêt légitime lié à la sécurisation et à l’amélioration du service, sur l’exécution de mesures précontractuelles ou sur votre consentement lorsque celui-ci est requis, notamment pour certains traceurs optionnels.</p>
<h2>Destinataires des données</h2>
<p>Les données sont destinées aux agents et services habilités de Routes de Guadeloupe ainsi qu’aux prestataires techniques intervenant pour l’hébergement, la maintenance, la sécurité ou le support, dans la stricte limite de leurs attributions.</p>
<h2>Durée de conservation</h2>
<p>Les données sont conservées pendant une durée proportionnée à la finalité du traitement, aux obligations légales applicables et aux besoins de suivi administratif ou technique. Les durées exactes peuvent varier selon la nature de la demande, le type de compte ou les contraintes réglementaires applicables.</p>
<h2>Vos droits</h2>
<p>Conformément à la réglementation en vigueur, vous pouvez demander l’accès à vos données, leur rectification, leur effacement, la limitation de certains traitements, ou vous opposer à un traitement lorsque la loi le permet. Lorsque le traitement repose sur le consentement, vous pouvez le retirer à tout moment.</p>
<p>Pour exercer vos droits, vous pouvez utiliser le formulaire de contact du portail en précisant l’objet de votre demande et tout élément utile à son traitement.</p>
<h2>Sécurité</h2>
<p>Routes de Guadeloupe met en œuvre des mesures organisationnelles et techniques adaptées afin de préserver la confidentialité, l’intégrité et la disponibilité des données traitées.</p>
<h2>Mise à jour</h2>
<p>La présente politique peut évoluer afin de tenir compte des changements techniques, réglementaires ou fonctionnels du portail. La version publiée en ligne est celle opposable à la date de consultation.</p>
HTML,
            $admin,
            $publishedAt,
            null,
        );

        $this->upsertSystemPage(
            'politique-cookies',
            'Politique de cookies',
            'cookies',
            <<<'HTML'
<p>Le portail SIG de Routes de Guadeloupe utilise des cookies et autres traceurs limités à ce qui est nécessaire au fonctionnement du service et, selon vos choix, à la mesure d’audience ou à l’activation de contenus et services tiers.</p>
<h2>Qu’est-ce qu’un cookie ?</h2>
<p>Un cookie est un petit fichier déposé sur votre terminal lors de la consultation d’un site. Il permet notamment de mémoriser des informations techniques, des préférences utilisateur ou des données liées à la navigation.</p>
<h2>Catégories de cookies utilisées</h2>
<h3>Cookies strictement nécessaires</h3>
<p>Ces cookies sont indispensables au fonctionnement du portail. Ils permettent par exemple la gestion de session, la sécurité, l’authentification et la conservation technique de vos préférences essentielles. Ils ne peuvent pas être désactivés via le gestionnaire de consentement.</p>
<h3>Cookies de mesure d’audience</h3>
<p>Ces cookies permettent d’établir des statistiques de fréquentation et d’usage du portail afin d’améliorer les contenus, les parcours et les performances du service. Ils ne sont déposés qu’après votre accord lorsque cette fonctionnalité est activée.</p>
<h3>Cookies liés à des services tiers intégrés</h3>
<p>Certaines fonctionnalités, contenus externes ou services intégrés peuvent déposer leurs propres traceurs. Ils ne sont activés qu’après votre consentement explicite lorsque ces services sont effectivement utilisés.</p>
<h2>Gestion du consentement</h2>
<p>Lors de votre première visite, un bandeau vous permet d’accepter, de refuser ou de personnaliser les cookies optionnels. Vous pouvez ensuite modifier vos choix à tout moment depuis le lien « Gérer les cookies » présent en pied de page.</p>
<h2>Durée de conservation</h2>
<p>Le choix de consentement est conservé pour une durée limitée afin d’éviter de vous solliciter à chaque visite. Au terme de cette durée, une nouvelle demande de consentement peut être affichée.</p>
<h2>Comment s’opposer aux cookies ?</h2>
<p>Vous pouvez refuser les cookies optionnels depuis le gestionnaire de consentement du portail. Vous pouvez également configurer votre navigateur pour bloquer tout ou partie des cookies, sous réserve que cela n’altère pas certaines fonctionnalités du site.</p>
<h2>Évolution de la politique</h2>
<p>La présente politique peut être mise à jour pour tenir compte d’évolutions techniques, réglementaires ou fonctionnelles du portail.</p>
HTML,
            $admin,
            $publishedAt,
            null,
        );

        $this->upsertSystemPage(
            'declaration-accessibilite',
            'Déclaration d’accessibilité',
            'accessibility',
            <<<'HTML'
<p>Routes de Guadeloupe s’engage à rendre le portail SIG accessible, conformément aux bonnes pratiques d’accessibilité numérique et dans une logique d’amélioration continue des services publics en ligne.</p>
<h2>État de conformité</h2>
<p>Le portail fait l’objet d’une démarche progressive de mise en conformité. À ce stade, l’accessibilité est prise en compte dans la conception, l’intégration, la navigation clavier, la structuration des contenus et l’amélioration des contrastes, mais certains contenus ou composants peuvent encore présenter des écarts.</p>
<h2>Actions engagées</h2>
<ul>
    <li>structuration sémantique des pages et hiérarchisation des titres ;</li>
    <li>prise en charge de la navigation clavier sur les parcours principaux ;</li>
    <li>présence de libellés, d’intitulés et de textes d’aide sur les composants essentiels ;</li>
    <li>amélioration progressive des contrastes, états de focus et messages d’interface ;</li>
    <li>intégration de contrôles correctifs lors des évolutions du portail.</li>
</ul>
<h2>Limites connues</h2>
<p>Des non-conformités peuvent subsister sur certains contenus riches, composants cartographiques, documents embarqués, contenus tiers ou éléments éditoriaux issus d’anciennes publications.</p>
<h2>Retour d’information</h2>
<p>Si vous rencontrez une difficulté d’accès à un contenu ou à une fonctionnalité, vous pouvez le signaler via le formulaire de contact du portail en décrivant le problème rencontré, la page concernée et, si possible, votre contexte d’utilisation.</p>
<h2>Voies de recours</h2>
<p>Si vous constatez un défaut d’accessibilité vous empêchant d’accéder à un contenu ou à une fonctionnalité et qu’aucune réponse satisfaisante ne vous est apportée après signalement, vous pouvez engager les démarches de recours prévues par la réglementation applicable.</p>
<h2>Amélioration continue</h2>
<p>Routes de Guadeloupe poursuit l’amélioration de ce portail au fil des mises à jour fonctionnelles, éditoriales et techniques afin de renforcer durablement son niveau d’accessibilité.</p>
HTML,
            $admin,
            $publishedAt,
            null,
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

    private function upsertSystemPage(string $slug, string $title, string $legalType, string $content, ?User $admin, \DateTimeImmutable $publishedAt, ?string $summary): void
    {
        $repository = $this->entityManager->getRepository(Page::class);
        $page = $repository->findOneBy(['slug' => $slug]) ?? new Page();

        $page
            ->setSlug($slug)
            ->setTitle($title)
            ->setSummary($summary)
            ->setContent($content)
            ->setLegalType($legalType)
            ->setSystemPage(true)
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
