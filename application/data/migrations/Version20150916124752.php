<?php

namespace iCoordinator\Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150916124752 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE license_chargify_mappers (id INT AUTO_INCREMENT NOT NULL, license_id INT DEFAULT NULL, chargify_website_id VARCHAR(255) NOT NULL, chargify_product_handle VARCHAR(255) NOT NULL, chargify_users_component_ids VARCHAR(255) NOT NULL, chargify_workspaces_component_ids VARCHAR(255) DEFAULT NULL, chargify_storage_component_ids VARCHAR(255) DEFAULT NULL, INDEX IDX_A79380CA460F904B (license_id), UNIQUE INDEX website_product_unique_idx (chargify_website_id, chargify_product_handle), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE licenses (id INT AUTO_INCREMENT NOT NULL, users_limit INT DEFAULT NULL, workspaces_limit INT DEFAULT NULL, storage_limit INT DEFAULT NULL, file_size_limit INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE subscription_chargify_mappers (id INT AUTO_INCREMENT NOT NULL, subscription_id INT DEFAULT NULL, chargify_subscription_id INT NOT NULL, UNIQUE INDEX UNIQ_932E270921F1DB44 (chargify_subscription_id), UNIQUE INDEX UNIQ_932E27099A1887DC (subscription_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE subscriptions (id INT AUTO_INCREMENT NOT NULL, portal_id INT DEFAULT NULL, license_id INT DEFAULT NULL, users_allocation INT NOT NULL, workspaces_allocation INT NOT NULL, storage_allocation INT NOT NULL, state VARCHAR(20) NOT NULL, UNIQUE INDEX UNIQ_4778A01B887E1DD (portal_id), UNIQUE INDEX UNIQ_4778A01460F904B (license_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE license_chargify_mappers ADD CONSTRAINT FK_A79380CA460F904B FOREIGN KEY (license_id) REFERENCES licenses (id)');
        $this->addSql('ALTER TABLE subscription_chargify_mappers ADD CONSTRAINT FK_932E27099A1887DC FOREIGN KEY (subscription_id) REFERENCES subscriptions (id)');
        $this->addSql('ALTER TABLE subscriptions ADD CONSTRAINT FK_4778A01B887E1DD FOREIGN KEY (portal_id) REFERENCES portals (id)');
        $this->addSql('ALTER TABLE subscriptions ADD CONSTRAINT FK_4778A01460F904B FOREIGN KEY (license_id) REFERENCES licenses (id)');
        $this->addSql('ALTER TABLE portals ADD uuid CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A6B89384D17F50A6 ON portals (uuid)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE license_chargify_mappers DROP FOREIGN KEY FK_A79380CA460F904B');
        $this->addSql('ALTER TABLE subscriptions DROP FOREIGN KEY FK_4778A01460F904B');
        $this->addSql('ALTER TABLE subscription_chargify_mappers DROP FOREIGN KEY FK_932E27099A1887DC');
        $this->addSql('DROP TABLE license_chargify_mappers');
        $this->addSql('DROP TABLE licenses');
        $this->addSql('DROP TABLE subscription_chargify_mappers');
        $this->addSql('DROP TABLE subscriptions');
        $this->addSql('DROP INDEX UNIQ_A6B89384D17F50A6 ON portals');
        $this->addSql('ALTER TABLE portals DROP uuid');
    }
}
