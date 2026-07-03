<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260703110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Align official map themes in production and reassign demo datasets to them';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
INSERT INTO taxonomy_term (uuid, taxonomy, slug, label, description, metadata, active, created_at, updated_at)
VALUES
    (gen_random_uuid(), 'map_theme', 'chaussees-et-accotements', 'Chaussées et accotements', 'Données relatives au réseau et aux dépendances immédiates.', '{"iconKey":"route","colorHex":"#FC5000","featuredOnHomepage":true,"position":10}', true, NOW(), NOW()),
    (gen_random_uuid(), 'map_theme', 'mobilite', 'Mobilité', 'Aménagements cyclables, arrêts de transports en commun et déplacements.', '{"iconKey":"transport","colorHex":"#38B4E7","featuredOnHomepage":true,"position":20}', true, NOW(), NOW()),
    (gen_random_uuid(), 'map_theme', 'ouvrages-art', 'Ouvrages d’art', 'Ponts, ouvrages et informations de suivi patrimonial.', '{"iconKey":"bridge","colorHex":"#15366F","featuredOnHomepage":true,"position":30}', true, NOW(), NOW()),
    (gen_random_uuid(), 'map_theme', 'equipements-securite', 'Équipements de sécurité', 'Signalisation et dispositifs de sécurité routière.', '{"iconKey":"shield","colorHex":"#AAAE02","featuredOnHomepage":true,"position":40}', true, NOW(), NOW()),
    (gen_random_uuid(), 'map_theme', 'dependances-vertes-bleues', 'Dépendances vertes et bleues', 'Espaces végétalisés, hydraulique et abords du domaine routier.', '{"iconKey":"globe","colorHex":"#1F8A5B","featuredOnHomepage":true,"position":50}', true, NOW(), NOW()),
    (gen_random_uuid(), 'map_theme', 'circulation-routiere', 'Circulation routière', 'Vitesses, limites d’agglomération, trafic et informations de circulation.', '{"iconKey":"traffic","colorHex":"#FBD002","featuredOnHomepage":true,"position":60}', true, NOW(), NOW()),
    (gen_random_uuid(), 'map_theme', 'milieu-environnant', 'Milieu environnant', 'Contexte territorial, risques et contraintes externes au réseau.', '{"iconKey":"map-pin","colorHex":"#725AC1","featuredOnHomepage":true,"position":70}', true, NOW(), NOW()),
    (gen_random_uuid(), 'map_theme', 'referentiels-croises', 'Référentiels croisés', 'Référentiels transverses pour croiser les analyses.', '{"iconKey":"layers","colorHex":"#2D6CDF","featuredOnHomepage":true,"position":80}', true, NOW(), NOW())
ON CONFLICT (taxonomy, slug) DO UPDATE
SET
    label = EXCLUDED.label,
    description = EXCLUDED.description,
    metadata = EXCLUDED.metadata,
    active = EXCLUDED.active,
    updated_at = NOW()
SQL);

        $this->addSql("UPDATE taxonomy_term SET active = false, updated_at = NOW() WHERE taxonomy = 'map_theme' AND slug IN ('securite-routiere', 'patrimoine', 'territoire', 'circulation')");

        $this->addSql("UPDATE static_map SET theme = 'Chaussées et accotements' WHERE slug = 'reseau-routier-principal'");

        $this->addSql("UPDATE data_source SET theme = 'Circulation routière' WHERE slug IN ('source-carto-trafic-temps-reel', 'source-service-wfs-travaux', 'source-fichier-csv-comptages-2026', 'source-visualisation-reseau', 'service-wfs-travaux-planifies')");
        $this->addSql("UPDATE data_source SET theme = 'Chaussées et accotements' WHERE slug IN ('source-carte-statique-reseau-a3', 'source-carte-statique-reseau-png-hd', 'source-service-wms-reseau-principal', 'source-fichier-geojson-reseau-routier', 'source-carte-reseau-routier-principal', 'fichier-reseau-routier-geojson', 'source-static-map-reseau-routier-principal')");
        $this->addSql("UPDATE data_source SET theme = 'Équipements de sécurité' WHERE slug IN ('source-service-wfs-equipements')");
        $this->addSql("UPDATE data_source SET theme = 'Ouvrages d’art' WHERE slug IN ('source-fichier-gpkg-ouvrages-art')");
        $this->addSql("UPDATE data_source SET theme = 'Référentiels croisés' WHERE slug IN ('source-service-wms-orthophoto', 'source-carto-consultation-generale')");

        $this->addSql("UPDATE data_source SET theme = 'Mobilité' WHERE theme = 'Mobilite'");
        $this->addSql("UPDATE data_source SET theme = 'Milieu environnant' WHERE theme = 'Territoire'");
        $this->addSql("UPDATE data_source SET theme = 'Équipements de sécurité' WHERE theme = 'Securite routiere'");
        $this->addSql("UPDATE data_source SET theme = 'Ouvrages d’art' WHERE theme = 'Patrimoine'");
        $this->addSql("UPDATE static_map SET theme = 'Mobilité' WHERE theme = 'Mobilite'");
        $this->addSql("UPDATE static_map SET theme = 'Milieu environnant' WHERE theme = 'Territoire'");
        $this->addSql("UPDATE static_map SET theme = 'Équipements de sécurité' WHERE theme = 'Securite routiere'");
        $this->addSql("UPDATE static_map SET theme = 'Ouvrages d’art' WHERE theme = 'Patrimoine'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE data_source SET theme = 'Mobilite' WHERE theme = 'Mobilité'");
        $this->addSql("UPDATE data_source SET theme = 'Territoire' WHERE theme = 'Milieu environnant'");
        $this->addSql("UPDATE data_source SET theme = 'Securite routiere' WHERE theme = 'Équipements de sécurité'");
        $this->addSql("UPDATE data_source SET theme = 'Patrimoine' WHERE theme = 'Ouvrages d’art'");
        $this->addSql("UPDATE static_map SET theme = 'Mobilite' WHERE theme = 'Mobilité'");
        $this->addSql("UPDATE static_map SET theme = 'Territoire' WHERE theme = 'Milieu environnant'");
        $this->addSql("UPDATE static_map SET theme = 'Securite routiere' WHERE theme = 'Équipements de sécurité'");
        $this->addSql("UPDATE static_map SET theme = 'Patrimoine' WHERE theme = 'Ouvrages d’art'");
    }
}
