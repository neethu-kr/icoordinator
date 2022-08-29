<?php

namespace iCoordinator;

require  './../vendor/autoload.php';

$app = WebApp::create(array(
    'applicationPath' => realpath(dirname(__DIR__)) . '/application',
));

$app->run();
