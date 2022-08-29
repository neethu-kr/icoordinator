<?php

namespace iCoordinator\Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160407064556 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE invitation_workspaces DROP INDEX UNIQ_E5D9DE8682D40A1F, ADD INDEX IDX_E5D9DE8682D40A1F (workspace_id)');
        $this->addSql('ALTER TABLE invitation_workspace_groups DROP INDEX UNIQ_F229B83DFE54D947, ADD INDEX IDX_F229B83DFE54D947 (group_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE invitation_workspace_groups DROP INDEX IDX_F229B83DFE54D947, ADD UNIQUE INDEX UNIQ_F229B83DFE54D947 (group_id)');
        $this->addSql('ALTER TABLE invitation_workspaces DROP INDEX IDX_E5D9DE8682D40A1F, ADD UNIQUE INDEX UNIQ_E5D9DE8682D40A1F (workspace_id)');
    }
}
