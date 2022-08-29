<?php

use iCoordinator\CliApp;
use iCoordinator\Console\Helper\ContainerHelper;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\HelperSet;


(@include_once __DIR__ . '/../vendor/autoload.php') || @include_once __DIR__ . '/../../vendor/autoload.php';

//init bootstrap
$app = CliApp::create(array(
    'applicationPath' => realpath(dirname(__DIR__)) . '/application'
));

$cli = new Application('iCoordinator BDX AD Connector Command Line Interface');
$cli->setCatchExceptions(true);
$cli->setHelperSet(new HelperSet(array(
    'container' => new ContainerHelper($app->getContainer()),
    'dialog' => new \Symfony\Component\Console\Helper\QuestionHelper()
)));

$cli->addCommands(array(
    new \iCoordinator\Console\Command\CustomerSpecific\BDX\GetInformationFromAD()
));

$cli->run();