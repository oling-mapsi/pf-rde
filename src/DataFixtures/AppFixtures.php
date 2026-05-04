<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Domain\Access\Entity\Role;
use App\Domain\Access\Entity\User;
use App\Domain\Agent\Entity\AgentRequestType;
use App\Domain\Analytics\Entity\DashboardMetricSnapshot;
use App\Domain\Cartography\Entity\DatasetResource;
use App\Domain\Cartography\Entity\InteractiveMap;
use App\Domain\Cartography\Entity\MapLayer;
use App\Domain\Cartography\Entity\MapServiceEndpoint;
use App\Domain\Cartography\Entity\MetadataRecord;
use App\Domain\Cartography\Entity\StaticMap;
use App\Domain\Cartography\Entity\StaticMapAsset;
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
        $roleAgent = (new Role('Agent', 'ROLE_AGENT'))->setDescription('Accès aux demandes internes');

        $admin = (new User())
            ->setEmail('admin@routesguadeloupe.local')
            ->setDisplayName('Admin SIG')
            ->setIsActive(true)
            ->addRole($roleAdmin)
            ->addRole($roleAgent);
        $adminPassword = $_ENV['APP_FIXTURE_ADMIN_PASSWORD'] ?? 'Admin12345!';
        $admin->setPassword($this->passwordHasher->hashPassword($admin, $adminPassword));

        $agent = (new User())
            ->setEmail('agent@routesguadeloupe.local')
            ->setDisplayName('Agent Carto')
            ->setIsActive(true)
            ->addRole($roleAgent);
        $agentPassword = $_ENV['APP_FIXTURE_AGENT_PASSWORD'] ?? 'Agent12345!';
        $agent->setPassword($this->passwordHasher->hashPassword($agent, $agentPassword));

        $manager->persist($roleAdmin);
        $manager->persist($roleAgent);
        $manager->persist($admin);
        $manager->persist($agent);

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
            ->setLicense('Open License');

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
            $partner,
            $themeMob,
            $metadata,
            $staticMap,
            $endpointUp,
            $endpointDown,
            $interactiveMap,
            $requestTypeData,
            $requestTypeMap,
            $metricVisits,
            $metricRequests,
        ] as $entity) {
            $manager->persist($entity);
        }

        $manager->flush();
    }
}
