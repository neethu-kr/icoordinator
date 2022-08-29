<?php

namespace iCoordinator\Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140829141827 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('CREATE TABLE files (id INT AUTO_INCREMENT NOT NULL, workspace_id INT DEFAULT NULL, created_by INT DEFAULT NULL, modified_by INT DEFAULT NULL, parent INT DEFAULT NULL, name VARCHAR(255) NOT NULL, size INT NOT NULL, content_created_at DATETIME NOT NULL, content_modified_at DATETIME NOT NULL, is_deleted TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, modified_at DATETIME NOT NULL, version INT NOT NULL, type VARCHAR(100) NOT NULL, INDEX IDX_635405982D40A1F (workspace_id), INDEX IDX_6354059DE12AB56 (created_by), INDEX IDX_635405925F94802 (modified_by), INDEX IDX_63540593D8E604F (parent), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE files ADD CONSTRAINT FK_635405982D40A1F FOREIGN KEY (workspace_id) REFERENCES workspaces (id)');
        $this->addSql('ALTER TABLE files ADD CONSTRAINT FK_6354059DE12AB56 FOREIGN KEY (created_by) REFERENCES users (id)');
        $this->addSql('ALTER TABLE files ADD CONSTRAINT FK_635405925F94802 FOREIGN KEY (modified_by) REFERENCES users (id)');
        $this->addSql('ALTER TABLE files ADD CONSTRAINT FK_63540593D8E604F FOREIGN KEY (parent) REFERENCES files (id)');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('ALTER TABLE files DROP FOREIGN KEY FK_63540593D8E604F');
        $this->addSql('DROP TABLE files');
    }
}
