<?php

namespace iCoordinator\Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150318113138 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE email_confirmations ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE email_confirmations ADD CONSTRAINT FK_C61E9D36A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C61E9D36A76ED395 ON email_confirmations (user_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE email_confirmations DROP FOREIGN KEY FK_C61E9D36A76ED395');
        $this->addSql('DROP INDEX UNIQ_C61E9D36A76ED395 ON email_confirmations');
        $this->addSql('ALTER TABLE email_confirmations DROP user_id');
    }
}
