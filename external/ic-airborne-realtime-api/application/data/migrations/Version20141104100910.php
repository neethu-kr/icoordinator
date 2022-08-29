<?php

namespace iCoordinator\Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141104100910 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('CREATE TABLE meta_fields (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(100) NOT NULL, options LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, modified_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_6CBD4C6E5E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE meta_fields_values (id INT AUTO_INCREMENT NOT NULL, meta_field_id INT DEFAULT NULL, file_id INT DEFAULT NULL, value LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, modified_at DATETIME NOT NULL, INDEX IDX_F920D2D4A7AFFFB (meta_field_id), INDEX IDX_F920D2D493CB796C (file_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE meta_fields_values ADD CONSTRAINT FK_F920D2D4A7AFFFB FOREIGN KEY (meta_field_id) REFERENCES meta_fields (id)');
        $this->addSql('ALTER TABLE meta_fields_values ADD CONSTRAINT FK_F920D2D493CB796C FOREIGN KEY (file_id) REFERENCES files (id)');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('ALTER TABLE meta_fields_values DROP FOREIGN KEY FK_F920D2D4A7AFFFB');
        $this->addSql('DROP TABLE meta_fields');
        $this->addSql('DROP TABLE meta_fields_values');
    }
}
