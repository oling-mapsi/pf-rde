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
            ->setTitle('Présentation du portail')
            ->setSummary('Portail public SIG dédié aux infrastructures routières de la Guadeloupe.')
            ->setContent('Ce portail centralise les informations cartographiques publiques et les services agents. Il est conçu pour une évolution progressive et interopérable.')
            ->setStatus('published')
            ->setPublishedAt(new \DateTimeImmutable('-2 days'))
            ->setCreatedBy($admin)
            ->setUpdatedBy($admin);

        $legal = (new Page())
            ->setSlug('mentions-legales')
            ->setTitle('Mentions légales')
            ->setLegalType('legal_mentions')
            ->setSystemPage(true)
            ->setContent('Éditeur, hébergeur, droits et responsabilités. Contenu de démonstration à personnaliser avant mise en production.')
            ->setStatus('published')
            ->setPublishedAt(new \DateTimeImmutable('-2 days'))
            ->setCreatedBy($admin)
            ->setUpdatedBy($admin);

        $privacy = (new Page())
            ->setSlug('politique-confidentialite')
            ->setTitle('Politique de confidentialité')
            ->setLegalType('privacy')
            ->setSystemPage(true)
            ->setContent('Collecte minimale des données, rétention maîtrisée, droits RGPD et modalités d’exercice. Contenu de démonstration.')
            ->setStatus('published')
            ->setPublishedAt(new \DateTimeImmutable('-2 days'))
            ->setCreatedBy($admin)
            ->setUpdatedBy($admin);

        $cookies = (new Page())
            ->setSlug('politique-cookies')
            ->setTitle('Politique de cookies')
            ->setLegalType('cookies')
            ->setSystemPage(true)
            ->setContent('Cette page de démonstration précise les cookies strictement nécessaires, analytiques et de personnalisation ainsi que les modalités de consentement.')
            ->setStatus('published')
            ->setPublishedAt(new \DateTimeImmutable('-2 days'))
            ->setCreatedBy($admin)
            ->setUpdatedBy($admin);

        $accessibility = (new Page())
            ->setSlug('declaration-accessibilite')
            ->setTitle('Déclaration d’accessibilité')
            ->setLegalType('accessibility')
            ->setSystemPage(true)
            ->setContent('Le portail SIG Routes de Guadeloupe applique une démarche d’accessibilité progressive. Les contenus non conformes font l’objet d’un plan d’amélioration.')
            ->setStatus('published')
            ->setPublishedAt(new \DateTimeImmutable('-2 days'))
            ->setCreatedBy($admin)
            ->setUpdatedBy($admin);

        $news1 = (new News())
            ->setSlug('ouverture-portail-sig')
            ->setTitle('Ouverture du portail SIG Routes de Guadeloupe')
            ->setSummary('Mise en ligne de la première version du portail public et de l’espace agents.')
            ->setBody('Le projet Lot 3 est lancé avec un socle Symfony, une cartothèque et des premiers services de suivi des demandes.')
            ->setStatus('published')
            ->setPublishedAt(new \DateTimeImmutable('-1 day'))
            ->setCreatedBy($admin)
            ->setUpdatedBy($admin);

        $news2 = (new News())
            ->setSlug('nouveaux-jeux-donnees-routiers')
            ->setTitle('Nouveaux jeux de données routiers disponibles')
            ->setSummary('Mise à disposition de couches thématiques et de cartes de référence.')
            ->setBody('Plusieurs ressources téléchargeables sont désormais disponibles dans la cartothèque statique.')
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

        $themeMob = (new TaxonomyTerm())
            ->setTaxonomy('map_theme')
            ->setSlug('mobilite')
            ->setLabel('Mobilité')
            ->setDescription('Données de mobilité et infrastructures de transport')
            ->setActive(true);

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
            ->setTheme('Mobilité')
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
            ->setTheme('Mobilité')
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
            ->setTheme('Mobilité')
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
            ->setTheme('Mobilité')
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
            ->setTheme('Mobilité')
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
            ->setTheme('Mobilité')
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
            ->setSubject('Accès à une couche complémentaire')
            ->setMessage('Je souhaite accéder aux données historiques sur les incidents routiers.')
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
            $themeMob,
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
            $metricVisits,
            $metricRequests,
            $favorite,
            $externalRequest,
        ] as $entity) {
            $manager->persist($entity);
        }

        $manager->flush();
    }
}
