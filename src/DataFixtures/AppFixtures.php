<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Domain\Access\Entity\ExternalResourceRequest;
use App\Domain\Access\Entity\Role;
use App\Domain\Access\Entity\User;
use App\Domain\Access\Entity\UserFavorite;
use App\Domain\Access\VisibilityScope;
use App\Domain\Agent\Entity\AgentRequestType;
use App\Domain\Analytics\Entity\DashboardMetricSnapshot;
use App\Domain\Cartography\Entity\DataSource;
use App\Domain\Cartography\Entity\DataCategory;
use App\Domain\Cartography\Entity\DatasetResource;
use App\Domain\Cartography\Entity\InteractiveMap;
use App\Domain\Cartography\Entity\MapLayer;
use App\Domain\Cartography\Entity\MapServiceEndpoint;
use App\Domain\Cartography\Entity\MetadataRecord;
use App\Domain\Cartography\Entity\StaticMap;
use App\Domain\Cartography\Entity\StaticMapAsset;
use App\Domain\Content\Entity\HomepageContent;
use App\Domain\Content\Entity\HomepageSection;
use App\Domain\Content\Entity\News;
use App\Domain\Content\Entity\Page;
use App\Domain\Content\Entity\Partner;
use App\Domain\Content\Entity\QuickLink;
use App\Domain\Taxonomy\MapThemeCatalog;
use App\Domain\Taxonomy\Entity\TaxonomyTerm;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AppFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $roleAdmin = (new Role('Administrateur', 'ROLE_ADMIN'))->setDescription('Accès complet au back-office');
        $roleManager = (new Role('Gestionnaire', 'ROLE_MANAGER'))->setDescription('Gestion des demandes cartographiques internes');
        $roleAgent = (new Role('Agent', 'ROLE_AGENT'))->setDescription('Accès aux demandes internes');
        $roleExternal = (new Role('Externe enregistré', 'ROLE_EXTERNAL'))->setDescription('Accès à l’espace privé externe');
        $roleGod = (new Role('God mode', 'ROLE_GOD'))->setDescription('Accès super-administrateur avec simulation de profils');

        $admin = (new User())
            ->setEmail('admin@routesguadeloupe.local')
            ->setDisplayName('Admin SIG')
            ->setUserType(User::TYPE_ADMIN_EXTERNAL)
            ->setAuthProvider(User::AUTH_PROVIDER_LOCAL)
            ->setIsActive(true)
            ->addRole($roleAdmin)
            ->addRole($roleManager)
            ->addRole($roleAgent);
        $adminPassword = $_ENV['APP_FIXTURE_ADMIN_PASSWORD'] ?? 'Admin12345!';
        $admin->setPassword($this->passwordHasher->hashPassword($admin, $adminPassword));

        $agent = (new User())
            ->setEmail('agent@routesguadeloupe.local')
            ->setDisplayName('Agent Carto')
            ->setUserType(User::TYPE_AGENT_SSO)
            ->setAuthProvider(User::AUTH_PROVIDER_LOCAL)
            ->setIsActive(true)
            ->addRole($roleAgent);
        $agentPassword = $_ENV['APP_FIXTURE_AGENT_PASSWORD'] ?? 'Agent12345!';
        $agent->setPassword($this->passwordHasher->hashPassword($agent, $agentPassword));

        $godEmail = $_ENV['APP_GOD_MODE_EMAIL'] ?? 'florestan.rouet@oling.fr';
        $god = (new User())
            ->setEmail($godEmail)
            ->setDisplayName('Flo')
            ->setFirstName('Flo')
            ->setLastName('God')
            ->setUserType(User::TYPE_ADMIN_EXTERNAL)
            ->setAuthProvider(User::AUTH_PROVIDER_LOCAL)
            ->setIsActive(true)
            ->addRole($roleGod);
        $godPassword = $_ENV['APP_FIXTURE_GOD_PASSWORD'] ?? 'Flo';
        $god->setPassword($this->passwordHasher->hashPassword($god, $godPassword));

        $external = (new User())
            ->setEmail('partenaire@routesguadeloupe.local')
            ->setDisplayName('Marie Partenaire')
            ->setFirstName('Marie')
            ->setLastName('Partenaire')
            ->setOrganizationName('Guadeloupe Data Partners')
            ->setWebsiteUrl('https://example.local')
            ->setUserType(User::TYPE_EXTERNAL)
            ->setAuthProvider(User::AUTH_PROVIDER_LOCAL)
            ->setIsActive(true)
            ->addRole($roleExternal);
        $externalPassword = $_ENV['APP_FIXTURE_EXTERNAL_PASSWORD'] ?? 'Partner12345!';
        $external->setPassword($this->passwordHasher->hashPassword($external, $externalPassword));

        $manager->persist($roleAdmin);
        $manager->persist($roleManager);
        $manager->persist($roleAgent);
        $manager->persist($roleExternal);
        $manager->persist($roleGod);
        $manager->persist($admin);
        $manager->persist($agent);
        $manager->persist($external);
        $manager->persist($god);

        $presentation = (new Page())
            ->setSlug('presentation-portail')
            ->setTitle('Nous sommes Routes de Guadeloupe')
            ->setSummary('Présentation de Routes de Guadeloupe, du service SIGR, de la démarche Open Data et des partenaires.')
            ->setContent(<<<'HTML'
<p>Nous sommes Routes de Guadeloupe, un acteur engagé au service du territoire et de ses habitants. Chaque jour, nous œuvrons à la gestion, à l’aménagement et à l’entretien du réseau routier afin de garantir des déplacements sûrs, fluides et durables pour tous.</p>
<h2>Une expertise au service du territoire</h2>
<p>Nos équipes, composées de professionnels aux compétences complémentaires, interviennent à toutes les étapes de la vie des infrastructures :</p>
<ul>
    <li>Études et prospective, pour anticiper les besoins de demain</li>
    <li>Conception et aménagement, pour développer des projets adaptés et durables</li>
    <li>Entretien et exploitation, pour garantir sécurité et qualité au quotidien</li>
</ul>
<p>Pour renforcer cette expertise, nous nous appuyons sur un Système d’Information Géographique (SIG) routier performant. Véritable outil d’aide à la décision, le SIG des Routes de Guadeloupe permet de mieux connaître le patrimoine routier, de suivre son évolution, d’analyser les usages et d’optimiser nos interventions. Grâce à la cartographie et à la donnée, nous adaptons plus efficacement nos actions aux réalités du terrain.</p>
<p><a href="https://www.routesdeguadeloupe.fr/" rel="noopener" target="_blank">Consulter le site institutionnel Routes de Guadeloupe</a></p>
<h2>L’open data : partager la donnée pour mieux agir</h2>
<p>L’open data, ou données ouvertes, consiste à mettre à disposition du public des données produites ou collectées par les acteurs publics, de manière libre et accessible.</p>
<p>Ces données, structurées et régulièrement mises à jour, peuvent être consultées, réutilisées et partagées par tous : citoyens, entreprises, collectivités.</p>
<p>Pour Routes de Guadeloupe, ces données représentent un levier stratégique, qu’il s’agisse du réseau, des équipements, des travaux ou des conditions de circulation. Cette ouverture nous permet d’améliorer la connaissance du territoire par les thématiques suivantes :</p>
<ul>
    <li>Surveillance de réseau</li>
    <li>Anomalies et dégâts au domaine public routier</li>
    <li>Fauchage</li>
    <li>Patrimoine (ouvrage d’art, signalisation, équipements Trafikéra)</li>
    <li>Gestion de crise</li>
</ul>
<h2>Nous sommes à votre écoute</h2>
<p>Parce que la route appartient à tous, nous attachons une importance particulière au dialogue avec les usagers et les acteurs locaux. Votre expérience et vos attentes, enrichies par les données et les analyses issues de notre SIG, nous permettent de construire ensemble des solutions adaptées et durables.</p>
<p><em>Anwout Ansanm !</em></p>
HTML)
            ->setStatus('published')
            ->setPublishedAt(new \DateTimeImmutable('-2 days'))
            ->setCreatedBy($admin)
            ->setUpdatedBy($admin);

        $legal = (new Page())
            ->setSlug('mentions-legales')
            ->setTitle('Mentions légales')
            ->setLegalType('legal_mentions')
            ->setSystemPage(true)
            ->setContent(<<<'HTML'
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
HTML)
            ->setStatus('published')
            ->setPublishedAt(new \DateTimeImmutable('-2 days'))
            ->setCreatedBy($admin)
            ->setUpdatedBy($admin);

        $privacy = (new Page())
            ->setSlug('politique-confidentialite')
            ->setTitle('Politique de confidentialité')
            ->setLegalType('privacy')
            ->setSystemPage(true)
            ->setContent(<<<'HTML'
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
HTML)
            ->setStatus('published')
            ->setPublishedAt(new \DateTimeImmutable('-2 days'))
            ->setCreatedBy($admin)
            ->setUpdatedBy($admin);

        $cookies = (new Page())
            ->setSlug('politique-cookies')
            ->setTitle('Politique de cookies')
            ->setLegalType('cookies')
            ->setSystemPage(true)
            ->setContent(<<<'HTML'
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
HTML)
            ->setStatus('published')
            ->setPublishedAt(new \DateTimeImmutable('-2 days'))
            ->setCreatedBy($admin)
            ->setUpdatedBy($admin);

        $accessibility = (new Page())
            ->setSlug('declaration-accessibilite')
            ->setTitle('Déclaration d’accessibilité')
            ->setLegalType('accessibility')
            ->setSystemPage(true)
            ->setContent(<<<'HTML'
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
HTML)
            ->setStatus('published')
            ->setPublishedAt(new \DateTimeImmutable('-2 days'))
            ->setCreatedBy($admin)
            ->setUpdatedBy($admin);

        $news1 = (new News())
            ->setSlug('ouverture-portail-sig')
            ->setTitle('Ouverture du portail SIG Routes de Guadeloupe')
            ->setSummary('Mise en ligne de la première version du portail public et de l’espace agents.')
            ->setBody('Le projet Lot 3 est lancé avec un socle Symfony, une cartothèque et des premiers services de suivi des demandes.')
            ->setCoverImagePath('/images/rdg-siege.jpg')
            ->setStatus('published')
            ->setPublishedAt(new \DateTimeImmutable('-1 day'))
            ->setCreatedBy($admin)
            ->setUpdatedBy($admin);

        $news2 = (new News())
            ->setSlug('nouveaux-jeux-donnees-routiers')
            ->setTitle('Nouveaux jeux de données routiers disponibles')
            ->setSummary('Mise à disposition de couches thématiques et de cartes de référence.')
            ->setBody('Plusieurs ressources téléchargeables sont désormais disponibles dans la cartothèque statique.')
            ->setCoverImagePath('/images/rdg-travaux-route.jpg')
            ->setStatus('published')
            ->setPublishedAt(new \DateTimeImmutable('-8 hours'))
            ->setCreatedBy($admin)
            ->setUpdatedBy($admin);

        $quickLink1 = (new QuickLink())
            ->setLabel('Cartothèque')
            ->setUrl('/cartotheque')
            ->setPosition(1)
            ->setExternal(false)
            ->setStatus('published')
            ->setPublishedAt(new \DateTimeImmutable())
            ->setCreatedBy($admin)
            ->setUpdatedBy($admin);

        $quickLink2 = (new QuickLink())
            ->setLabel('Cartes interactives')
            ->setUrl('/cartes-interactives')
            ->setPosition(2)
            ->setExternal(false)
            ->setStatus('published')
            ->setPublishedAt(new \DateTimeImmutable())
            ->setCreatedBy($admin)
            ->setUpdatedBy($admin);

        $quickLink3 = (new QuickLink())
            ->setLabel('Espace agents')
            ->setUrl('/agents')
            ->setPosition(3)
            ->setExternal(false)
            ->setStatus('published')
            ->setPublishedAt(new \DateTimeImmutable())
            ->setCreatedBy($admin)
            ->setUpdatedBy($admin);

        $homepage = (new HomepageContent())
            ->setName('Accueil principal')
            ->setHeroTitle('La plateforme Open Data et SIG de Routes de Guadeloupe')
            ->setHeroBaseline('Plateforme de référence pour la cartographie routière de la Guadeloupe. Cartothèque statique, cartes interactives, information usagers et services agents.')
            ->setSearchIntro('Explorer les jeux de données, cartes et ressources SIG du portail')
            ->setSearchPlaceholder('Rechercher une carte, un jeu de données ou une ressource SIG')
            ->setPrimaryCtaLabel('Explorer le catalogue')
            ->setPrimaryCtaUrl('/donnees-cartes')
            ->setStatus('published')
            ->setPublishedAt(new \DateTimeImmutable('-2 days'))
            ->setCreatedBy($admin)
            ->setUpdatedBy($admin);

        $homeAccess = (new HomepageSection())
            ->setName('Accès principaux')
            ->setType(HomepageSection::TYPE_MANUAL_CARDS)
            ->setPosition(10)
            ->setTitle('Accès principaux du portail')
            ->setLayout(HomepageSection::LAYOUT_GRID)
            ->setBackgroundStyle('kpi')
            ->setItemsConfig(json_encode([
                ['title' => 'Cartothèque statique', 'url' => '/donnees-cartes?type%5B0%5D=static', 'label' => 'Accéder à la cartothèque', 'accent' => 'orange', 'icon' => 'map'],
                ['title' => 'Cartes interactives', 'url' => '/donnees-cartes?type%5B0%5D=interactive', 'label' => 'Voir les cartes', 'accent' => 'blue', 'icon' => 'layers'],
                ['title' => 'Information publique', 'url' => '/actualites', 'label' => 'Lire les actualités', 'accent' => 'yellow', 'icon' => 'megaphone'],
                ['title' => 'Espace agents', 'url' => '/connexion', 'label' => 'Accéder à l’espace', 'accent' => 'green', 'icon' => 'shield'],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))
            ->setStatus('published')
            ->setPublishedAt(new \DateTimeImmutable('-2 days'))
            ->setCreatedBy($admin)
            ->setUpdatedBy($admin);

        $homeFeaturedVisuals = (new HomepageSection())
            ->setName('Visualisations à la une')
            ->setType(HomepageSection::TYPE_MANUAL_CARDS)
            ->setPosition(20)
            ->setTitle('Visualisations à la une')
            ->setIntro('Des entrées directes vers les usages cartographiques les plus consultés du portail.')
            ->setCtaLabel('Voir le catalogue des visualisations')
            ->setCtaUrl('/donnees-cartes?type%5B0%5D=interactive')
            ->setBackgroundStyle('institutional')
            ->setItemsConfig(json_encode([
                ['title' => 'Réseau routier principal', 'text' => 'Lecture synthétique des axes structurants et des continuités de circulation à l’échelle du territoire.', 'imagePath' => '/images/hero-guadeloupe-map-v5.png', 'url' => '/donnees-cartes?type%5B0%5D=interactive&q=réseau routier', 'label' => 'Consulter la visualisation'],
                ['title' => 'Travaux et incidents', 'text' => 'Identification des zones en travaux, événements de circulation et points de vigilance opérationnels.', 'imagePath' => '/images/hero-guadeloupe-map-v4.png', 'url' => '/donnees-cartes?type%5B0%5D=interactive&q=travaux', 'label' => 'Accéder à la carte'],
                ['title' => 'Services SIG disponibles', 'text' => 'Vérification de disponibilité des couches interactives et accès direct aux ressources cartographiques.', 'imagePath' => '/images/hero-guadeloupe-map-v6.png', 'url' => '/donnees-cartes?type%5B0%5D=interactive', 'label' => 'Voir les cartes interactives'],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))
            ->setStatus('published')
            ->setPublishedAt(new \DateTimeImmutable('-2 days'))
            ->setCreatedBy($admin)
            ->setUpdatedBy($admin);

        $homeResources = (new HomepageSection())
            ->setName('Ressources de référence')
            ->setType(HomepageSection::TYPE_FEATURED_RESOURCES)
            ->setPosition(30)
            ->setTitle('Les données de référence')
            ->setIntro('Cartes officielles, ressources de diffusion et jeux de données SIG publiés.')
            ->setCtaLabel('Explorer le catalogue')
            ->setCtaUrl('/donnees-cartes')
            ->setItemLimit(3)
            ->setStatus('published')
            ->setPublishedAt(new \DateTimeImmutable('-2 days'))
            ->setCreatedBy($admin)
            ->setUpdatedBy($admin);

        $homeUsingData = (new HomepageSection())
            ->setName('Utiliser les données')
            ->setType(HomepageSection::TYPE_MANUAL_CARDS)
            ->setPosition(40)
            ->setTitle('Utiliser les données')
            ->setIntro('Trois parcours simples pour trouver, consulter et réutiliser les ressources SIG.')
            ->setBackgroundStyle('muted')
            ->setItemsConfig(json_encode([
                ['title' => 'Rechercher et filtrer', 'text' => 'Utilisez les mots-clés, thèmes et formats pour trouver rapidement la bonne carte ou la bonne donnée.', 'imagePath' => '/images/hero-guadeloupe-map-v6.png', 'url' => '/donnees-cartes', 'label' => 'Lancer une recherche'],
                ['title' => 'Télécharger les ressources', 'text' => 'Accédez aux supports publiés et aux fichiers associés depuis les fiches de la cartothèque.', 'imagePath' => '/images/hero-guadeloupe-map-v5.png', 'url' => '/donnees-cartes?type%5B0%5D=static', 'label' => 'Télécharger des cartes'],
                ['title' => 'Consulter les services agents', 'text' => 'Les agents disposent d’un espace dédié pour suivre les demandes et les services opérationnels.', 'imagePath' => '/images/hero-guadeloupe-map-v4.png', 'url' => '/connexion', 'label' => 'Accéder à l’espace agents'],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))
            ->setStatus('published')
            ->setPublishedAt(new \DateTimeImmutable('-2 days'))
            ->setCreatedBy($admin)
            ->setUpdatedBy($admin);

        $homeInstitutionalMessage = (new HomepageSection())
            ->setName('Message institutionnel')
            ->setType(HomepageSection::TYPE_MESSAGE)
            ->setPosition(50)
            ->setTitle('Une donnée routière plus lisible, plus accessible et plus réutilisable')
            ->setIntro('Le portail accompagne la diffusion progressive des données publiques routières de Guadeloupe.')
            ->setBody('<p>Routes de Guadeloupe met à disposition un point d’accès commun pour consulter les cartes, suivre les ressources publiées et faciliter la réutilisation des données SIG.</p><p>La page d’accueil peut désormais évoluer selon les priorités éditoriales : alerte, campagne de publication, focus thématique, mise en avant partenaire ou sélection de contenus.</p>')
            ->setImagePath('/images/hero-guadeloupe-map-v3.png')
            ->setCtaLabel('Découvrir la démarche')
            ->setCtaUrl('/pages/presentation-portail')
            ->setLayout(HomepageSection::LAYOUT_FEATURE)
            ->setStatus('published')
            ->setPublishedAt(new \DateTimeImmutable('-2 days'))
            ->setCreatedBy($admin)
            ->setUpdatedBy($admin);

        $homeSponsors = (new HomepageSection())
            ->setName('Partenaires et financeurs')
            ->setType(HomepageSection::TYPE_SPONSOR)
            ->setPosition(60)
            ->setTitle('Partenaires et financeurs')
            ->setIntro('Une rubrique pour valoriser les institutions et programmes associés au portail.')
            ->setBody('<p>Cette section peut accueillir un sponsor, un financeur, un logo partenaire ou un message de programme. Elle est pensée pour rester éditable sans intervention technique.</p>')
            ->setImagePath('/images/logo-rdg.jpg')
            ->setCtaLabel('Consulter la rubrique partenaires')
            ->setCtaUrl('/partenaires-financeurs')
            ->setLayout(HomepageSection::LAYOUT_BANNER)
            ->setBackgroundStyle('muted')
            ->setStatus('published')
            ->setPublishedAt(new \DateTimeImmutable('-2 days'))
            ->setCreatedBy($admin)
            ->setUpdatedBy($admin);

        $homeNews = (new HomepageSection())
            ->setName('Actualités')
            ->setType(HomepageSection::TYPE_LATEST_NEWS)
            ->setPosition(70)
            ->setTitle('Actualités et communiqués')
            ->setIntro('Les dernières informations publiques liées au portail, aux données et aux services SIG.')
            ->setCtaLabel('Voir toutes les actualités')
            ->setCtaUrl('/actualites')
            ->setItemLimit(3)
            ->setStatus('published')
            ->setPublishedAt(new \DateTimeImmutable('-2 days'))
            ->setCreatedBy($admin)
            ->setUpdatedBy($admin);

        $partner = (new Partner())
            ->setSlug('collectivite-region-guadeloupe')
            ->setName('Collectivité Région Guadeloupe')
            ->setKind('funder')
            ->setDescription('Financeur institutionnel du programme de modernisation SIG.')
            ->setStatus('published')
            ->setPublishedAt(new \DateTimeImmutable('-3 days'))
            ->setCreatedBy($admin)
            ->setUpdatedBy($admin);

        $mapThemes = [];
        foreach (MapThemeCatalog::definitions() as $themeDefinition) {
            $mapTheme = (new TaxonomyTerm())
                ->setTaxonomy(TaxonomyTerm::MAP_THEME_TAXONOMY)
                ->setSlug($themeDefinition['slug'])
                ->setLabel($themeDefinition['label'])
                ->setDescription($themeDefinition['description'])
                ->setIconKey($themeDefinition['icon'])
                ->setColorHex($themeDefinition['color'])
                ->setPosition($themeDefinition['position'])
                ->setFeaturedOnHomepage(true)
                ->setActive(true);

            $mapThemes[] = $mapTheme;
        }

        $metadata = (new MetadataRecord())
            ->setIdentifier('MD-RDG-001')
            ->setTitle('Métadonnée - Inventaire réseau routier principal')
            ->setAbstractText('Métadonnées normalisées ISO pour un jeu d’informations routières.')
            ->setKeywords(['route', 'infrastructure', 'guadeloupe'])
            ->setLastSyncedAt(new \DateTimeImmutable('-1 hour'));

        $staticMap = (new StaticMap())
            ->setSlug('reseau-routier-principal')
            ->setTitle('Réseau routier principal')
            ->setSummary('Carte statique du réseau routier principal.')
            ->setDescription('Version PDF et PNG du réseau routier principal. Jeux de données associés disponibles.')
            ->setTheme(MapThemeCatalog::labelForSlug('chaussees-et-accotements'))
            ->setVisibilityScope(VisibilityScope::PUBLIC)
            ->setStatus('published')
            ->setPublishedAt(new \DateTimeImmutable('-1 day'))
            ->setDocumentDate(new \DateTimeImmutable('2025-12-01'))
            ->setMetadataRecord($metadata)
            ->setCreatedBy($admin)
            ->setUpdatedBy($admin);

        $assetPdf = (new StaticMapAsset())
            ->setLabel('Version PDF A3')
            ->setAssetType('pdf')
            ->setFilePath('/files/maps/reseau-routier-principal-a3.pdf')
            ->setMimeType('application/pdf')
            ->setFileSize(1823210);

        $assetPng = (new StaticMapAsset())
            ->setLabel('Version PNG HD')
            ->setAssetType('png')
            ->setFilePath('/files/maps/reseau-routier-principal.png')
            ->setMimeType('image/png')
            ->setFileSize(732100);

        $dataset = (new DatasetResource())
            ->setLabel('Jeu de données réseau routier')
            ->setFormat('geojson')
            ->setExternal(true)
            ->setUrl('https://example.local/datasets/reseau-routier.geojson')
            ->setLicense('Open License')
            ->setVisibilityScope(VisibilityScope::EXTERNAL);

        $staticMap->addAsset($assetPdf)->addAsset($assetPng)->addDatasetResource($dataset);

        $endpointUp = (new MapServiceEndpoint())
            ->setName('Mock WMS principal')
            ->setServiceType('wms')
            ->setBaseUrl('https://mock-wms-up.local/service')
            ->setEnabled(true)
            ->setHealthStatus('unknown')
            ->setTimeoutMs(3000);

        $endpointDown = (new MapServiceEndpoint())
            ->setName('Mock WFS secondaire')
            ->setServiceType('wfs')
            ->setBaseUrl('https://mock-down.local/service')
            ->setEnabled(true)
            ->setHealthStatus('unknown')
            ->setTimeoutMs(3000);

        $interactiveMap = (new InteractiveMap())
            ->setSlug('visualisation-reseau')
            ->setTitle('Visualisation réseau routier')
            ->setSummary('Carte interactive de démonstration avec couches superposables.')
            ->setVisibilityScope(VisibilityScope::EXTERNAL)
            ->setStatus('published')
            ->setPublishedAt(new \DateTimeImmutable('-1 day'))
            ->setDegradedModeMessage('Certaines couches distantes sont momentanément indisponibles.')
            ->setCreatedBy($admin)
            ->setUpdatedBy($admin);

        $layer = (new MapLayer())
            ->setInteractiveMap($interactiveMap)
            ->setEndpoint($endpointUp)
            ->setName('reseau_principal')
            ->setLabel('Réseau principal')
            ->setServiceLayerName('rdg:reseau_principal')
            ->setLayerType('wms')
            ->setRenderOrder(1)
            ->setVisibleByDefault(true);

        $layer2 = (new MapLayer())
            ->setInteractiveMap($interactiveMap)
            ->setEndpoint($endpointDown)
            ->setName('travaux_planifies')
            ->setLabel('Travaux planifiés')
            ->setServiceLayerName('rdg:travaux')
            ->setLayerType('wfs')
            ->setRenderOrder(2)
            ->setVisibleByDefault(true);

        $interactiveMap->addLayer($layer)->addLayer($layer2);

        $sourceInteractive = (new DataSource())
            ->setSlug('source-visualisation-reseau')
            ->setTitle('Visualisation réseau routier')
            ->setSourceType(DataSource::TYPE_CARTOGRAPHY_LINK)
            ->setSummary('Accès direct à la cartographie interactive du réseau routier.')
            ->setTheme(MapThemeCatalog::labelForSlug('circulation-routiere'))
            ->setVisibilityScope(VisibilityScope::EXTERNAL)
            ->setFormat('websig')
            ->setLinkedInteractiveMap($interactiveMap)
            ->setStatus('published')
            ->setPublishedAt(new \DateTimeImmutable('-1 day'))
            ->setCreatedBy($admin)
            ->setUpdatedBy($admin);

        $sourceWms = (new DataSource())
            ->setSlug('service-wms-reseau-principal')
            ->setTitle('Service WMS - Réseau principal')
            ->setSourceType(DataSource::TYPE_WMS)
            ->setSummary('Service cartographique WMS de consultation du réseau principal.')
            ->setTheme(MapThemeCatalog::labelForSlug('chaussees-et-accotements'))
            ->setVisibilityScope(VisibilityScope::INTERNAL)
            ->setFormat('wms')
            ->setSourceUrl('https://mock-wms-up.local/service')
            ->setServiceEndpoint($endpointUp)
            ->setStatus('published')
            ->setPublishedAt(new \DateTimeImmutable('-1 day'))
            ->setCreatedBy($admin)
            ->setUpdatedBy($admin);

        $sourceWfs = (new DataSource())
            ->setSlug('service-wfs-travaux-planifies')
            ->setTitle('Service WFS - Travaux planifiés')
            ->setSourceType(DataSource::TYPE_WFS)
            ->setSummary('Service WFS exposant les travaux planifiés et événements opérationnels.')
            ->setTheme(MapThemeCatalog::labelForSlug('circulation-routiere'))
            ->setVisibilityScope(VisibilityScope::INTERNAL)
            ->setFormat('wfs')
            ->setSourceUrl('https://mock-down.local/service')
            ->setServiceEndpoint($endpointDown)
            ->setStatus('published')
            ->setPublishedAt(new \DateTimeImmutable('-2 days'))
            ->setCreatedBy($admin)
            ->setUpdatedBy($admin);

        $sourceFile = (new DataSource())
            ->setSlug('fichier-reseau-routier-geojson')
            ->setTitle('Fichier réseau routier GeoJSON')
            ->setSourceType(DataSource::TYPE_DATA_FILE)
            ->setSummary('Fichier de données réutilisable du réseau routier principal.')
            ->setTheme(MapThemeCatalog::labelForSlug('chaussees-et-accotements'))
            ->setVisibilityScope(VisibilityScope::PUBLIC)
            ->setFormat('geojson')
            ->setSourceUrl('https://example.local/datasets/reseau-routier.geojson')
            ->setLicense('Open License')
            ->setStatus('published')
            ->setPublishedAt(new \DateTimeImmutable('-2 days'))
            ->setCreatedBy($admin)
            ->setUpdatedBy($admin);

        $sourceStaticMap = (new DataSource())
            ->setSlug('source-carte-reseau-routier-principal')
            ->setTitle('Carte statique réseau routier principal')
            ->setSourceType(DataSource::TYPE_STATIC_MAP)
            ->setSummary('Fiche source associée à la carte statique du réseau routier principal.')
            ->setTheme(MapThemeCatalog::labelForSlug('chaussees-et-accotements'))
            ->setVisibilityScope(VisibilityScope::PUBLIC)
            ->setFormat('pdf/png')
            ->setLinkedStaticMap($staticMap)
            ->setStatus('published')
            ->setPublishedAt(new \DateTimeImmutable('-1 day'))
            ->setCreatedBy($admin)
            ->setUpdatedBy($admin);

        $categoryData = (new DataCategory())
            ->setName('Données ouvertes')
            ->setSlug('donnees-ouvertes')
            ->setDescription('Jeux de données téléchargeables et réutilisables.')
            ->setIconKey('database')
            ->setColorHex('#3CB4DF')
            ->setFeaturedOnHomepage(true)
            ->setPosition(10)
            ->setStatus('published')
            ->setPublishedAt(new \DateTimeImmutable('-2 days'))
            ->setCreatedBy($admin)
            ->setUpdatedBy($admin);

        $categoryMaps = (new DataCategory())
            ->setName('Cartes statiques')
            ->setSlug('cartes-statiques')
            ->setDescription('Cartothèque de cartes de référence prêtes à consulter.')
            ->setIconKey('map')
            ->setColorHex('#FF4D0A')
            ->setFeaturedOnHomepage(true)
            ->setPosition(20)
            ->setStatus('published')
            ->setPublishedAt(new \DateTimeImmutable('-2 days'))
            ->setCreatedBy($admin)
            ->setUpdatedBy($admin);

        $categoryInteractive = (new DataCategory())
            ->setName('Cartes interactives')
            ->setSlug('cartes-interactives')
            ->setDescription('Visualisations dynamiques avec couches superposables.')
            ->setIconKey('layers')
            ->setColorHex('#2FA7D9')
            ->setFeaturedOnHomepage(true)
            ->setPosition(30)
            ->setStatus('published')
            ->setPublishedAt(new \DateTimeImmutable('-2 days'))
            ->setCreatedBy($admin)
            ->setUpdatedBy($admin);

        $categoryServices = (new DataCategory())
            ->setName('Services SIG')
            ->setSlug('services-sig')
            ->setDescription('Services WMS/WFS et endpoints de diffusion.')
            ->setIconKey('sig')
            ->setColorHex('#7AA63A')
            ->setFeaturedOnHomepage(true)
            ->setPosition(40)
            ->setStatus('published')
            ->setPublishedAt(new \DateTimeImmutable('-2 days'))
            ->setCreatedBy($admin)
            ->setUpdatedBy($admin);

        $sourceInteractive->addCategory($categoryInteractive);
        $sourceInteractive->addCategory($categoryServices);
        $sourceWms->addCategory($categoryServices);
        $sourceWfs->addCategory($categoryServices);
        $sourceFile->addCategory($categoryData);
        $sourceStaticMap->addCategory($categoryMaps);

        $requestTypeData = (new AgentRequestType())
            ->setCode('DATA_REQUEST')
            ->setLabel('Demande de données')
            ->setDescription('Demande d’extraction de données SIG')
            ->setRequiresAttachment(false)
            ->setActive(true);

        $requestTypeMap = (new AgentRequestType())
            ->setCode('MAP_REQUEST')
            ->setLabel('Demande de carte')
            ->setDescription('Création ou mise à jour de carte')
            ->setRequiresAttachment(true)
            ->setActive(true);

        $requestTypeMixed = (new AgentRequestType())
            ->setCode('MIXED_REQUEST')
            ->setLabel('Demande mixte carte + données')
            ->setDescription('Demande combinant une production cartographique et des données SIG')
            ->setRequiresAttachment(false)
            ->setActive(true);

        $metricVisits = (new DashboardMetricSnapshot())
            ->setMetricKey('portal.visits.daily')
            ->setScope('public')
            ->setValueInteger(128);

        $metricRequests = (new DashboardMetricSnapshot())
            ->setMetricKey('agent.requests.open')
            ->setScope('agents')
            ->setValueInteger(7);

        $favorite = (new UserFavorite())
            ->setUser($external)
            ->setResourceKind(UserFavorite::KIND_DATA_SOURCE)
            ->setResourceSlug($sourceFile->getSlug())
            ->setResourceTitle($sourceFile->getTitle())
            ->setResourceUrl('/donnees-cartes/source/'.$sourceFile->getSlug());

        $externalRequest = (new ExternalResourceRequest())
            ->setRequester($external)
            ->setRequestNumber('RDG-EXT-20260623-0001')
            ->setRequesterType(ExternalResourceRequest::REQUESTER_TYPE_PROFESSIONAL)
            ->setLastName('Partenaire')
            ->setFirstName('Marie')
            ->setEmail('partenaire@routesguadeloupe.local')
            ->setPhoneNumber('0690123456')
            ->setOrganizationName('Guadeloupe Data Partners')
            ->setCompanySiret('12345678901234')
            ->setPostalCode('97100')
            ->setCity('Basse-Terre')
            ->setSubject('Accès à une couche complémentaire')
            ->setMessage('Je souhaite accéder aux données historiques sur les incidents routiers.')
            ->setRequestKind(ExternalResourceRequest::REQUEST_KIND_DATA)
            ->setNetworkTypes(['Route Départementale'])
            ->setDataFormats(['GeoJSON', 'CSV'])
            ->setProjectionSystem('RGAF09')
            ->setPrivacyConsent(true)
            ->setNoticeVersion('v1')
            ->setStatus('submitted');

        foreach ([
            $presentation,
            $legal,
            $privacy,
            $cookies,
            $accessibility,
            $news1,
            $news2,
            $quickLink1,
            $quickLink2,
            $quickLink3,
            $homepage,
            $homeAccess,
            $homeFeaturedVisuals,
            $homeResources,
            $homeUsingData,
            $homeInstitutionalMessage,
            $homeSponsors,
            $homeNews,
            $partner,
            $metadata,
            $staticMap,
            $endpointUp,
            $endpointDown,
            $interactiveMap,
            $sourceInteractive,
            $sourceWms,
            $sourceWfs,
            $sourceFile,
            $sourceStaticMap,
            $categoryData,
            $categoryMaps,
            $categoryInteractive,
            $categoryServices,
            $requestTypeData,
            $requestTypeMap,
            $requestTypeMixed,
            $metricVisits,
            $metricRequests,
            $favorite,
            $externalRequest,
        ] as $entity) {
            $manager->persist($entity);
        }

        foreach ($mapThemes as $mapTheme) {
            $manager->persist($mapTheme);
        }

        $manager->flush();
    }
}
