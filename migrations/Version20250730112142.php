<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250730112142 extends AbstractMigration
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
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BE1E3470989D9B62 ON categorie_service (slug)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BE1E34706C6E55B5 ON categorie_service (nom)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__categorie_service AS SELECT id, nom, actif, ordre_affichage, slug, description, icone, couleur, created_at, updated_at FROM categorie_service');
        $this->addSql('DROP TABLE categorie_service');
        $this->addSql('CREATE TABLE categorie_service (id BLOB NOT NULL --(DC2Type:uuid)
        , nom VARCHAR(100) NOT NULL, actif BOOLEAN NOT NULL, ordre_affichage INTEGER NOT NULL, slug VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, icone VARCHAR(50) DEFAULT NULL, couleur VARCHAR(7) DEFAULT NULL, created_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , PRIMARY KEY(id))');
        $this->addSql('INSERT INTO categorie_service (id, nom, actif, ordre_affichage, slug, description, icone, couleur, created_at, updated_at) SELECT id, nom, actif, ordre_affichage, slug, description, icone, couleur, created_at, updated_at FROM __temp__categorie_service');
        $this->addSql('DROP TABLE __temp__categorie_service');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BE1E3470989D9B62 ON categorie_service (slug)');
    }
}
