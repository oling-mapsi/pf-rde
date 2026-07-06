<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260706154000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute l image de fond du hero d accueil';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE homepage_content ADD hero_background_image_path VARCHAR(512) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE homepage_content DROP hero_background_image_path');
    }
}
