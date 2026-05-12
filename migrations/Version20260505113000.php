<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260505113000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed richer data sources for all source types';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
INSERT INTO data_source (
    linked_interactive_map_id,
    created_by_id,
    updated_by_id,
    uuid,
    slug,
    title,
    source_type,
    summary,
    description,
    theme,
    source_url,
    format,
    license,
    status,
    published_at,
    created_at,
    updated_at
)
VALUES (
    (SELECT id FROM interactive_map WHERE slug = 'visualisation-reseau' LIMIT 1),
    (SELECT id FROM app_user WHERE email = 'admin@routesguadeloupe.local' LIMIT 1),
    (SELECT id FROM app_user WHERE email = 'admin@routesguadeloupe.local' LIMIT 1),
    gen_random_uuid(),
    'source-carto-trafic-temps-reel',
    'Cartographie trafic temps reel',
    'cartography_link',
    'Vue cartographique orientee exploitation avec focus sur trafic et incidents.',
    'Point d''entree vers la cartographie operationnelle pour analyser les evenements de circulation.',
    'Mobilite',
    '/cartes-interactives/visualisation-reseau?mode=trafic',
    'websig',
    'Usage interne',
    'published',
    NOW(),
    NOW(),
    NOW()
)
ON CONFLICT (slug) DO NOTHING
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO data_source (
    created_by_id,
    updated_by_id,
    uuid,
    slug,
    title,
    source_type,
    summary,
    description,
    theme,
    source_url,
    format,
    license,
    status,
    published_at,
    created_at,
    updated_at
)
VALUES (
    (SELECT id FROM app_user WHERE email = 'admin@routesguadeloupe.local' LIMIT 1),
    (SELECT id FROM app_user WHERE email = 'admin@routesguadeloupe.local' LIMIT 1),
    gen_random_uuid(),
    'source-carto-consultation-generale',
    'Portail cartographique de consultation',
    'cartography_link',
    'Acces grand public vers les couches de reference et les vues thematiques.',
    'Lien externe de consultation destine aux partenaires et usagers.',
    'Mobilite',
    'https://geo.guadeloupe.gouv.fr/viewer',
    'web',
    'Open Data',
    'published',
    NOW(),
    NOW(),
    NOW()
)
ON CONFLICT (slug) DO NOTHING
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO data_source (
    linked_static_map_id,
    created_by_id,
    updated_by_id,
    uuid,
    slug,
    title,
    source_type,
    summary,
    description,
    theme,
    source_url,
    file_path,
    format,
    license,
    status,
    published_at,
    created_at,
    updated_at
)
VALUES (
    (SELECT id FROM static_map WHERE slug = 'reseau-routier-principal' LIMIT 1),
    (SELECT id FROM app_user WHERE email = 'admin@routesguadeloupe.local' LIMIT 1),
    (SELECT id FROM app_user WHERE email = 'admin@routesguadeloupe.local' LIMIT 1),
    gen_random_uuid(),
    'source-carte-statique-reseau-a3',
    'Carte statique reseau routier A3',
    'static_map',
    'Version PDF haute qualite pour diffusion institutionnelle.',
    'Carte de reference au format A3 orientee communication et impression.',
    'Mobilite',
    '/files/maps/reseau-routier-principal-a3.pdf',
    '/files/maps/reseau-routier-principal-a3.pdf',
    'pdf',
    'Open License',
    'published',
    NOW(),
    NOW(),
    NOW()
)
ON CONFLICT (slug) DO NOTHING
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO data_source (
    linked_static_map_id,
    created_by_id,
    updated_by_id,
    uuid,
    slug,
    title,
    source_type,
    summary,
    description,
    theme,
    source_url,
    file_path,
    format,
    license,
    status,
    published_at,
    created_at,
    updated_at
)
VALUES (
    (SELECT id FROM static_map WHERE slug = 'reseau-routier-principal' LIMIT 1),
    (SELECT id FROM app_user WHERE email = 'admin@routesguadeloupe.local' LIMIT 1),
    (SELECT id FROM app_user WHERE email = 'admin@routesguadeloupe.local' LIMIT 1),
    gen_random_uuid(),
    'source-carte-statique-reseau-png-hd',
    'Carte statique reseau routier PNG HD',
    'static_map',
    'Version image HD pour integration dans presentations et notes.',
    'Support graphique de consultation rapide du reseau principal.',
    'Mobilite',
    '/files/maps/reseau-routier-principal.png',
    '/files/maps/reseau-routier-principal.png',
    'png',
    'Open License',
    'published',
    NOW(),
    NOW(),
    NOW()
)
ON CONFLICT (slug) DO NOTHING
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO data_source (
    service_endpoint_id,
    created_by_id,
    updated_by_id,
    uuid,
    slug,
    title,
    source_type,
    summary,
    description,
    theme,
    source_url,
    format,
    license,
    status,
    published_at,
    created_at,
    updated_at
)
VALUES (
    (SELECT id FROM map_service_endpoint WHERE service_type = 'wms' ORDER BY id ASC LIMIT 1),
    (SELECT id FROM app_user WHERE email = 'admin@routesguadeloupe.local' LIMIT 1),
    (SELECT id FROM app_user WHERE email = 'admin@routesguadeloupe.local' LIMIT 1),
    gen_random_uuid(),
    'source-service-wms-reseau-principal',
    'Service WMS reseau principal',
    'wms',
    'Flux WMS pour visualiser le reseau principal dans les SIG clients.',
    'Service OGC WMS principal pour consultation multicouches.',
    'Mobilite',
    COALESCE((SELECT base_url FROM map_service_endpoint WHERE service_type = 'wms' ORDER BY id ASC LIMIT 1), 'https://services.rdg.local/wms'),
    'image/png',
    'Open License',
    'published',
    NOW(),
    NOW(),
    NOW()
)
ON CONFLICT (slug) DO NOTHING
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO data_source (
    created_by_id,
    updated_by_id,
    uuid,
    slug,
    title,
    source_type,
    summary,
    description,
    theme,
    source_url,
    format,
    license,
    status,
    published_at,
    created_at,
    updated_at
)
VALUES (
    (SELECT id FROM app_user WHERE email = 'admin@routesguadeloupe.local' LIMIT 1),
    (SELECT id FROM app_user WHERE email = 'admin@routesguadeloupe.local' LIMIT 1),
    gen_random_uuid(),
    'source-service-wms-orthophoto',
    'Service WMS fond orthophoto',
    'wms',
    'Flux WMS de fond de plan orthophoto pour superposition cartographique.',
    'Service de reference pour contextualisation visuelle des couches metier.',
    'Territoire',
    'https://services.rdg.local/wms/orthophoto',
    'image/jpeg',
    'Open License',
    'published',
    NOW(),
    NOW(),
    NOW()
)
ON CONFLICT (slug) DO NOTHING
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO data_source (
    service_endpoint_id,
    created_by_id,
    updated_by_id,
    uuid,
    slug,
    title,
    source_type,
    summary,
    description,
    theme,
    source_url,
    format,
    license,
    status,
    published_at,
    created_at,
    updated_at
)
VALUES (
    (SELECT id FROM map_service_endpoint WHERE service_type = 'wfs' ORDER BY id ASC LIMIT 1),
    (SELECT id FROM app_user WHERE email = 'admin@routesguadeloupe.local' LIMIT 1),
    (SELECT id FROM app_user WHERE email = 'admin@routesguadeloupe.local' LIMIT 1),
    gen_random_uuid(),
    'source-service-wfs-travaux',
    'Service WFS travaux routiers',
    'wfs',
    'Flux WFS des zones de travaux et interventions planifiees.',
    'Service OGC WFS pour exploitation analytique des objets travaux.',
    'Mobilite',
    COALESCE((SELECT base_url FROM map_service_endpoint WHERE service_type = 'wfs' ORDER BY id ASC LIMIT 1), 'https://services.rdg.local/wfs'),
    'application/json',
    'Open License',
    'published',
    NOW(),
    NOW(),
    NOW()
)
ON CONFLICT (slug) DO NOTHING
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO data_source (
    created_by_id,
    updated_by_id,
    uuid,
    slug,
    title,
    source_type,
    summary,
    description,
    theme,
    source_url,
    format,
    license,
    status,
    published_at,
    created_at,
    updated_at
)
VALUES (
    (SELECT id FROM app_user WHERE email = 'admin@routesguadeloupe.local' LIMIT 1),
    (SELECT id FROM app_user WHERE email = 'admin@routesguadeloupe.local' LIMIT 1),
    gen_random_uuid(),
    'source-service-wfs-equipements',
    'Service WFS equipements de securite',
    'wfs',
    'Flux WFS des equipements de securite et signalisation verticale.',
    'Service de consultation vectorielle pour analyses metier.',
    'Securite routiere',
    'https://services.rdg.local/wfs/equipements',
    'application/gml+xml',
    'Open License',
    'published',
    NOW(),
    NOW(),
    NOW()
)
ON CONFLICT (slug) DO NOTHING
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO data_source (
    created_by_id,
    updated_by_id,
    uuid,
    slug,
    title,
    source_type,
    summary,
    description,
    theme,
    source_url,
    format,
    license,
    status,
    published_at,
    created_at,
    updated_at
)
VALUES (
    (SELECT id FROM app_user WHERE email = 'admin@routesguadeloupe.local' LIMIT 1),
    (SELECT id FROM app_user WHERE email = 'admin@routesguadeloupe.local' LIMIT 1),
    gen_random_uuid(),
    'source-fichier-geojson-reseau-routier',
    'Fichier GeoJSON reseau routier',
    'data_file',
    'Export GeoJSON du reseau routier principal.',
    'Fichier pret a l''emploi pour reutilisation dans SIG ou outils BI.',
    'Mobilite',
    'https://example.local/datasets/reseau-routier.geojson',
    'geojson',
    'Open License',
    'published',
    NOW(),
    NOW(),
    NOW()
)
ON CONFLICT (slug) DO NOTHING
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO data_source (
    created_by_id,
    updated_by_id,
    uuid,
    slug,
    title,
    source_type,
    summary,
    description,
    theme,
    source_url,
    file_path,
    format,
    license,
    status,
    published_at,
    created_at,
    updated_at
)
VALUES (
    (SELECT id FROM app_user WHERE email = 'admin@routesguadeloupe.local' LIMIT 1),
    (SELECT id FROM app_user WHERE email = 'admin@routesguadeloupe.local' LIMIT 1),
    gen_random_uuid(),
    'source-fichier-csv-comptages-2026',
    'Fichier CSV comptages 2026',
    'data_file',
    'Serie de comptages routiers 2026 consolidee.',
    'Fichier tabulaire pour analyses temporelles et tableaux de bord.',
    'Mobilite',
    '/files/datasets/comptages-2026.csv',
    '/files/datasets/comptages-2026.csv',
    'csv',
    'Open License',
    'published',
    NOW(),
    NOW(),
    NOW()
)
ON CONFLICT (slug) DO NOTHING
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO data_source (
    created_by_id,
    updated_by_id,
    uuid,
    slug,
    title,
    source_type,
    summary,
    description,
    theme,
    source_url,
    file_path,
    format,
    license,
    status,
    published_at,
    created_at,
    updated_at
)
VALUES (
    (SELECT id FROM app_user WHERE email = 'admin@routesguadeloupe.local' LIMIT 1),
    (SELECT id FROM app_user WHERE email = 'admin@routesguadeloupe.local' LIMIT 1),
    gen_random_uuid(),
    'source-fichier-gpkg-ouvrages-art',
    'Fichier GeoPackage ouvrages d art',
    'data_file',
    'Inventaire geopackage des ouvrages d art et sections associees.',
    'Jeu de donnees structure pour analyses spatiales avancees.',
    'Patrimoine',
    '/files/datasets/ouvrages-art.gpkg',
    '/files/datasets/ouvrages-art.gpkg',
    'gpkg',
    'Open License',
    'published',
    NOW(),
    NOW(),
    NOW()
)
ON CONFLICT (slug) DO NOTHING
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM data_source WHERE slug IN (
            'source-carto-trafic-temps-reel',
            'source-carto-consultation-generale',
            'source-carte-statique-reseau-a3',
            'source-carte-statique-reseau-png-hd',
            'source-service-wms-reseau-principal',
            'source-service-wms-orthophoto',
            'source-service-wfs-travaux',
            'source-service-wfs-equipements',
            'source-fichier-geojson-reseau-routier',
            'source-fichier-csv-comptages-2026',
            'source-fichier-gpkg-ouvrages-art'
        )");
    }
}
