<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260707123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute des tokens de base separes pour liens et boutons.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE homepage_content ADD link_color VARCHAR(32) DEFAULT NULL, ADD link_hover_color VARCHAR(32) DEFAULT NULL, ADD button_primary_background_color VARCHAR(32) DEFAULT NULL, ADD button_primary_border_color VARCHAR(32) DEFAULT NULL, ADD button_primary_text_color VARCHAR(32) DEFAULT NULL, ADD button_primary_background_hover_color VARCHAR(32) DEFAULT NULL, ADD button_primary_border_hover_color VARCHAR(32) DEFAULT NULL, ADD button_primary_text_hover_color VARCHAR(32) DEFAULT NULL, ADD button_outline_background_color VARCHAR(32) DEFAULT NULL, ADD button_outline_border_color VARCHAR(32) DEFAULT NULL, ADD button_outline_text_color VARCHAR(32) DEFAULT NULL, ADD button_outline_background_hover_color VARCHAR(32) DEFAULT NULL, ADD button_outline_border_hover_color VARCHAR(32) DEFAULT NULL, ADD button_outline_text_hover_color VARCHAR(32) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE homepage_content DROP link_color, DROP link_hover_color, DROP button_primary_background_color, DROP button_primary_border_color, DROP button_primary_text_color, DROP button_primary_background_hover_color, DROP button_primary_border_hover_color, DROP button_primary_text_hover_color, DROP button_outline_background_color, DROP button_outline_border_color, DROP button_outline_text_color, DROP button_outline_background_hover_color, DROP button_outline_border_hover_color, DROP button_outline_text_hover_color');
    }
}
