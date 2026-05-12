<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260505103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add metadata column to unified data sources';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE data_source ADD metadata JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE data_source DROP metadata');
    }
}
