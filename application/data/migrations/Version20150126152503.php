<?php

namespace iCoordinator\Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150126152503 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE shared_links DROP FOREIGN KEY FK_2FEC4C0593CB796C');
        $this->addSql('ALTER TABLE shared_links ADD CONSTRAINT FK_2FEC4C0593CB796C FOREIGN KEY (file_id) REFERENCES files (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE shared_links DROP FOREIGN KEY FK_2FEC4C0593CB796C');
        $this->addSql('ALTER TABLE shared_links ADD CONSTRAINT FK_2FEC4C0593CB796C FOREIGN KEY (file_id) REFERENCES files (id)');
    }
}
