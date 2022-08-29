<?php

namespace iCoordinator\Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160330072428 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE invitation_workspaces (id INT AUTO_INCREMENT NOT NULL, workspace_id INT DEFAULT NULL, invitation_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_E5D9DE8682D40A1F (workspace_id), INDEX IDX_E5D9DE86A35D7AF0 (invitation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE invitation_workspace_groups (id INT AUTO_INCREMENT NOT NULL, group_id INT DEFAULT NULL, invitation_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_F229B83DFE54D947 (group_id), INDEX IDX_F229B83DA35D7AF0 (invitation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE invitation_workspaces ADD CONSTRAINT FK_E5D9DE8682D40A1F FOREIGN KEY (workspace_id) REFERENCES workspaces (id)');
        $this->addSql('ALTER TABLE invitation_workspaces ADD CONSTRAINT FK_E5D9DE86A35D7AF0 FOREIGN KEY (invitation_id) REFERENCES invitations (id)');
        $this->addSql('ALTER TABLE invitation_workspace_groups ADD CONSTRAINT FK_F229B83DFE54D947 FOREIGN KEY (group_id) REFERENCES groups (id)');
        $this->addSql('ALTER TABLE invitation_workspace_groups ADD CONSTRAINT FK_F229B83DA35D7AF0 FOREIGN KEY (invitation_id) REFERENCES invitations (id)');
        $this->addSql('ALTER TABLE invitations DROP FOREIGN KEY FK_232710AEFE54D947');
        $this->addSql('DROP INDEX IDX_232710AEFE54D947 ON invitations');
        $this->addSql('ALTER TABLE invitations DROP group_id');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE invitation_workspaces');
        $this->addSql('DROP TABLE invitation_workspace_groups');
        $this->addSql('ALTER TABLE invitations ADD group_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE invitations ADD CONSTRAINT FK_232710AEFE54D947 FOREIGN KEY (group_id) REFERENCES groups (id)');
        $this->addSql('CREATE INDEX IDX_232710AEFE54D947 ON invitations (group_id)');
    }
}
