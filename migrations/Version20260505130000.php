<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260505130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add icon key to data sources and backfill by source type';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE data_source ADD icon_key VARCHAR(64) DEFAULT NULL');
        $this->addSql("UPDATE data_source SET icon_key = 'layers' WHERE source_type = 'cartography_link' AND icon_key IS NULL");
        $this->addSql("UPDATE data_source SET icon_key = 'satellite' WHERE source_type = 'wms' AND icon_key IS NULL");
        $this->addSql("UPDATE data_source SET icon_key = 'database' WHERE source_type = 'wfs' AND icon_key IS NULL");
        $this->addSql("UPDATE data_source SET icon_key = 'file-json' WHERE source_type = 'data_file' AND icon_key IS NULL");
        $this->addSql("UPDATE data_source SET icon_key = 'map' WHERE source_type = 'static_map' AND icon_key IS NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE data_source DROP icon_key');
    }
}
