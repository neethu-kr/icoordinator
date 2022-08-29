<?php

namespace iCoordinator\Config\Route;

use iCoordinator\Config\AbstractConfig;
use Slim\App;

class ChargifyRouteConfig extends AbstractConfig
{
    const ROUTE_CHARGIFY_WEBHOOK = 'chargifyWebhookEndpoint';

    public function configure(App $app)
    {
        $app->map(['GET', 'POST'], '/chargify/webhook', 'ChargifyController:processWebhookAction')
            ->setName(self::ROUTE_CHARGIFY_WEBHOOK);
    }
}
