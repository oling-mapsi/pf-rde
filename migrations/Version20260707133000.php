<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260707133000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les reglages admin du fond des icones de themes du hero.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE homepage_content ADD hero_theme_icon_background_color VARCHAR(32) DEFAULT NULL, ADD hero_theme_icon_background_opacity VARCHAR(16) DEFAULT NULL, ADD hero_theme_icon_padding VARCHAR(32) DEFAULT NULL, ADD hero_theme_icon_margin VARCHAR(32) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE homepage_content DROP hero_theme_icon_background_color, DROP hero_theme_icon_background_opacity, DROP hero_theme_icon_padding, DROP hero_theme_icon_margin');
    }
}
