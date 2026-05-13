<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260513120829 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add external professional registration fields and email confirmation fields on app_user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user ADD company_siret VARCHAR(14) DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD postal_address VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD account_request_reason TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD email_verification_token_hash VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD email_verified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user DROP company_siret');
        $this->addSql('ALTER TABLE app_user DROP postal_address');
        $this->addSql('ALTER TABLE app_user DROP account_request_reason');
        $this->addSql('ALTER TABLE app_user DROP email_verification_token_hash');
        $this->addSql('ALTER TABLE app_user DROP email_verified_at');
    }
}
