<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260506213000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Sync authentication provider with normalized user account types';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE app_user SET auth_provider = 'sso' WHERE user_type IN ('admin_sso', 'agent_sso')");
        $this->addSql("UPDATE app_user SET auth_provider = 'local', sso_subject = NULL WHERE user_type IN ('admin_external', 'external')");
        $this->addSql("UPDATE app_user SET auth_provider = 'local' WHERE COALESCE(TRIM(auth_provider), '') = ''");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE app_user SET auth_provider = 'local'");
    }
}

