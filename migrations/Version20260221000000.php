<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260221000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create all tables for OFF Dashboard';
    }

    public function up(Schema $schema): void
    {
        // User table
        $this->addSql('CREATE TABLE "user" (
            id UUID NOT NULL,
            email VARCHAR(180) NOT NULL,
            password VARCHAR(255) NOT NULL,
            roles JSON NOT NULL,
            failed_login_count INT DEFAULT 0 NOT NULL,
            is_locked BOOLEAN DEFAULT false NOT NULL,
            locked_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            email_auth_code VARCHAR(10) DEFAULT NULL,
            email_auth_code_expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');
        $this->addSql('COMMENT ON COLUMN "user".created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN "user".locked_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN "user".email_auth_code_expires_at IS \'(DC2Type:datetime_immutable)\'');

        // Dashboard table
        $this->addSql('CREATE TABLE dashboard (
            id UUID NOT NULL,
            user_id UUID NOT NULL,
            name VARCHAR(255) NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2AEA2E2CA76ED395 ON dashboard (user_id)');
        $this->addSql('COMMENT ON COLUMN dashboard.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN dashboard.updated_at IS \'(DC2Type:datetime_immutable)\'');

        // Widget table
        $this->addSql('CREATE TABLE widget (
            id UUID NOT NULL,
            dashboard_id UUID NOT NULL,
            type VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            position INT NOT NULL,
            config JSON NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IDX_85F91ED5B2FCD8E ON widget (dashboard_id)');
        $this->addSql('COMMENT ON COLUMN widget.created_at IS \'(DC2Type:datetime_immutable)\'');

        // LoginAttempt table
        $this->addSql('CREATE TABLE login_attempt (
            id UUID NOT NULL,
            user_id UUID DEFAULT NULL,
            email VARCHAR(180) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            success BOOLEAN NOT NULL,
            attempted_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IDX_8C11C1BA76ED395 ON login_attempt (user_id)');
        $this->addSql('COMMENT ON COLUMN login_attempt.attempted_at IS \'(DC2Type:datetime_immutable)\'');

        // Foreign keys
        $this->addSql('ALTER TABLE dashboard ADD CONSTRAINT FK_2AEA2E2CA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE widget ADD CONSTRAINT FK_85F91ED5B2FCD8E FOREIGN KEY (dashboard_id) REFERENCES dashboard (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE login_attempt ADD CONSTRAINT FK_8C11C1BA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE dashboard DROP CONSTRAINT FK_2AEA2E2CA76ED395');
        $this->addSql('ALTER TABLE widget DROP CONSTRAINT FK_85F91ED5B2FCD8E');
        $this->addSql('ALTER TABLE login_attempt DROP CONSTRAINT FK_8C11C1BA76ED395');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('DROP TABLE dashboard');
        $this->addSql('DROP TABLE widget');
        $this->addSql('DROP TABLE login_attempt');
    }
}
