<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260703143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add homepage hero display customization fields';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE homepage_content ADD hero_title_color VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE homepage_content ADD hero_baseline_color VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE homepage_content ADD hero_title_font_size VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE homepage_content ADD hero_baseline_font_size VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE homepage_content ADD hero_search_background_color VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE homepage_content ADD hero_search_text_color VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE homepage_content ADD hero_search_placeholder_color VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE homepage_content ADD hero_search_button_color VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE homepage_content ADD hero_search_button_background_color VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE homepage_content ADD hero_search_border_color VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE homepage_content ADD hero_primary_cta_text_color VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE homepage_content ADD hero_primary_cta_background_color VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE homepage_content ADD hero_themes_gap VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE homepage_content ADD hero_theme_button_radius VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE homepage_content ADD hero_theme_button_padding VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE homepage_content ADD hero_theme_label_color VARCHAR(32) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE homepage_content DROP hero_title_color');
        $this->addSql('ALTER TABLE homepage_content DROP hero_baseline_color');
        $this->addSql('ALTER TABLE homepage_content DROP hero_title_font_size');
        $this->addSql('ALTER TABLE homepage_content DROP hero_baseline_font_size');
        $this->addSql('ALTER TABLE homepage_content DROP hero_search_background_color');
        $this->addSql('ALTER TABLE homepage_content DROP hero_search_text_color');
        $this->addSql('ALTER TABLE homepage_content DROP hero_search_placeholder_color');
        $this->addSql('ALTER TABLE homepage_content DROP hero_search_button_color');
        $this->addSql('ALTER TABLE homepage_content DROP hero_search_button_background_color');
        $this->addSql('ALTER TABLE homepage_content DROP hero_search_border_color');
        $this->addSql('ALTER TABLE homepage_content DROP hero_primary_cta_text_color');
        $this->addSql('ALTER TABLE homepage_content DROP hero_primary_cta_background_color');
        $this->addSql('ALTER TABLE homepage_content DROP hero_themes_gap');
        $this->addSql('ALTER TABLE homepage_content DROP hero_theme_button_radius');
        $this->addSql('ALTER TABLE homepage_content DROP hero_theme_button_padding');
        $this->addSql('ALTER TABLE homepage_content DROP hero_theme_label_color');
    }
}
