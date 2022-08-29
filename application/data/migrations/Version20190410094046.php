<?php declare(strict_types=1);

namespace iCoordinator\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190410094046 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE download_zip_tokens (id INT AUTO_INCREMENT NOT NULL, created_by INT DEFAULT NULL, expires_at DATETIME NOT NULL, token VARCHAR(32) NOT NULL, UNIQUE INDEX UNIQ_A9B6CB465F37A13B (token), INDEX IDX_A9B6CB46DE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE download_zip_token_files (id INT AUTO_INCREMENT NOT NULL, file_id INT DEFAULT NULL, download_zip_token_id INT DEFAULT NULL, INDEX IDX_BEB2C4F893CB796C (file_id), INDEX IDX_BEB2C4F824FB82D9 (download_zip_token_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE download_zip_tokens ADD CONSTRAINT FK_A9B6CB46DE12AB56 FOREIGN KEY (created_by) REFERENCES users (id)');
        $this->addSql('ALTER TABLE download_zip_token_files ADD CONSTRAINT FK_BEB2C4F893CB796C FOREIGN KEY (file_id) REFERENCES files (id)');
        $this->addSql('ALTER TABLE download_zip_token_files ADD CONSTRAINT FK_BEB2C4F824FB82D9 FOREIGN KEY (download_zip_token_id) REFERENCES download_zip_tokens (id)');}

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE download_zip_token_files DROP FOREIGN KEY FK_BEB2C4F824FB82D9');
        $this->addSql('DROP TABLE download_zip_tokens');
        $this->addSql('DROP TABLE download_zip_token_files');
    }
}
