<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250726151749 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD COLUMN created_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD COLUMN updated_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, email, roles, password, pseudo, prenom, nom, date_naissance, telephone, is_verified, accepte_newsletters, score_fiabilite, statut FROM user');
        $this->addSql('DROP TABLE user');
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , password VARCHAR(255) NOT NULL, pseudo VARCHAR(50) DEFAULT NULL, prenom VARCHAR(255) DEFAULT NULL, nom VARCHAR(255) DEFAULT NULL, date_naissance DATE DEFAULT NULL, telephone VARCHAR(255) DEFAULT NULL, is_verified BOOLEAN NOT NULL, accepte_newsletters BOOLEAN NOT NULL, score_fiabilite INTEGER NOT NULL, statut VARCHAR(255) NOT NULL)');
        $this->addSql('INSERT INTO user (id, email, roles, password, pseudo, prenom, nom, date_naissance, telephone, is_verified, accepte_newsletters, score_fiabilite, statut) SELECT id, email, roles, password, pseudo, prenom, nom, date_naissance, telephone, is_verified, accepte_newsletters, score_fiabilite, statut FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON user (email)');
    }
}
