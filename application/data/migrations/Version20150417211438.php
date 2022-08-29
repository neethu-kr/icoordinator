<?php

namespace iCoordinator\Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150417211438 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE locks (id INT AUTO_INCREMENT NOT NULL, file INT DEFAULT NULL, created_by INT DEFAULT NULL, expires_at DATETIME NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_FC316D978C9F3610 (file), INDEX IDX_FC316D97DE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE locks ADD CONSTRAINT FK_FC316D978C9F3610 FOREIGN KEY (file) REFERENCES files (id)');
        $this->addSql('ALTER TABLE locks ADD CONSTRAINT FK_FC316D97DE12AB56 FOREIGN KEY (created_by) REFERENCES users (id)');
        $this->addSql('ALTER TABLE files ADD lock_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE files ADD CONSTRAINT FK_6354059836D25DD FOREIGN KEY (lock_id) REFERENCES locks (id)');
        $this->addSql('CREATE INDEX IDX_6354059836D25DD ON files (lock_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE files DROP FOREIGN KEY FK_6354059836D25DD');
        $this->addSql('DROP TABLE locks');
        $this->addSql('DROP INDEX IDX_6354059836D25DD ON files');
        $this->addSql('ALTER TABLE files DROP lock_id');
    }
}
