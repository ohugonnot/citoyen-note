<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250808172758 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE categorie_service (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', nom VARCHAR(100) NOT NULL, actif TINYINT(1) NOT NULL, ordre_affichage INT NOT NULL, slug VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, icone VARCHAR(50) DEFAULT NULL, couleur VARCHAR(7) DEFAULT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_BE1E34706C6E55B5 (nom), UNIQUE INDEX UNIQ_BE1E3470989D9B62 (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE evaluation (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, service_public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', uuid BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', note SMALLINT NOT NULL, commentaire LONGTEXT DEFAULT NULL, criteres_specifiques JSON DEFAULT NULL, statut VARCHAR(20) NOT NULL, est_anonyme TINYINT(1) NOT NULL, est_verifie TINYINT(1) NOT NULL, nombre_utile INT NOT NULL, nombre_signalement INT NOT NULL, pseudo VARCHAR(255) DEFAULT NULL, ip VARCHAR(45) DEFAULT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_1323A575D17F50A6 (uuid), INDEX IDX_1323A575A76ED395 (user_id), INDEX IDX_1323A575ED25B1DB (service_public_id), INDEX idx_service_note (service_public_id, note), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE refresh_tokens (id INT AUTO_INCREMENT NOT NULL, refresh_token VARCHAR(128) NOT NULL, username VARCHAR(255) NOT NULL, valid DATETIME NOT NULL, UNIQUE INDEX UNIQ_9BACE7E1C74F2195 (refresh_token), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE service_public (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', categorie_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', id_gouv VARCHAR(255) DEFAULT NULL, id_externe VARCHAR(255) DEFAULT NULL, score DOUBLE PRECISION DEFAULT NULL, nom VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, adresse_complete LONGTEXT DEFAULT NULL, code_postal VARCHAR(16) NOT NULL, ville VARCHAR(100) NOT NULL, latitude NUMERIC(10, 8) DEFAULT NULL, longitude NUMERIC(11, 8) DEFAULT NULL, telephone VARCHAR(250) DEFAULT NULL, email VARCHAR(180) DEFAULT NULL, site_web LONGTEXT DEFAULT NULL, horaires_ouverture JSON DEFAULT NULL, accessibilite_pmr TINYINT(1) NOT NULL, statut VARCHAR(20) NOT NULL, source_donnees VARCHAR(100) DEFAULT NULL, slug VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_5732B2BA430D903 (id_gouv), UNIQUE INDEX UNIQ_5732B2B989D9B62 (slug), INDEX IDX_5732B2BBCF5E72D (categorie_id), INDEX idx_localisation (ville, code_postal), INDEX idx_categorie_statut (categorie_id, statut), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, pseudo VARCHAR(50) DEFAULT NULL, prenom VARCHAR(255) DEFAULT NULL, nom VARCHAR(255) DEFAULT NULL, date_naissance DATE DEFAULT NULL, code_postal VARCHAR(5) DEFAULT NULL, ville VARCHAR(100) DEFAULT NULL, telephone VARCHAR(20) DEFAULT NULL, is_verified TINYINT(1) NOT NULL, verified_at DATETIME DEFAULT NULL, accepte_newsletters TINYINT(1) NOT NULL, score_fiabilite INT NOT NULL, statut VARCHAR(255) NOT NULL, derniere_connexion DATETIME DEFAULT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), UNIQUE INDEX UNIQ_IDENTIFIER_PSEUDO (pseudo), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE evaluation ADD CONSTRAINT FK_1323A575A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE evaluation ADD CONSTRAINT FK_1323A575ED25B1DB FOREIGN KEY (service_public_id) REFERENCES service_public (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE service_public ADD CONSTRAINT FK_5732B2BBCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie_service (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE evaluation DROP FOREIGN KEY FK_1323A575A76ED395');
        $this->addSql('ALTER TABLE evaluation DROP FOREIGN KEY FK_1323A575ED25B1DB');
        $this->addSql('ALTER TABLE service_public DROP FOREIGN KEY FK_5732B2BBCF5E72D');
        $this->addSql('DROP TABLE categorie_service');
        $this->addSql('DROP TABLE evaluation');
        $this->addSql('DROP TABLE refresh_tokens');
        $this->addSql('DROP TABLE service_public');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
