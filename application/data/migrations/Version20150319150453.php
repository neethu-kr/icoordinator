<?php

namespace iCoordinator\Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150319150453 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE portals ADD owned_by INT DEFAULT NULL, ADD name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE portals ADD CONSTRAINT FK_A6B893848BBCDCA8 FOREIGN KEY (owned_by) REFERENCES users (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A6B893845E237E06 ON portals (name)');
        $this->addSql('CREATE INDEX IDX_A6B893848BBCDCA8 ON portals (owned_by)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE portals DROP FOREIGN KEY FK_A6B893848BBCDCA8');
        $this->addSql('DROP INDEX UNIQ_A6B893845E237E06 ON portals');
        $this->addSql('DROP INDEX IDX_A6B893848BBCDCA8 ON portals');
        $this->addSql('ALTER TABLE portals DROP owned_by, DROP name');
    }
}
