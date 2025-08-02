<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250802170850 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__categorie_service AS SELECT id, nom, actif, ordre_affichage, slug, description, icone, couleur, created_at, updated_at FROM categorie_service');
        $this->addSql('DROP TABLE categorie_service');
        $this->addSql('CREATE TABLE categorie_service (id BLOB NOT NULL --(DC2Type:uuid)
        , nom VARCHAR(100) NOT NULL, actif BOOLEAN NOT NULL, ordre_affichage INTEGER NOT NULL, slug VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, icone VARCHAR(50) DEFAULT NULL, couleur VARCHAR(7) DEFAULT NULL, created_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , PRIMARY KEY(id))');
        $this->addSql('INSERT INTO categorie_service (id, nom, actif, ordre_affichage, slug, description, icone, couleur, created_at, updated_at) SELECT id, nom, actif, ordre_affichage, slug, description, icone, couleur, created_at, updated_at FROM __temp__categorie_service');
        $this->addSql('DROP TABLE __temp__categorie_service');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BE1E34706C6E55B5 ON categorie_service (nom)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BE1E3470989D9B62 ON categorie_service (slug)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__evaluation AS SELECT id, user_id, service_public_id, uuid, note, commentaire, criteres_specifiques, statut, est_anonyme, est_verifie, nombre_utile, nombre_signalement, created_at, updated_at, pseudo FROM evaluation');
        $this->addSql('DROP TABLE evaluation');
        $this->addSql('CREATE TABLE evaluation (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER DEFAULT NULL, service_public_id BLOB NOT NULL --(DC2Type:uuid)
        , uuid BLOB NOT NULL --(DC2Type:uuid)
        , note SMALLINT NOT NULL, commentaire CLOB DEFAULT NULL, criteres_specifiques CLOB DEFAULT NULL --(DC2Type:json)
        , statut VARCHAR(20) NOT NULL, est_anonyme BOOLEAN NOT NULL, est_verifie BOOLEAN NOT NULL, nombre_utile INTEGER NOT NULL, nombre_signalement INTEGER NOT NULL, created_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , pseudo VARCHAR(255) DEFAULT NULL, CONSTRAINT FK_1323A575A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_1323A575ED25B1DB FOREIGN KEY (service_public_id) REFERENCES service_public (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO evaluation (id, user_id, service_public_id, uuid, note, commentaire, criteres_specifiques, statut, est_anonyme, est_verifie, nombre_utile, nombre_signalement, created_at, updated_at, pseudo) SELECT id, user_id, service_public_id, uuid, note, commentaire, criteres_specifiques, statut, est_anonyme, est_verifie, nombre_utile, nombre_signalement, created_at, updated_at, pseudo FROM __temp__evaluation');
        $this->addSql('DROP TABLE __temp__evaluation');
        $this->addSql('CREATE INDEX idx_service_note ON evaluation (service_public_id, note)');
        $this->addSql('CREATE INDEX IDX_1323A575ED25B1DB ON evaluation (service_public_id)');
        $this->addSql('CREATE INDEX IDX_1323A575A76ED395 ON evaluation (user_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1323A575D17F50A6 ON evaluation (uuid)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__service_public AS SELECT id, categorie_id, nom, description, adresse_complete, code_postal, ville, latitude, longitude, telephone, email, site_web, horaires_ouverture, accessibilite_pmr, statut, source_donnees, slug, created_at, updated_at, id_gouv, id_externe FROM service_public');
        $this->addSql('DROP TABLE service_public');
        $this->addSql('CREATE TABLE service_public (id BLOB NOT NULL --(DC2Type:uuid)
        , categorie_id BLOB DEFAULT NULL --(DC2Type:uuid)
        , nom VARCHAR(200) NOT NULL, description CLOB DEFAULT NULL, adresse_complete CLOB DEFAULT NULL, code_postal VARCHAR(5) NOT NULL, ville VARCHAR(100) NOT NULL, latitude NUMERIC(10, 8) DEFAULT NULL, longitude NUMERIC(11, 8) DEFAULT NULL, telephone VARCHAR(20) DEFAULT NULL, email VARCHAR(180) DEFAULT NULL, site_web VARCHAR(255) DEFAULT NULL, horaires_ouverture CLOB DEFAULT NULL --(DC2Type:json)
        , accessibilite_pmr BOOLEAN NOT NULL, statut VARCHAR(20) NOT NULL, source_donnees VARCHAR(100) DEFAULT NULL, slug VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , id_gouv VARCHAR(255) DEFAULT NULL, id_externe VARCHAR(255) DEFAULT NULL, score DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY(id), CONSTRAINT FK_5732B2BBCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie_service (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO service_public (id, categorie_id, nom, description, adresse_complete, code_postal, ville, latitude, longitude, telephone, email, site_web, horaires_ouverture, accessibilite_pmr, statut, source_donnees, slug, created_at, updated_at, id_gouv, id_externe) SELECT id, categorie_id, nom, description, adresse_complete, code_postal, ville, latitude, longitude, telephone, email, site_web, horaires_ouverture, accessibilite_pmr, statut, source_donnees, slug, created_at, updated_at, id_gouv, id_externe FROM __temp__service_public');
        $this->addSql('DROP TABLE __temp__service_public');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5732B2BA430D903 ON service_public (id_gouv)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5732B2B989D9B62 ON service_public (slug)');
        $this->addSql('CREATE INDEX IDX_5732B2BBCF5E72D ON service_public (categorie_id)');
        $this->addSql('CREATE INDEX idx_localisation ON service_public (ville, code_postal)');
        $this->addSql('CREATE INDEX idx_categorie_statut ON service_public (categorie_id, statut)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, email, roles, password, pseudo, prenom, nom, date_naissance, telephone, is_verified, accepte_newsletters, score_fiabilite, statut, code_postal, ville, verified_at, derniere_connexion, created_at, updated_at FROM user');
        $this->addSql('DROP TABLE user');
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , password VARCHAR(255) NOT NULL, pseudo VARCHAR(50) DEFAULT NULL, prenom VARCHAR(255) DEFAULT NULL, nom VARCHAR(255) DEFAULT NULL, date_naissance DATE DEFAULT NULL, telephone VARCHAR(20) DEFAULT NULL, is_verified BOOLEAN NOT NULL, accepte_newsletters BOOLEAN NOT NULL, score_fiabilite INTEGER NOT NULL, statut VARCHAR(255) NOT NULL, code_postal VARCHAR(5) DEFAULT NULL, ville VARCHAR(100) DEFAULT NULL, verified_at DATETIME DEFAULT NULL, derniere_connexion DATETIME DEFAULT NULL, created_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('INSERT INTO user (id, email, roles, password, pseudo, prenom, nom, date_naissance, telephone, is_verified, accepte_newsletters, score_fiabilite, statut, code_postal, ville, verified_at, derniere_connexion, created_at, updated_at) SELECT id, email, roles, password, pseudo, prenom, nom, date_naissance, telephone, is_verified, accepte_newsletters, score_fiabilite, statut, code_postal, ville, verified_at, derniere_connexion, created_at, updated_at FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_PSEUDO ON user (pseudo)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON user (email)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__messenger_messages AS SELECT id, body, headers, queue_name, created_at, available_at, delivered_at FROM messenger_messages');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('CREATE TABLE messenger_messages (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, body CLOB NOT NULL, headers CLOB NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , available_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , delivered_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('INSERT INTO messenger_messages (id, body, headers, queue_name, created_at, available_at, delivered_at) SELECT id, body, headers, queue_name, created_at, available_at, delivered_at FROM __temp__messenger_messages');
        $this->addSql('DROP TABLE __temp__messenger_messages');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__categorie_service AS SELECT id, nom, actif, ordre_affichage, slug, description, icone, couleur, created_at, updated_at FROM categorie_service');
        $this->addSql('DROP TABLE categorie_service');
        $this->addSql('CREATE TABLE categorie_service (id BLOB NOT NULL --(DC2Type:uuid)
        , nom VARCHAR(100) NOT NULL, actif BOOLEAN NOT NULL, ordre_affichage INTEGER NOT NULL, slug VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, icone VARCHAR(50) DEFAULT NULL, couleur VARCHAR(7) DEFAULT NULL, created_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , PRIMARY KEY(id))');
        $this->addSql('INSERT INTO categorie_service (id, nom, actif, ordre_affichage, slug, description, icone, couleur, created_at, updated_at) SELECT id, nom, actif, ordre_affichage, slug, description, icone, couleur, created_at, updated_at FROM __temp__categorie_service');
        $this->addSql('DROP TABLE __temp__categorie_service');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BE1E34706C6E55B5 ON categorie_service (nom)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BE1E3470989D9B62 ON categorie_service (slug)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__evaluation AS SELECT id, user_id, service_public_id, uuid, note, commentaire, criteres_specifiques, statut, est_anonyme, est_verifie, nombre_utile, nombre_signalement, pseudo, created_at, updated_at FROM evaluation');
        $this->addSql('DROP TABLE evaluation');
        $this->addSql('CREATE TABLE evaluation (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER DEFAULT NULL, service_public_id BLOB NOT NULL --(DC2Type:uuid)
        , uuid BLOB NOT NULL --(DC2Type:uuid)
        , note SMALLINT NOT NULL, commentaire CLOB DEFAULT NULL, criteres_specifiques CLOB DEFAULT NULL --(DC2Type:json)
        , statut VARCHAR(20) NOT NULL, est_anonyme BOOLEAN NOT NULL, est_verifie BOOLEAN NOT NULL, nombre_utile INTEGER NOT NULL, nombre_signalement INTEGER NOT NULL, pseudo VARCHAR(255) DEFAULT NULL, created_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_1323A575A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_1323A575ED25B1DB FOREIGN KEY (service_public_id) REFERENCES service_public (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO evaluation (id, user_id, service_public_id, uuid, note, commentaire, criteres_specifiques, statut, est_anonyme, est_verifie, nombre_utile, nombre_signalement, pseudo, created_at, updated_at) SELECT id, user_id, service_public_id, uuid, note, commentaire, criteres_specifiques, statut, est_anonyme, est_verifie, nombre_utile, nombre_signalement, pseudo, created_at, updated_at FROM __temp__evaluation');
        $this->addSql('DROP TABLE __temp__evaluation');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1323A575D17F50A6 ON evaluation (uuid)');
        $this->addSql('CREATE INDEX IDX_1323A575A76ED395 ON evaluation (user_id)');
        $this->addSql('CREATE INDEX IDX_1323A575ED25B1DB ON evaluation (service_public_id)');
        $this->addSql('CREATE INDEX idx_service_note ON evaluation (service_public_id, note)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__messenger_messages AS SELECT id, body, headers, queue_name, created_at, available_at, delivered_at FROM messenger_messages');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('CREATE TABLE messenger_messages (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, body CLOB NOT NULL, headers CLOB NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , available_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , delivered_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('INSERT INTO messenger_messages (id, body, headers, queue_name, created_at, available_at, delivered_at) SELECT id, body, headers, queue_name, created_at, available_at, delivered_at FROM __temp__messenger_messages');
        $this->addSql('DROP TABLE __temp__messenger_messages');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__service_public AS SELECT id, categorie_id, id_gouv, id_externe, nom, description, adresse_complete, code_postal, ville, latitude, longitude, telephone, email, site_web, horaires_ouverture, accessibilite_pmr, statut, source_donnees, slug, created_at, updated_at FROM service_public');
        $this->addSql('DROP TABLE service_public');
        $this->addSql('CREATE TABLE service_public (id BLOB NOT NULL --(DC2Type:uuid)
        , categorie_id BLOB DEFAULT NULL --(DC2Type:uuid)
        , id_gouv VARCHAR(255) DEFAULT NULL, id_externe VARCHAR(255) DEFAULT NULL, nom VARCHAR(200) NOT NULL, description CLOB DEFAULT NULL, adresse_complete CLOB DEFAULT NULL, code_postal VARCHAR(5) NOT NULL, ville VARCHAR(100) NOT NULL, latitude NUMERIC(10, 8) DEFAULT NULL, longitude NUMERIC(11, 8) DEFAULT NULL, telephone VARCHAR(20) DEFAULT NULL, email VARCHAR(180) DEFAULT NULL, site_web VARCHAR(255) DEFAULT NULL, horaires_ouverture CLOB DEFAULT NULL --(DC2Type:json)
        , accessibilite_pmr BOOLEAN NOT NULL, statut VARCHAR(20) NOT NULL, source_donnees VARCHAR(100) DEFAULT NULL, slug VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , PRIMARY KEY(id), CONSTRAINT FK_5732B2BBCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie_service (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO service_public (id, categorie_id, id_gouv, id_externe, nom, description, adresse_complete, code_postal, ville, latitude, longitude, telephone, email, site_web, horaires_ouverture, accessibilite_pmr, statut, source_donnees, slug, created_at, updated_at) SELECT id, categorie_id, id_gouv, id_externe, nom, description, adresse_complete, code_postal, ville, latitude, longitude, telephone, email, site_web, horaires_ouverture, accessibilite_pmr, statut, source_donnees, slug, created_at, updated_at FROM __temp__service_public');
        $this->addSql('DROP TABLE __temp__service_public');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5732B2BA430D903 ON service_public (id_gouv)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5732B2B989D9B62 ON service_public (slug)');
        $this->addSql('CREATE INDEX IDX_5732B2BBCF5E72D ON service_public (categorie_id)');
        $this->addSql('CREATE INDEX idx_localisation ON service_public (ville, code_postal)');
        $this->addSql('CREATE INDEX idx_categorie_statut ON service_public (categorie_id, statut)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, email, roles, password, pseudo, prenom, nom, date_naissance, code_postal, ville, telephone, is_verified, verified_at, accepte_newsletters, score_fiabilite, statut, derniere_connexion, created_at, updated_at FROM user');
        $this->addSql('DROP TABLE user');
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , password VARCHAR(255) NOT NULL, pseudo VARCHAR(50) DEFAULT NULL, prenom VARCHAR(255) DEFAULT NULL, nom VARCHAR(255) DEFAULT NULL, date_naissance DATE DEFAULT NULL, code_postal VARCHAR(5) DEFAULT NULL, ville VARCHAR(100) DEFAULT NULL, telephone VARCHAR(20) DEFAULT NULL, is_verified BOOLEAN NOT NULL, verified_at DATETIME DEFAULT NULL, accepte_newsletters BOOLEAN NOT NULL, score_fiabilite INTEGER NOT NULL, statut VARCHAR(255) NOT NULL, derniere_connexion DATETIME DEFAULT NULL, created_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('INSERT INTO user (id, email, roles, password, pseudo, prenom, nom, date_naissance, code_postal, ville, telephone, is_verified, verified_at, accepte_newsletters, score_fiabilite, statut, derniere_connexion, created_at, updated_at) SELECT id, email, roles, password, pseudo, prenom, nom, date_naissance, code_postal, ville, telephone, is_verified, verified_at, accepte_newsletters, score_fiabilite, statut, derniere_connexion, created_at, updated_at FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON user (email)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_PSEUDO ON user (pseudo)');
    }
}
