<?php

namespace iCoordinator\Config;

use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Slim\App;

class LoggerConfig extends AbstractConfig
{
    public function configure(App $app)
    {
        $c = $app->getContainer();

        $c['logger'] = function (ContainerInterface $c) {
            $logger = new Logger('iCoordinator');
            $logger->pushHandler(new ErrorLogHandler());

            return $logger;
        };
    }
}
