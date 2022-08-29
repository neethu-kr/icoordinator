<?php

namespace iCoordinator\Config\Route;

use iCoordinator\Config\AbstractConfig;
use Slim\App;

class DefaultRouteConfig extends AbstractConfig
{
    const ROUTE_OPTIONS = 'optionsEndpoint';
    const EXAMPLE_ERROR = 'exampleErrorEnpoint';
    const PING = 'pingEndpoint';

    public function configure(App $app)
    {
        $app->get('/example-error', 'DefaultController:exampleErrorAction')
            ->setName(self::EXAMPLE_ERROR);
        $app->get('/ping', 'DefaultController:pingAction')
            ->setName(self::PING);
        $app->options('/[.+]', 'DefaultController:optionsAction')
            ->setName(self::ROUTE_OPTIONS);
    }
}
