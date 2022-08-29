<?php

namespace iCoordinator\Config\Route;

use iCoordinator\Config\AbstractConfig;
use Slim\App;

class AuthRouteConfig extends AbstractConfig
{
    const ROUTE_AUTH_TOKEN = 'tokenEndpoint';
    const ROUTE_AUTH_AUTHORIZE = 'authorizeEndpoint';
    const ROUTE_AUTH_PROTECTED_RESOURCE = 'protectedResourceEndpoint';

    public function configure(App $app)
    {
        $app->group('/auth', function () use ($app) {
            $app->post('/token', 'AuthController:tokenAction')
                ->setName(self::ROUTE_AUTH_TOKEN);

            $app->map(['GET', 'POST'], '/authorize', 'AuthController:authorizeAction')
                ->setName(self::ROUTE_AUTH_AUTHORIZE);

            $app->map(['GET', 'POST'], '/protected', 'AuthController:protectedAction')
                ->setName(self::ROUTE_AUTH_PROTECTED_RESOURCE);
        });
    }
}
