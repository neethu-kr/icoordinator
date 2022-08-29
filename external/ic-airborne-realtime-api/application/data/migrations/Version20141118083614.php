<?php

namespace iCoordinator\Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141118083614 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('CREATE TABLE meta_fields_criteria (id INT AUTO_INCREMENT NOT NULL, meta_field_id INT DEFAULT NULL, smart_folder_id INT DEFAULT NULL, `condition` VARCHAR(30) NOT NULL, value LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, modified_at DATETIME NOT NULL, INDEX IDX_4CD8B234A7AFFFB (meta_field_id), INDEX IDX_4CD8B234160001A8 (smart_folder_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE meta_fields_criteria ADD CONSTRAINT FK_4CD8B234A7AFFFB FOREIGN KEY (meta_field_id) REFERENCES meta_fields (id)');
        $this->addSql('ALTER TABLE meta_fields_criteria ADD CONSTRAINT FK_4CD8B234160001A8 FOREIGN KEY (smart_folder_id) REFERENCES files (id)');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('DROP TABLE meta_fields_criteria');
    }
}
