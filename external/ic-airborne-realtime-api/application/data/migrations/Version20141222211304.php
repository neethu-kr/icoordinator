<?php

namespace iCoordinator\Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141222211304 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('ALTER TABLE files ADD owned_by INT DEFAULT NULL');
        $this->addSql('ALTER TABLE files ADD CONSTRAINT FK_63540598BBCDCA8 FOREIGN KEY (owned_by) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_63540598BBCDCA8 ON files (owned_by)');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('ALTER TABLE files DROP FOREIGN KEY FK_63540598BBCDCA8');
        $this->addSql('DROP INDEX IDX_63540598BBCDCA8 ON files');
        $this->addSql('ALTER TABLE files DROP owned_by');
    }
}
