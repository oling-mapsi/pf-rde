<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260505110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Backfill unified data sources from existing maps';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO data_source (
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
            keywords,
            format,
            thumbnail_path,
            status,
            published_at,
            created_at,
            updated_at,
            metadata
        )
        SELECT
            m.id,
            m.created_by_id,
            m.updated_by_id,
            gen_random_uuid(),
            CONCAT('source-', m.slug),
            m.title,
            'static_map',
            m.summary,
            m.description,
            m.theme,
            m.keywords,
            'carte',
            m.thumbnail_path,
            m.status,
            m.published_at,
            m.created_at,
            m.updated_at,
            m.metadata
        FROM static_map m
        WHERE NOT EXISTS (
            SELECT 1 FROM data_source s WHERE s.linked_static_map_id = m.id
        )
        ON CONFLICT (slug) DO NOTHING");

        $this->addSql("INSERT INTO data_source (
            linked_interactive_map_id,
            created_by_id,
            updated_by_id,
            uuid,
            slug,
            title,
            source_type,
            summary,
            format,
            status,
            published_at,
            created_at,
            updated_at,
            metadata
        )
        SELECT
            m.id,
            m.created_by_id,
            m.updated_by_id,
            gen_random_uuid(),
            CONCAT('source-', m.slug),
            m.title,
            'cartography_link',
            m.summary,
            'websig',
            m.status,
            m.published_at,
            m.created_at,
            m.updated_at,
            m.metadata
        FROM interactive_map m
        WHERE NOT EXISTS (
            SELECT 1 FROM data_source s WHERE s.linked_interactive_map_id = m.id
        )
        ON CONFLICT (slug) DO NOTHING");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM data_source WHERE source_type = 'static_map' AND linked_static_map_id IS NOT NULL");
        $this->addSql("DELETE FROM data_source WHERE source_type = 'cartography_link' AND linked_interactive_map_id IS NOT NULL");
    }
}
