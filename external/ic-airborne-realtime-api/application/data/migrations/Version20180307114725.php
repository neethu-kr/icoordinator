<?php

namespace iCoordinator\Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180307114725 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE portal_allowed_clients (id INT AUTO_INCREMENT NOT NULL, portal_id INT NOT NULL, user_id INT NOT NULL, uuid VARCHAR(100) DEFAULT NULL, desktop TINYINT(1) NOT NULL, mobile TINYINT(1) NOT NULL, INDEX IDX_33D69A2DB887E1DD (portal_id), INDEX IDX_33D69A2DA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE portal_allowed_clients ADD CONSTRAINT FK_33D69A2DB887E1DD FOREIGN KEY (portal_id) REFERENCES portals (id)');
        $this->addSql('ALTER TABLE portal_allowed_clients ADD CONSTRAINT FK_33D69A2DA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE portal_allowed_clients');
    }
}
