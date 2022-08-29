<?php

namespace iCoordinator\Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150317132626 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE portals (id INT AUTO_INCREMENT NOT NULL, domain_part VARCHAR(100) NOT NULL, created_at DATETIME NOT NULL, modified_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_A6B89384E4D35DA1 (domain_part), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE events ADD portal_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE events ADD CONSTRAINT FK_5387574AB887E1DD FOREIGN KEY (portal_id) REFERENCES portals (id)');
        $this->addSql('CREATE INDEX IDX_5387574AB887E1DD ON events (portal_id)');
        $this->addSql('ALTER TABLE files ADD portal_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE files ADD CONSTRAINT FK_6354059B887E1DD FOREIGN KEY (portal_id) REFERENCES portals (id)');
        $this->addSql('CREATE INDEX IDX_6354059B887E1DD ON files (portal_id)');
        $this->addSql('ALTER TABLE meta_fields ADD portal_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE meta_fields ADD CONSTRAINT FK_6CBD4C6EB887E1DD FOREIGN KEY (portal_id) REFERENCES portals (id)');
        $this->addSql('CREATE INDEX IDX_6CBD4C6EB887E1DD ON meta_fields (portal_id)');
        $this->addSql('ALTER TABLE meta_fields_criteria ADD portal_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE meta_fields_criteria ADD CONSTRAINT FK_4CD8B234B887E1DD FOREIGN KEY (portal_id) REFERENCES portals (id)');
        $this->addSql('CREATE INDEX IDX_4CD8B234B887E1DD ON meta_fields_criteria (portal_id)');
        $this->addSql('ALTER TABLE meta_fields_values ADD portal_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE meta_fields_values ADD CONSTRAINT FK_F920D2D4B887E1DD FOREIGN KEY (portal_id) REFERENCES portals (id)');
        $this->addSql('CREATE INDEX IDX_F920D2D4B887E1DD ON meta_fields_values (portal_id)');
        $this->addSql('ALTER TABLE permissions ADD portal_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE permissions ADD CONSTRAINT FK_2DEDCC6FB887E1DD FOREIGN KEY (portal_id) REFERENCES portals (id)');
        $this->addSql('CREATE INDEX IDX_2DEDCC6FB887E1DD ON permissions (portal_id)');
        $this->addSql('ALTER TABLE shared_links ADD portal_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE shared_links ADD CONSTRAINT FK_2FEC4C05B887E1DD FOREIGN KEY (portal_id) REFERENCES portals (id)');
        $this->addSql('CREATE INDEX IDX_2FEC4C05B887E1DD ON shared_links (portal_id)');
        $this->addSql('ALTER TABLE workspaces ADD portal_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE workspaces ADD CONSTRAINT FK_7FE8F3CBB887E1DD FOREIGN KEY (portal_id) REFERENCES portals (id)');
        $this->addSql('CREATE INDEX IDX_7FE8F3CBB887E1DD ON workspaces (portal_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE events DROP FOREIGN KEY FK_5387574AB887E1DD');
        $this->addSql('ALTER TABLE files DROP FOREIGN KEY FK_6354059B887E1DD');
        $this->addSql('ALTER TABLE meta_fields DROP FOREIGN KEY FK_6CBD4C6EB887E1DD');
        $this->addSql('ALTER TABLE meta_fields_criteria DROP FOREIGN KEY FK_4CD8B234B887E1DD');
        $this->addSql('ALTER TABLE meta_fields_values DROP FOREIGN KEY FK_F920D2D4B887E1DD');
        $this->addSql('ALTER TABLE permissions DROP FOREIGN KEY FK_2DEDCC6FB887E1DD');
        $this->addSql('ALTER TABLE shared_links DROP FOREIGN KEY FK_2FEC4C05B887E1DD');
        $this->addSql('ALTER TABLE workspaces DROP FOREIGN KEY FK_7FE8F3CBB887E1DD');
        $this->addSql('DROP TABLE portals');
        $this->addSql('DROP INDEX IDX_5387574AB887E1DD ON events');
        $this->addSql('ALTER TABLE events DROP portal_id');
        $this->addSql('DROP INDEX IDX_6354059B887E1DD ON files');
        $this->addSql('ALTER TABLE files DROP portal_id');
        $this->addSql('DROP INDEX IDX_6CBD4C6EB887E1DD ON meta_fields');
        $this->addSql('ALTER TABLE meta_fields DROP portal_id');
        $this->addSql('DROP INDEX IDX_4CD8B234B887E1DD ON meta_fields_criteria');
        $this->addSql('ALTER TABLE meta_fields_criteria DROP portal_id');
        $this->addSql('DROP INDEX IDX_F920D2D4B887E1DD ON meta_fields_values');
        $this->addSql('ALTER TABLE meta_fields_values DROP portal_id');
        $this->addSql('DROP INDEX IDX_2DEDCC6FB887E1DD ON permissions');
        $this->addSql('ALTER TABLE permissions DROP portal_id');
        $this->addSql('DROP INDEX IDX_2FEC4C05B887E1DD ON shared_links');
        $this->addSql('ALTER TABLE shared_links DROP portal_id');
        $this->addSql('DROP INDEX IDX_7FE8F3CBB887E1DD ON workspaces');
        $this->addSql('ALTER TABLE workspaces DROP portal_id');
    }
}
