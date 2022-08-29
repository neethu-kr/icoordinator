<?php

namespace iCoordinator\Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150519123121 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX IDX_9863DD9B89329D25 ON acl_resources');
        $this->addSql('ALTER TABLE acl_resources DROP resource_id');
        $this->addSql('CREATE INDEX IDX_9863DD9B81257D5D ON acl_resources (entity_id)');
        $this->addSql('DROP INDEX IDX_32A76378D60322AC ON acl_roles');
        $this->addSql('ALTER TABLE acl_roles DROP role_id');
        $this->addSql('CREATE INDEX IDX_32A7637881257D5D ON acl_roles (entity_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX IDX_9863DD9B81257D5D ON acl_resources');
        $this->addSql('ALTER TABLE acl_resources ADD resource_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_9863DD9B89329D25 ON acl_resources (resource_id)');
        $this->addSql('DROP INDEX IDX_32A7637881257D5D ON acl_roles');
        $this->addSql('ALTER TABLE acl_roles ADD role_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_32A76378D60322AC ON acl_roles (role_id)');
    }
}
