<?php

namespace iCoordinator\Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140730110258 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE permissions ADD user_id INT DEFAULT NULL");
        $this->addSql("ALTER TABLE permissions ADD CONSTRAINT FK_2DEDCC6FA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)");
        $this->addSql("CREATE INDEX IDX_2DEDCC6FA76ED395 ON permissions (user_id)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE permissions DROP FOREIGN KEY FK_2DEDCC6FA76ED395");
        $this->addSql("DROP INDEX IDX_2DEDCC6FA76ED395 ON permissions");
        $this->addSql("ALTER TABLE permissions DROP user_id");
    }
}
