<?php

namespace iCoordinator\Config;

use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\ORM\EntityManager;
use Slim\App;

class DbMigrationsConfig extends AbstractConfig
{
    public function configure(App $app)
    {
        $c = $app->getContainer();

        $c['migrationsConfiguration'] = function ($c) {
            $settings = $c['settings']['migrations'];

            /** @var EntityManager $em */
            $em = $c->get('entityManager');

            $migrationsConfiguration = new Configuration($em->getConnection());

            $migrationsConfiguration->setName($settings['name']);
            $migrationsConfiguration->setMigrationsTableName($settings['tableName']);
            $migrationsConfiguration->setMigrationsNamespace($settings['namespace']);
            $migrationsConfiguration->setMigrationsDirectory($settings['directory']);
            $migrationsConfiguration->registerMigrationsFromDirectory($settings['directory']);

            return $migrationsConfiguration;
        };
    }
}
