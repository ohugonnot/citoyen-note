<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250728192230 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE categorie_service (id BLOB NOT NULL --(DC2Type:uuid)
        , nom VARCHAR(100) NOT NULL, actif BOOLEAN NOT NULL, ordre_affichage INTEGER NOT NULL, slug VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, icone VARCHAR(50) DEFAULT NULL, couleur VARCHAR(7) DEFAULT NULL, created_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BE1E3470989D9B62 ON categorie_service (slug)');
        $this->addSql('CREATE TABLE evaluation (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER DEFAULT NULL, service_public_id BLOB NOT NULL --(DC2Type:uuid)
        , uuid BLOB NOT NULL --(DC2Type:uuid)
        , note SMALLINT NOT NULL, commentaire CLOB DEFAULT NULL, criteres_specifiques CLOB DEFAULT NULL --(DC2Type:json)
        , statut VARCHAR(20) NOT NULL, est_anonyme BOOLEAN NOT NULL, est_verifie BOOLEAN NOT NULL, nombre_utile INTEGER NOT NULL, nombre_signalement INTEGER NOT NULL, created_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_1323A575A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_1323A575ED25B1DB FOREIGN KEY (service_public_id) REFERENCES service_public (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1323A575D17F50A6 ON evaluation (uuid)');
        $this->addSql('CREATE INDEX IDX_1323A575A76ED395 ON evaluation (user_id)');
        $this->addSql('CREATE INDEX IDX_1323A575ED25B1DB ON evaluation (service_public_id)');
        $this->addSql('CREATE INDEX idx_service_note ON evaluation (service_public_id, note)');
        $this->addSql('CREATE TABLE service_public (id BLOB NOT NULL --(DC2Type:uuid)
        , categorie_id BLOB DEFAULT NULL --(DC2Type:uuid)
        , nom VARCHAR(200) NOT NULL, description CLOB DEFAULT NULL, adresse_complete CLOB DEFAULT NULL, code_postal VARCHAR(5) NOT NULL, ville VARCHAR(100) NOT NULL, latitude NUMERIC(10, 8) DEFAULT NULL, longitude NUMERIC(11, 8) DEFAULT NULL, telephone VARCHAR(20) DEFAULT NULL, email VARCHAR(180) DEFAULT NULL, site_web VARCHAR(255) DEFAULT NULL, horaires_ouverture CLOB DEFAULT NULL --(DC2Type:json)
        , accessibilite_pmr BOOLEAN NOT NULL, statut VARCHAR(20) NOT NULL, source_donnees VARCHAR(100) DEFAULT NULL, slug VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , PRIMARY KEY(id), CONSTRAINT FK_5732B2BBCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie_service (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5732B2B989D9B62 ON service_public (slug)');
        $this->addSql('CREATE INDEX IDX_5732B2BBCF5E72D ON service_public (categorie_id)');
        $this->addSql('CREATE INDEX idx_localisation ON service_public (ville, code_postal)');
        $this->addSql('CREATE INDEX idx_categorie_statut ON service_public (categorie_id, statut)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE categorie_service');
        $this->addSql('DROP TABLE evaluation');
        $this->addSql('DROP TABLE service_public');
    }
}
