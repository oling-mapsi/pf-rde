<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260707120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les reglages de voile du hero et la palette globale administrable.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE homepage_content ADD hero_dark_overlay_opacity VARCHAR(16) DEFAULT NULL, ADD hero_white_veil_opacity VARCHAR(16) DEFAULT NULL, ADD brand_primary_color VARCHAR(32) DEFAULT NULL, ADD brand_secondary_color VARCHAR(32) DEFAULT NULL, ADD brand_accent_color VARCHAR(32) DEFAULT NULL, ADD brand_success_color VARCHAR(32) DEFAULT NULL, ADD brand_orange_road_color VARCHAR(32) DEFAULT NULL, ADD text_default_color VARCHAR(32) DEFAULT NULL, ADD text_heading_color VARCHAR(32) DEFAULT NULL, ADD text_muted_color VARCHAR(32) DEFAULT NULL, ADD text_inverse_color VARCHAR(32) DEFAULT NULL, ADD background_default_color VARCHAR(32) DEFAULT NULL, ADD background_surface_alt_color VARCHAR(32) DEFAULT NULL, ADD border_default_color VARCHAR(32) DEFAULT NULL, ADD border_focus_color VARCHAR(32) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE homepage_content DROP hero_dark_overlay_opacity, DROP hero_white_veil_opacity, DROP brand_primary_color, DROP brand_secondary_color, DROP brand_accent_color, DROP brand_success_color, DROP brand_orange_road_color, DROP text_default_color, DROP text_heading_color, DROP text_muted_color, DROP text_inverse_color, DROP background_default_color, DROP background_surface_alt_color, DROP border_default_color, DROP border_focus_color');
    }
}
