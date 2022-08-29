<?php

namespace iCoordinator\Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150811151612 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('LOCK TABLES acl_permissions WRITE, portals WRITE');
        $this->addSql('ALTER TABLE acl_permissions DROP FOREIGN KEY FK_4066EC45B887E1DD');
        $this->addSql('ALTER TABLE acl_permissions CHANGE portal_id portal_id INT NOT NULL');
        $this->addSql('ALTER TABLE acl_permissions ADD CONSTRAINT FK_4066EC45B887E1DD FOREIGN KEY (portal_id) REFERENCES portals (id)');
        $this->addSql('UNLOCK TABLES');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('LOCK TABLES acl_permissions WRITE, portals WRITE');
        $this->addSql('ALTER TABLE acl_permissions DROP FOREIGN KEY FK_4066EC45B887E1DD');
        $this->addSql('ALTER TABLE acl_permissions CHANGE portal_id portal_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE acl_permissions ADD CONSTRAINT FK_4066EC45B887E1DD FOREIGN KEY (portal_id) REFERENCES portals (id)');
    }
}
