<?php

namespace iCoordinator\Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150422163232 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE file_versions (id INT AUTO_INCREMENT NOT NULL, file_id INT DEFAULT NULL, modified_by INT DEFAULT NULL, name VARCHAR(255) NOT NULL, size INT NOT NULL, storage_path VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, modified_at DATETIME NOT NULL, comment LONGTEXT NOT NULL, INDEX IDX_A88CCF4F93CB796C (file_id), INDEX IDX_A88CCF4F25F94802 (modified_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE file_versions ADD CONSTRAINT FK_A88CCF4F93CB796C FOREIGN KEY (file_id) REFERENCES files (id)');
        $this->addSql('ALTER TABLE file_versions ADD CONSTRAINT FK_A88CCF4F25F94802 FOREIGN KEY (modified_by) REFERENCES users (id)');
        $this->addSql('ALTER TABLE files DROP storage_path, CHANGE version etag INT NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE file_versions');
        $this->addSql('ALTER TABLE files ADD storage_path VARCHAR(255) DEFAULT NULL COLLATE utf8_general_ci, CHANGE etag version INT NOT NULL');
    }
}
