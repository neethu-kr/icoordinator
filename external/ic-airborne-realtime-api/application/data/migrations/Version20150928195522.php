<?php

namespace iCoordinator\Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150928195522 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DELETE FROM meta_fields_values WHERE file_id IS NULL');
        $this->addSql('DELETE FROM meta_fields_values WHERE meta_field_id IS NULL');
        $this->addSql('ALTER TABLE meta_fields_values DROP FOREIGN KEY FK_F920D2D4A7AFFFB');
        $this->addSql('ALTER TABLE meta_fields_values DROP FOREIGN KEY FK_F920D2D493CB796C');
        $this->addSql('ALTER TABLE meta_fields_values CHANGE file_id file_id INT NOT NULL, CHANGE meta_field_id meta_field_id INT NOT NULL');
        $this->addSql('ALTER TABLE meta_fields_values ADD CONSTRAINT FK_F920D2D4A7AFFFB FOREIGN KEY (meta_field_id) REFERENCES meta_fields (id)');
        $this->addSql('ALTER TABLE meta_fields_values ADD CONSTRAINT FK_F920D2D493CB796C FOREIGN KEY (file_id) REFERENCES files (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE meta_fields_values DROP FOREIGN KEY FK_F920D2D4A7AFFFB');
        $this->addSql('ALTER TABLE meta_fields_values DROP FOREIGN KEY FK_F920D2D493CB796C');
        $this->addSql('ALTER TABLE meta_fields_values CHANGE meta_field_id meta_field_id INT DEFAULT NULL, CHANGE file_id file_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE meta_fields_values ADD CONSTRAINT FK_F920D2D4A7AFFFB FOREIGN KEY (meta_field_id) REFERENCES meta_fields (id)');
        $this->addSql('ALTER TABLE meta_fields_values ADD CONSTRAINT FK_F920D2D493CB796C FOREIGN KEY (file_id) REFERENCES files (id)');
    }
}
