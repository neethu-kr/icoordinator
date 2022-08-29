<?php

use Doctrine\Common\ClassLoader;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\ORM\Tools\Console\ConsoleRunner;

(@include_once __DIR__ . '/../vendor/autoload.php') || @include_once __DIR__ . '/../../../autoload.php';

$classLoader = new ClassLoader('iCoordinator\Migrations', '/data/migrations');
$classLoader->register();

$commands = array(
    //Migration Commands
    new \Doctrine\DBAL\Migrations\Tools\Console\Command\DiffCommand(),
    new \Doctrine\DBAL\Migrations\Tools\Console\Command\ExecuteCommand(),
    new \Doctrine\DBAL\Migrations\Tools\Console\Command\GenerateCommand(),
    new \Doctrine\DBAL\Migrations\Tools\Console\Command\StatusCommand(),
    new \Doctrine\DBAL\Migrations\Tools\Console\Command\VersionCommand(),
    new \Doctrine\DBAL\Migrations\Tools\Console\Command\MigrateCommand()
);

//init bootstrap
$app = iCoordinator\CliApp::create(array(
    'applicationPath' => realpath(dirname(__DIR__)) . '/application'
));

$entityManager = $app->getContainer()->get('entityManager');

if (!$entityManager instanceof \Doctrine\ORM\EntityManager) {
    throw new RuntimeException('Entity Manager is not defined');
}

//init helperSet
$helperSet = ConsoleRunner::createHelperSet($entityManager);
$helperSet->set(new \Symfony\Component\Console\Helper\QuestionHelper());

//setup migrations config
$conn = $helperSet->get('connection')->getConnection();

$migrationsConfiguration = new Configuration($conn);
$mConf = $app->getContainer()->get('settings')['migrations'];

if (isset($mConf['name'])) {
    $migrationsConfiguration->setName($mConf['name']);
}
if (isset($mConf['tableName'])) {
    $migrationsConfiguration->setMigrationsTableName($mConf['tableName']);
}
if (isset($mConf['namespace'])) {
    $migrationsConfiguration->setMigrationsNamespace($mConf['namespace']);
}
if (isset($mConf['directory'])) {
    $migrationsConfiguration->setMigrationsDirectory($mConf['directory']);
    $migrationsConfiguration->registerMigrationsFromDirectory($mConf['directory']);
}

if (isset($mConf['filter_expression'])) {
    $conn->getConfiguration()->setFilterSchemaAssetsExpression($mConf['filter_expression']);
}

foreach ($commands as $command) {
    $command->setMigrationConfiguration($migrationsConfiguration);
}

ConsoleRunner::run($helperSet, $commands);

