<?php

namespace iCoordinator\Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160527121837 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE event_notifications (id INT AUTO_INCREMENT NOT NULL, portal_id INT DEFAULT NULL, workspace_id INT DEFAULT NULL, user_id INT DEFAULT NULL, created_by INT DEFAULT NULL, source_id INT DEFAULT NULL, type VARCHAR(100) NOT NULL, created_at DATETIME NOT NULL, source_type VARCHAR(100) NOT NULL, INDEX IDX_4459007FB887E1DD (portal_id), INDEX IDX_4459007F82D40A1F (workspace_id), INDEX IDX_4459007FA76ED395 (user_id), INDEX IDX_4459007FDE12AB56 (created_by), INDEX IDX_4459007F953C1C61 (source_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE event_notifications ADD CONSTRAINT FK_4459007FB887E1DD FOREIGN KEY (portal_id) REFERENCES portals (id)');
        $this->addSql('ALTER TABLE event_notifications ADD CONSTRAINT FK_4459007F82D40A1F FOREIGN KEY (workspace_id) REFERENCES workspaces (id)');
        $this->addSql('ALTER TABLE event_notifications ADD CONSTRAINT FK_4459007FA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE event_notifications ADD CONSTRAINT FK_4459007FDE12AB56 FOREIGN KEY (created_by) REFERENCES users (id)');
        $this->addSql('ALTER TABLE event_notifications ADD CONSTRAINT FK_4459007F953C1C61 FOREIGN KEY (source_id) REFERENCES files (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE event_notifications');
    }
}
