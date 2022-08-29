<?php

namespace iCoordinator\Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Rhumsaa\Uuid\Uuid;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141229152508 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $stmt = $this->connection->query('SELECT * FROM users');
        while ($row = $stmt->fetch()) {
            if (empty($row['uuid'])) {
                $this->connection->update('users', array('uuid' => Uuid::uuid4()), array('id' => $row['id']));
            }
        }
        // this up() migration is auto-generated, please modify it to your needs

    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
