<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250801124146 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE evaluation ADD COLUMN pseudo VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__evaluation AS SELECT id, user_id, service_public_id, uuid, note, commentaire, criteres_specifiques, statut, est_anonyme, est_verifie, nombre_utile, nombre_signalement, created_at, updated_at FROM evaluation');
        $this->addSql('DROP TABLE evaluation');
        $this->addSql('CREATE TABLE evaluation (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER DEFAULT NULL, service_public_id BLOB NOT NULL --(DC2Type:uuid)
        , uuid BLOB NOT NULL --(DC2Type:uuid)
        , note SMALLINT NOT NULL, commentaire CLOB DEFAULT NULL, criteres_specifiques CLOB DEFAULT NULL --(DC2Type:json)
        , statut VARCHAR(20) NOT NULL, est_anonyme BOOLEAN NOT NULL, est_verifie BOOLEAN NOT NULL, nombre_utile INTEGER NOT NULL, nombre_signalement INTEGER NOT NULL, created_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_1323A575A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_1323A575ED25B1DB FOREIGN KEY (service_public_id) REFERENCES service_public (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO evaluation (id, user_id, service_public_id, uuid, note, commentaire, criteres_specifiques, statut, est_anonyme, est_verifie, nombre_utile, nombre_signalement, created_at, updated_at) SELECT id, user_id, service_public_id, uuid, note, commentaire, criteres_specifiques, statut, est_anonyme, est_verifie, nombre_utile, nombre_signalement, created_at, updated_at FROM __temp__evaluation');
        $this->addSql('DROP TABLE __temp__evaluation');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1323A575D17F50A6 ON evaluation (uuid)');
        $this->addSql('CREATE INDEX IDX_1323A575A76ED395 ON evaluation (user_id)');
        $this->addSql('CREATE INDEX IDX_1323A575ED25B1DB ON evaluation (service_public_id)');
        $this->addSql('CREATE INDEX idx_service_note ON evaluation (service_public_id, note)');
    }
}
