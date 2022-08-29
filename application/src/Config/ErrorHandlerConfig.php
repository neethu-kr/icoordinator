<?php

namespace iCoordinator\Config;

use iCoordinator\ErrorHandler;
use iCoordinator\NotAllowedHandler;
use Monolog\Logger;
use Slim\App;

class ErrorHandlerConfig extends AbstractConfig
{
    public function configure(App $app)
    {
        $c = $app->getContainer();

        $settings = $c->get('settings');

        error_reporting(E_ALL);
        ini_set('log_errors', 1);

        if (isset($settings['mode']) && !in_array($settings['mode'], ['staging', 'production'])) {
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
        } else {
            ini_set('display_errors', 0);
            ini_set('display_startup_errors', 0);
        }

        /** @var Logger $logger */
        $logger = $c->get('logger');
        \Monolog\ErrorHandler::register($logger, [], false);

        $c['errorHandler'] = function ($c) use ($logger) {
            return new ErrorHandler($c, $logger);
        };

        $c['notAllowedHandler'] = function ($c) {
            return new NotAllowedHandler();
        };
    }
}
