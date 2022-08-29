<?php

namespace iCoordinator\Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140613134426 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != "mysql",
            "Migration can only be executed safely on 'mysql'."
        );
        
        $this->addSql(
            "CREATE TABLE oauth_access_tokens (access_token VARCHAR(40) NOT NULL, client_id VARCHAR(80) NOT NULL,
            user_id VARCHAR(255) DEFAULT NULL, expires DATETIME NOT NULL, scope VARCHAR(2000) DEFAULT NULL,
            PRIMARY KEY(access_token)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = InnoDB"
        );
        $this->addSql(
            "CREATE TABLE oauth_authorization_codes (authorization_code VARCHAR(40) NOT NULL,
            client_id VARCHAR(80) NOT NULL, user_id VARCHAR(255) DEFAULT NULL, redirect_uri VARCHAR(2000) DEFAULT NULL,
            expires DATETIME NOT NULL, scope VARCHAR(2000) DEFAULT NULL, PRIMARY KEY(authorization_code))
            DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = InnoDB"
        );
        $this->addSql(
            "CREATE TABLE oauth_clients (client_id VARCHAR(80) NOT NULL, client_secret VARCHAR(80) NOT NULL,
            redirect_uri VARCHAR(2000) NOT NULL, grant_types VARCHAR(80) DEFAULT NULL, scope VARCHAR(100) DEFAULT NULL,
            user_id VARCHAR(80) DEFAULT NULL, PRIMARY KEY(client_id)) DEFAULT CHARACTER SET utf8 COLLATE
            utf8_general_ci ENGINE = InnoDB"
        );
        $this->addSql(
            "CREATE TABLE oauth_jwt (client_id VARCHAR(80) NOT NULL, subject VARCHAR(80) DEFAULT NULL,
            public_key VARCHAR(2000) DEFAULT NULL, PRIMARY KEY(client_id)) DEFAULT CHARACTER SET utf8 COLLATE
            utf8_general_ci ENGINE = InnoDB"
        );
        $this->addSql(
            "CREATE TABLE oauth_refresh_tokens (refresh_token VARCHAR(40) NOT NULL, client_id VARCHAR(80) NOT NULL,
            user_id VARCHAR(255) DEFAULT NULL, expires DATETIME NOT NULL, scope VARCHAR(2000) DEFAULT NULL,
            PRIMARY KEY(refresh_token)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = InnoDB"
        );
        $this->addSql(
            "CREATE TABLE oauth_scopes (id INT AUTO_INCREMENT NOT NULL, scope TEXT DEFAULT NULL,
            is_default TINYINT(1) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE
            utf8_general_ci ENGINE = InnoDB"
        );
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != "mysql",
            "Migration can only be executed safely on 'mysql'."
        );
        
        $this->addSql("DROP TABLE oauth_access_tokens");
        $this->addSql("DROP TABLE oauth_authorization_codes");
        $this->addSql("DROP TABLE oauth_clients");
        $this->addSql("DROP TABLE oauth_jwt");
        $this->addSql("DROP TABLE oauth_refresh_tokens");
        $this->addSql("DROP TABLE oauth_scopes");
    }
}
