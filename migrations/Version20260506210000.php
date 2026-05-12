<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260506210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Normalize account user types to admin_external/admin_sso/agent_sso/external';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE app_user SET user_type = 'admin_external' WHERE user_type = 'admin'");
        $this->addSql("UPDATE app_user SET user_type = 'agent_sso' WHERE user_type = 'agent'");
        $this->addSql("UPDATE app_user SET user_type = 'external' WHERE user_type IN ('external_professional', 'external_company', 'external_partner')");
        $this->addSql("UPDATE app_user SET user_type = 'external' WHERE COALESCE(TRIM(user_type), '') = ''");
        $this->addSql("ALTER TABLE app_user ALTER COLUMN user_type SET DEFAULT 'external'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE app_user SET user_type = 'admin' WHERE user_type IN ('admin_external', 'admin_sso')");
        $this->addSql("UPDATE app_user SET user_type = 'agent' WHERE user_type = 'agent_sso'");
        $this->addSql("UPDATE app_user SET user_type = 'external_professional' WHERE user_type = 'external'");
        $this->addSql("ALTER TABLE app_user ALTER COLUMN user_type SET DEFAULT 'external_professional'");
    }
}

