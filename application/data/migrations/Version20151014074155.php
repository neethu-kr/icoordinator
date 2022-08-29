<?php

namespace iCoordinator\Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151014074155 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE download_tokens ADD token VARCHAR(32) NOT NULL, DROP ip_address, DROP download_token');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_68A294A55F37A13B ON download_tokens (token)');
        $this->addSql('ALTER TABLE invitations CHANGE token token VARCHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE shared_links CHANGE token token VARCHAR(32) NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX UNIQ_68A294A55F37A13B ON download_tokens');
        $this->addSql('ALTER TABLE download_tokens ADD ip_address VARCHAR(40) NOT NULL COLLATE utf8_general_ci, ADD download_token VARCHAR(40) NOT NULL COLLATE utf8_general_ci, DROP token');
        $this->addSql('ALTER TABLE invitations CHANGE token token VARCHAR(100) NOT NULL COLLATE utf8_general_ci');
        $this->addSql('ALTER TABLE shared_links CHANGE token token VARCHAR(255) NOT NULL COLLATE utf8_general_ci');
    }
}
