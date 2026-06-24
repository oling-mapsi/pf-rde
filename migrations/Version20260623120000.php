<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260623120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Expand external resource requests into a shared request base model';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE external_resource_request ALTER requester_id DROP NOT NULL');
        $this->addSql(<<<'SQL'
DO $$
BEGIN
    IF EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conname = 'fk_6e7d5e81c78ac0c3'
    ) THEN
        ALTER TABLE external_resource_request DROP CONSTRAINT "FK_6E7D5E81C78AC0C3";
    ELSIF EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conname = 'fk_external_request_user'
    ) THEN
        ALTER TABLE external_resource_request DROP CONSTRAINT fk_external_request_user;
    END IF;
END
$$;
SQL);
        $this->addSql('ALTER TABLE external_resource_request ADD CONSTRAINT FK_6E7D5E81C78AC0C3 FOREIGN KEY (requester_id) REFERENCES app_user (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE external_resource_request ADD request_number VARCHAR(40) DEFAULT NULL');
        $this->addSql('ALTER TABLE external_resource_request ADD requester_type VARCHAR(32) DEFAULT \'professional\' NOT NULL');
        $this->addSql('ALTER TABLE external_resource_request ADD last_name VARCHAR(120) DEFAULT NULL');
        $this->addSql('ALTER TABLE external_resource_request ADD first_name VARCHAR(120) DEFAULT NULL');
        $this->addSql('ALTER TABLE external_resource_request ADD email VARCHAR(180) DEFAULT NULL');
        $this->addSql('ALTER TABLE external_resource_request ADD phone_number VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE external_resource_request ADD organization_name VARCHAR(180) DEFAULT NULL');
        $this->addSql('ALTER TABLE external_resource_request ADD company_siret VARCHAR(14) DEFAULT NULL');
        $this->addSql('ALTER TABLE external_resource_request ADD postal_code VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE external_resource_request ADD city VARCHAR(120) DEFAULT NULL');
        $this->addSql('ALTER TABLE external_resource_request ADD request_kind VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE external_resource_request ADD network_types JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE external_resource_request ADD additional_information TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE external_resource_request ADD data_formats JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE external_resource_request ADD projection_system VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE external_resource_request ADD map_formats JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE external_resource_request ADD map_scale VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE external_resource_request ADD delivery_destination VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE external_resource_request ADD privacy_consent BOOLEAN DEFAULT FALSE NOT NULL');
        $this->addSql('ALTER TABLE external_resource_request ADD notice_version VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE external_resource_request ADD acknowledged_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE external_resource_request ADD processed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE external_resource_request ALTER status SET DEFAULT \'submitted\'');
        $this->addSql("UPDATE external_resource_request SET request_number = CONCAT('RDG-EXT-', TO_CHAR(submitted_at, 'YYYYMMDD'), '-', LPAD(id::text, 4, '0')) WHERE request_number IS NULL");
        $this->addSql('ALTER TABLE external_resource_request ALTER request_number SET NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6E7D5E8189736B0E ON external_resource_request (request_number)');
        $this->addSql('CREATE INDEX IDX_6E7D5E81A76ED395 ON external_resource_request (requester_type)');
        $this->addSql('CREATE INDEX IDX_6E7D5E81B1EA4DD7 ON external_resource_request (request_kind)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_6E7D5E8189736B0E');
        $this->addSql('DROP INDEX IDX_6E7D5E81A76ED395');
        $this->addSql('DROP INDEX IDX_6E7D5E81B1EA4DD7');
        $this->addSql('ALTER TABLE external_resource_request DROP request_number');
        $this->addSql('ALTER TABLE external_resource_request DROP requester_type');
        $this->addSql('ALTER TABLE external_resource_request DROP last_name');
        $this->addSql('ALTER TABLE external_resource_request DROP first_name');
        $this->addSql('ALTER TABLE external_resource_request DROP email');
        $this->addSql('ALTER TABLE external_resource_request DROP phone_number');
        $this->addSql('ALTER TABLE external_resource_request DROP organization_name');
        $this->addSql('ALTER TABLE external_resource_request DROP company_siret');
        $this->addSql('ALTER TABLE external_resource_request DROP postal_code');
        $this->addSql('ALTER TABLE external_resource_request DROP city');
        $this->addSql('ALTER TABLE external_resource_request DROP request_kind');
        $this->addSql('ALTER TABLE external_resource_request DROP network_types');
        $this->addSql('ALTER TABLE external_resource_request DROP additional_information');
        $this->addSql('ALTER TABLE external_resource_request DROP data_formats');
        $this->addSql('ALTER TABLE external_resource_request DROP projection_system');
        $this->addSql('ALTER TABLE external_resource_request DROP map_formats');
        $this->addSql('ALTER TABLE external_resource_request DROP map_scale');
        $this->addSql('ALTER TABLE external_resource_request DROP delivery_destination');
        $this->addSql('ALTER TABLE external_resource_request DROP privacy_consent');
        $this->addSql('ALTER TABLE external_resource_request DROP notice_version');
        $this->addSql('ALTER TABLE external_resource_request DROP acknowledged_at');
        $this->addSql('ALTER TABLE external_resource_request DROP processed_at');
        $this->addSql('ALTER TABLE external_resource_request ALTER requester_id SET NOT NULL');
        $this->addSql('ALTER TABLE external_resource_request DROP CONSTRAINT IF EXISTS "FK_6E7D5E81C78AC0C3"');
        $this->addSql('ALTER TABLE external_resource_request ADD CONSTRAINT fk_external_request_user FOREIGN KEY (requester_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
