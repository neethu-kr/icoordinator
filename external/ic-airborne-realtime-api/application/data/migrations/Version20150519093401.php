<?php

namespace iCoordinator\Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150519093401 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE acl_permissions (id INT AUTO_INCREMENT NOT NULL, portal_id INT DEFAULT NULL, acl_role_id INT DEFAULT NULL, acl_resource_id INT DEFAULT NULL, granted_by INT DEFAULT NULL, bit_mask INT NOT NULL, created_at DATETIME NOT NULL, modified_at DATETIME NOT NULL, INDEX IDX_4066EC45B887E1DD (portal_id), INDEX IDX_4066EC45BD33296F (acl_role_id), INDEX IDX_4066EC45E4B9BD0F (acl_resource_id), INDEX IDX_4066EC45A5FB753F (granted_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE acl_resources (id INT AUTO_INCREMENT NOT NULL, portal_id INT DEFAULT NULL, entity_id INT NOT NULL, created_at DATETIME NOT NULL, modified_at DATETIME NOT NULL, entity_type VARCHAR(10) NOT NULL, resource_id INT DEFAULT NULL, INDEX IDX_9863DD9BB887E1DD (portal_id), INDEX IDX_9863DD9B89329D25 (resource_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE acl_roles (id INT AUTO_INCREMENT NOT NULL, portal_id INT DEFAULT NULL, entity_id INT DEFAULT NULL, created_at DATETIME NOT NULL, modified_at DATETIME NOT NULL, entity_type VARCHAR(10) NOT NULL, role_id INT DEFAULT NULL, INDEX IDX_32A76378B887E1DD (portal_id), INDEX IDX_32A76378D60322AC (role_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE acl_permissions ADD CONSTRAINT FK_4066EC45B887E1DD FOREIGN KEY (portal_id) REFERENCES portals (id)');
        $this->addSql('ALTER TABLE acl_permissions ADD CONSTRAINT FK_4066EC45BD33296F FOREIGN KEY (acl_role_id) REFERENCES acl_roles (id)');
        $this->addSql('ALTER TABLE acl_permissions ADD CONSTRAINT FK_4066EC45E4B9BD0F FOREIGN KEY (acl_resource_id) REFERENCES acl_resources (id)');
        $this->addSql('ALTER TABLE acl_permissions ADD CONSTRAINT FK_4066EC45A5FB753F FOREIGN KEY (granted_by) REFERENCES users (id)');
        $this->addSql('ALTER TABLE acl_resources ADD CONSTRAINT FK_9863DD9BB887E1DD FOREIGN KEY (portal_id) REFERENCES portals (id)');
        $this->addSql('ALTER TABLE acl_roles ADD CONSTRAINT FK_32A76378B887E1DD FOREIGN KEY (portal_id) REFERENCES portals (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE acl_permissions DROP FOREIGN KEY FK_4066EC45E4B9BD0F');
        $this->addSql('ALTER TABLE acl_permissions DROP FOREIGN KEY FK_4066EC45BD33296F');
        $this->addSql('DROP TABLE acl_permissions');
        $this->addSql('DROP TABLE acl_resources');
        $this->addSql('DROP TABLE acl_roles');
    }
}
