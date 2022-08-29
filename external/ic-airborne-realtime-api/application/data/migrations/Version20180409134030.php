<?php

namespace iCoordinator\Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180409134030 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE fdv_entries (id INT AUTO_INCREMENT NOT NULL, portal_id INT DEFAULT NULL, workspace_id INT DEFAULT NULL, created_by INT DEFAULT NULL, selskapsnr VARCHAR(15) DEFAULT NULL, selskapsnavn VARCHAR(100) DEFAULT NULL, gnrbnr VARCHAR(15) DEFAULT NULL, eiendomnavn VARCHAR(100) DEFAULT NULL, bygningsnr VARCHAR(15) DEFAULT NULL, bygning VARCHAR(100) DEFAULT NULL, bygningsdel VARCHAR(15) DEFAULT NULL, systemnavn VARCHAR(100) DEFAULT NULL, systemtypenr VARCHAR(15) DEFAULT NULL, komponentnr VARCHAR(15) DEFAULT NULL, komponentnavn VARCHAR(100) DEFAULT NULL, komponenttypenr VARCHAR(15) DEFAULT NULL, komponentkategorinr VARCHAR(15) DEFAULT NULL, fabrikat VARCHAR(100) DEFAULT NULL, typebetegnelse VARCHAR(50) DEFAULT NULL, systemleverandor VARCHAR(100) DEFAULT NULL, installdato DATE NOT NULL, notat VARCHAR(200) DEFAULT NULL, garanti DATE NOT NULL, antal_service_per_ar INT DEFAULT NULL, tfm VARCHAR(100) DEFAULT NULL, INDEX IDX_B4C17E13B887E1DD (portal_id), INDEX IDX_B4C17E1382D40A1F (workspace_id), INDEX IDX_B4C17E13DE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE fdv_entries ADD CONSTRAINT FK_B4C17E13B887E1DD FOREIGN KEY (portal_id) REFERENCES portals (id)');
        $this->addSql('ALTER TABLE fdv_entries ADD CONSTRAINT FK_B4C17E1382D40A1F FOREIGN KEY (workspace_id) REFERENCES workspaces (id)');
        $this->addSql('ALTER TABLE fdv_entries ADD CONSTRAINT FK_B4C17E13DE12AB56 FOREIGN KEY (created_by) REFERENCES users (id)');
        $this->addSql('CREATE TABLE fdv_licenses (id INT AUTO_INCREMENT NOT NULL, portal_id INT DEFAULT NULL, INDEX IDX_101B4113B887E1DD (portal_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE fdv_licenses ADD CONSTRAINT FK_101B4113B887E1DD FOREIGN KEY (portal_id) REFERENCES portals (id)');
    }
    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE fdv_entries');
        $this->addSql('DROP TABLE fdv_licenses');
    }
}
