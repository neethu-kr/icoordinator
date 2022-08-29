<?php

namespace iCoordinator\Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;


/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150902075650 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */

    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE user_locales (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, lang VARCHAR(2) NOT NULL, date_format VARCHAR(50) NOT NULL, time_format VARCHAR(50) NOT NULL, first_week_day INT NOT NULL, UNIQUE INDEX UNIQ_BD964AB1A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_locales ADD CONSTRAINT FK_BD964AB1A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE user_locales');
    }
    public function postUp(Schema $schema)
    {
        try {
            foreach($this->connection->query('SELECT * from users') as $user) {
                $this->connection->query("INSERT INTO user_locales (user_id,lang,date_format,time_format,first_week_day) VALUES (".$user['id'].",'en','yyyy-mm-dd','H:i:s',1)");
            }
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
            die();
        }
    }
}
