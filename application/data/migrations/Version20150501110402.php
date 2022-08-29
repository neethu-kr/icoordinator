<?php

namespace iCoordinator\Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150501110402 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE shared_links ADD created_by INT DEFAULT NULL');
        $this->addSql('ALTER TABLE shared_links ADD CONSTRAINT FK_2FEC4C05DE12AB56 FOREIGN KEY (created_by) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_2FEC4C05DE12AB56 ON shared_links (created_by)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE shared_links DROP FOREIGN KEY FK_2FEC4C05DE12AB56');
        $this->addSql('DROP INDEX IDX_2FEC4C05DE12AB56 ON shared_links');
        $this->addSql('ALTER TABLE shared_links DROP created_by');
    }
}
