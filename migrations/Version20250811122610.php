<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250811122610 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE INDEX idx_service_public_statut ON service_public (statut)');
        $this->addSql('CREATE INDEX idx_sp_statut_nom ON service_public (statut, nom)');
        $this->addSql('CREATE INDEX idx_sp_statut_cat_nom ON service_public (statut, categorie_id, nom)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_service_public_statut ON service_public');
        $this->addSql('DROP INDEX idx_sp_statut_nom ON service_public');
        $this->addSql('DROP INDEX idx_sp_statut_cat_nom ON service_public');
    }
}
