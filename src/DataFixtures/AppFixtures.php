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
        $roleAdmin = (new Role('Administrateur', 'ROLE_ADMIN'))->setDescription('Acces complet au back-office');
        $roleAgent = (new Role('Agent', 'ROLE_AGENT'))->setDescription('Acces aux demandes internes');

        $admin = (new User())
            ->setEmail('admin@routesguadeloupe.local')
            ->setDisplayName('Admin SIG')
            ->setIsActive(true)
            ->addRole($roleAdmin)
            ->addRole($roleAgent);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'Admin12345!'));

        $agent = (new User())
            ->setEmail('agent@routesguadeloupe.local')
            ->setDisplayName('Agent Carto')
            ->setIsActive(true)
            ->addRole($roleAgent);
        $agent->setPassword($this->passwordHasher->hashPassword($agent, 'Agent12345!'));

        $manager->persist($roleAdmin);
        $manager->persist($roleAgent);
        $manager->persist($admin);
        $manager->persist($agent);

        $presentation = (new Page())
            ->setSlug('presentation-portail')
            ->setTitle('Presentation du portail')
            ->setSummary('Portail public SIG dedie aux infrastructures routieres de la Guadeloupe.')
            ->setContent('Ce portail centralise les informations cartographiques publiques et les services agents. Il est concu pour une evolution progressive et interoperable.')
            ->setStatus('published')
            ->setPublishedAt(new \DateTimeImmutable('-2 days'))
            ->setCreatedBy($admin)
            ->setUpdatedBy($admin);

        $legal = (new Page())
            ->setSlug('mentions-legales')
            ->setTitle('Mentions legales')
            ->setLegalType('legal_mentions')
            ->setSystemPage(true)
            ->setContent('Editeur, hebergeur, droits et responsabilites. Contenu de demonstration a personnaliser avant mise en production.')
            ->setStatus('published')
            ->setPublishedAt(new \DateTimeImmutable('-2 days'))
            ->setCreatedBy($admin)
            ->setUpdatedBy($admin);

        $privacy = (new Page())
            ->setSlug('politique-confidentialite')
            ->setTitle('Politique de confidentialite')
            ->setLegalType('privacy')
            ->setSystemPage(true)
            ->setContent('Collecte minimale des donnees, retention maitrisee, droits RGPD et modalites d exercice. Contenu de demonstration.')
            ->setStatus('published')
            ->setPublishedAt(new \DateTimeImmutable('-2 days'))
            ->setCreatedBy($admin)
            ->setUpdatedBy($admin);

        $cookies = (new Page())
            ->setSlug('politique-cookies')
            ->setTitle('Politique de cookies')
            ->setLegalType('cookies')
            ->setSystemPage(true)
            ->setContent('Cette page de demonstration precise les cookies strictement necessaires, analytiques et de personnalisation ainsi que les modalites de consentement.')
            ->setStatus('published')
            ->setPublishedAt(new \DateTimeImmutable('-2 days'))
            ->setCreatedBy($admin)
            ->setUpdatedBy($admin);

        $accessibility = (new Page())
            ->setSlug('declaration-accessibilite')
            ->setTitle('Declaration d accessibilite')
            ->setLegalType('accessibility')
            ->setSystemPage(true)
            ->setContent('Le portail SIG Routes de Guadeloupe applique une demarche d accessibilite progressive. Les contenus non conformes font l objet d un plan d amelioration.')
            ->setStatus('published')
            ->setPublishedAt(new \DateTimeImmutable('-2 days'))
            ->setCreatedBy($admin)
            ->setUpdatedBy($admin);

        $news1 = (new News())
            ->setSlug('ouverture-portail-sig')
            ->setTitle('Ouverture du portail SIG Routes de Guadeloupe')
            ->setSummary('Mise en ligne de la premiere version du portail public et de l espace agents.')
            ->setBody('Le projet Lot 3 est lance avec un socle Symfony, une cartotheque et des premiers services de suivi des demandes.')
            ->setStatus('published')
            ->setPublishedAt(new \DateTimeImmutable('-1 day'))
            ->setCreatedBy($admin)
            ->setUpdatedBy($admin);

        $news2 = (new News())
            ->setSlug('nouveaux-jeux-donnees-routiers')
            ->setTitle('Nouveaux jeux de donnees routiers disponibles')
            ->setSummary('Mise a disposition de couches thematiques et de cartes de reference.')
            ->setBody('Plusieurs ressources telechargeables sont desormais disponibles dans la cartotheque statique.')
            ->setStatus('published')
            ->setPublishedAt(new \DateTimeImmutable('-8 hours'))
            ->setCreatedBy($admin)
            ->setUpdatedBy($admin);

        $quickLink1 = (new QuickLink())
            ->setLabel('Cartotheque')
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
            ->setName('Collectivite Region Guadeloupe')
            ->setKind('funder')
            ->setDescription('Financeur institutionnel du programme de modernisation SIG.')
            ->setStatus('published')
            ->setPublishedAt(new \DateTimeImmutable('-3 days'))
            ->setCreatedBy($admin)
            ->setUpdatedBy($admin);

        $themeMob = (new TaxonomyTerm())
            ->setTaxonomy('map_theme')
            ->setSlug('mobilite')
            ->setLabel('Mobilite')
            ->setDescription('Donnees de mobilite et infrastructures de transport')
            ->setActive(true);

        $metadata = (new MetadataRecord())
            ->setIdentifier('MD-RDG-001')
            ->setTitle('Metadonnee - Inventaire reseau routier principal')
            ->setAbstractText('Metadonnees normalisees ISO pour un jeu d informations routieres.')
            ->setKeywords(['route', 'infrastructure', 'guadeloupe'])
            ->setLastSyncedAt(new \DateTimeImmutable('-1 hour'));

        $staticMap = (new StaticMap())
            ->setSlug('reseau-routier-principal')
            ->setTitle('Reseau routier principal')
            ->setSummary('Carte statique du reseau routier principal.')
            ->setDescription('Version PDF et PNG du reseau routier principal. Jeux de donnees associes disponibles.')
            ->setTheme('Mobilite')
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
            ->setLabel('Jeu de donnees reseau routier')
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
            ->setTitle('Visualisation reseau routier')
            ->setSummary('Carte interactive de demonstration avec couches superposables.')
            ->setStatus('published')
            ->setPublishedAt(new \DateTimeImmutable('-1 day'))
            ->setDegradedModeMessage('Certaines couches distantes sont momentanement indisponibles.')
            ->setCreatedBy($admin)
            ->setUpdatedBy($admin);

        $layer = (new MapLayer())
            ->setInteractiveMap($interactiveMap)
            ->setEndpoint($endpointUp)
            ->setName('reseau_principal')
            ->setLabel('Reseau principal')
            ->setServiceLayerName('rdg:reseau_principal')
            ->setLayerType('wms')
            ->setRenderOrder(1)
            ->setVisibleByDefault(true);

        $layer2 = (new MapLayer())
            ->setInteractiveMap($interactiveMap)
            ->setEndpoint($endpointDown)
            ->setName('travaux_planifies')
            ->setLabel('Travaux planifies')
            ->setServiceLayerName('rdg:travaux')
            ->setLayerType('wfs')
            ->setRenderOrder(2)
            ->setVisibleByDefault(true);

        $interactiveMap->addLayer($layer)->addLayer($layer2);

        $requestTypeData = (new AgentRequestType())
            ->setCode('DATA_REQUEST')
            ->setLabel('Demande de donnees')
            ->setDescription('Demande d extraction de donnees SIG')
            ->setRequiresAttachment(false)
            ->setActive(true);

        $requestTypeMap = (new AgentRequestType())
            ->setCode('MAP_REQUEST')
            ->setLabel('Demande de carte')
            ->setDescription('Creation ou mise a jour de carte')
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
