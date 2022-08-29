<?php

namespace iCoordinator\Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150521141032 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE acl_resources DROP FOREIGN KEY FK_9863DD9BB887E1DD');
        $this->addSql('DROP INDEX IDX_9863DD9BB887E1DD ON acl_resources');
        $this->addSql('ALTER TABLE acl_resources DROP portal_id');
        $this->addSql('ALTER TABLE acl_roles DROP FOREIGN KEY FK_32A76378B887E1DD');
        $this->addSql('DROP INDEX IDX_32A76378B887E1DD ON acl_roles');
        $this->addSql('ALTER TABLE acl_roles DROP portal_id');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE acl_resources ADD portal_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE acl_resources ADD CONSTRAINT FK_9863DD9BB887E1DD FOREIGN KEY (portal_id) REFERENCES portals (id)');
        $this->addSql('CREATE INDEX IDX_9863DD9BB887E1DD ON acl_resources (portal_id)');
        $this->addSql('ALTER TABLE acl_roles ADD portal_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE acl_roles ADD CONSTRAINT FK_32A76378B887E1DD FOREIGN KEY (portal_id) REFERENCES portals (id)');
        $this->addSql('CREATE INDEX IDX_32A76378B887E1DD ON acl_roles (portal_id)');
    }
}
