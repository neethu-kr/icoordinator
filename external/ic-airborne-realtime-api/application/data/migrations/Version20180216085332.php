<?php

namespace iCoordinator\Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180216085332 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE history_events (id INT AUTO_INCREMENT NOT NULL, portal_id INT DEFAULT NULL, workspace_id INT DEFAULT NULL, group_user INT DEFAULT NULL, created_by INT DEFAULT NULL, type VARCHAR(100) NOT NULL, created_at DATETIME NOT NULL, description VARCHAR(100) DEFAULT NULL, client_id VARCHAR(100) DEFAULT NULL, source_type VARCHAR(100) NOT NULL, source_id INT DEFAULT NULL, INDEX IDX_80081BB9B887E1DD (portal_id), INDEX IDX_80081BB982D40A1F (workspace_id), INDEX IDX_80081BB9A4C98D39 (group_user), INDEX IDX_80081BB9DE12AB56 (created_by), INDEX IDX_80081BB9953C1C61 (source_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE history_events ADD CONSTRAINT FK_80081BB9B887E1DD FOREIGN KEY (portal_id) REFERENCES portals (id)');
        $this->addSql('ALTER TABLE history_events ADD CONSTRAINT FK_80081BB982D40A1F FOREIGN KEY (workspace_id) REFERENCES workspaces (id)');
        $this->addSql('ALTER TABLE history_events ADD CONSTRAINT FK_80081BB9A4C98D39 FOREIGN KEY (group_user) REFERENCES users (id)');
        $this->addSql('ALTER TABLE history_events ADD CONSTRAINT FK_80081BB9DE12AB56 FOREIGN KEY (created_by) REFERENCES users (id)');
  }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('DROP TABLE history_events');
    }
}
