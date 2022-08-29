<?php

namespace iCoordinator\Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150417213414 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE locks DROP FOREIGN KEY FK_FC316D978C9F3610');
        $this->addSql('DROP INDEX IDX_FC316D978C9F3610 ON locks');
        $this->addSql('ALTER TABLE locks CHANGE file file_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE locks ADD CONSTRAINT FK_FC316D9793CB796C FOREIGN KEY (file_id) REFERENCES files (id)');
        $this->addSql('CREATE INDEX IDX_FC316D9793CB796C ON locks (file_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE locks DROP FOREIGN KEY FK_FC316D9793CB796C');
        $this->addSql('DROP INDEX IDX_FC316D9793CB796C ON locks');
        $this->addSql('ALTER TABLE locks CHANGE file_id file INT DEFAULT NULL');
        $this->addSql('ALTER TABLE locks ADD CONSTRAINT FK_FC316D978C9F3610 FOREIGN KEY (file) REFERENCES files (id)');
        $this->addSql('CREATE INDEX IDX_FC316D978C9F3610 ON locks (file)');
    }
}
