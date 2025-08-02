<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250801170922 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__service_public AS SELECT id, categorie_id, nom, description, adresse_complete, code_postal, ville, latitude, longitude, telephone, email, site_web, horaires_ouverture, accessibilite_pmr, statut, source_donnees, slug, created_at, updated_at FROM service_public');
        $this->addSql('DROP TABLE service_public');
        $this->addSql('CREATE TABLE service_public (id BLOB NOT NULL --(DC2Type:uuid)
        , categorie_id BLOB DEFAULT NULL --(DC2Type:uuid)
        , nom VARCHAR(200) NOT NULL, description CLOB DEFAULT NULL, adresse_complete CLOB DEFAULT NULL, code_postal VARCHAR(5) NOT NULL, ville VARCHAR(100) NOT NULL, latitude NUMERIC(10, 8) DEFAULT NULL, longitude NUMERIC(11, 8) DEFAULT NULL, telephone VARCHAR(20) DEFAULT NULL, email VARCHAR(180) DEFAULT NULL, site_web VARCHAR(255) DEFAULT NULL, horaires_ouverture CLOB DEFAULT NULL --(DC2Type:json)
        , accessibilite_pmr BOOLEAN NOT NULL, statut VARCHAR(20) NOT NULL, source_donnees VARCHAR(100) DEFAULT NULL, slug VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , id_gouv VARCHAR(255) DEFAULT NULL, id_externe VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id), CONSTRAINT FK_5732B2BBCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie_service (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO service_public (id, categorie_id, nom, description, adresse_complete, code_postal, ville, latitude, longitude, telephone, email, site_web, horaires_ouverture, accessibilite_pmr, statut, source_donnees, slug, created_at, updated_at) SELECT id, categorie_id, nom, description, adresse_complete, code_postal, ville, latitude, longitude, telephone, email, site_web, horaires_ouverture, accessibilite_pmr, statut, source_donnees, slug, created_at, updated_at FROM __temp__service_public');
        $this->addSql('DROP TABLE __temp__service_public');
        $this->addSql('CREATE INDEX idx_categorie_statut ON service_public (categorie_id, statut)');
        $this->addSql('CREATE INDEX idx_localisation ON service_public (ville, code_postal)');
        $this->addSql('CREATE INDEX IDX_5732B2BBCF5E72D ON service_public (categorie_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5732B2B989D9B62 ON service_public (slug)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5732B2BA430D903 ON service_public (id_gouv)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__service_public AS SELECT id, categorie_id, nom, description, adresse_complete, code_postal, ville, latitude, longitude, telephone, email, site_web, horaires_ouverture, accessibilite_pmr, statut, source_donnees, slug, created_at, updated_at FROM service_public');
        $this->addSql('DROP TABLE service_public');
        $this->addSql('CREATE TABLE service_public (id BLOB NOT NULL --(DC2Type:uuid)
        , categorie_id BLOB DEFAULT NULL --(DC2Type:uuid)
        , nom VARCHAR(200) NOT NULL, description CLOB DEFAULT NULL, adresse_complete CLOB DEFAULT NULL, code_postal VARCHAR(5) NOT NULL, ville VARCHAR(100) NOT NULL, latitude NUMERIC(10, 8) DEFAULT NULL, longitude NUMERIC(11, 8) DEFAULT NULL, telephone VARCHAR(20) DEFAULT NULL, email VARCHAR(180) DEFAULT NULL, site_web VARCHAR(255) DEFAULT NULL, horaires_ouverture CLOB DEFAULT NULL --(DC2Type:json)
        , accessibilite_pmr BOOLEAN NOT NULL, statut VARCHAR(20) NOT NULL, source_donnees VARCHAR(100) DEFAULT NULL, slug VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , PRIMARY KEY(id), CONSTRAINT FK_5732B2BBCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie_service (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO service_public (id, categorie_id, nom, description, adresse_complete, code_postal, ville, latitude, longitude, telephone, email, site_web, horaires_ouverture, accessibilite_pmr, statut, source_donnees, slug, created_at, updated_at) SELECT id, categorie_id, nom, description, adresse_complete, code_postal, ville, latitude, longitude, telephone, email, site_web, horaires_ouverture, accessibilite_pmr, statut, source_donnees, slug, created_at, updated_at FROM __temp__service_public');
        $this->addSql('DROP TABLE __temp__service_public');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5732B2B989D9B62 ON service_public (slug)');
        $this->addSql('CREATE INDEX IDX_5732B2BBCF5E72D ON service_public (categorie_id)');
        $this->addSql('CREATE INDEX idx_localisation ON service_public (ville, code_postal)');
        $this->addSql('CREATE INDEX idx_categorie_statut ON service_public (categorie_id, statut)');
    }
}
