<?php

namespace iCoordinator\Config\Route;

use iCoordinator\Config\AbstractConfig;
use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

class InboundEmailsRouteConfig extends AbstractConfig
{
    const ROUTE_INBOUND_EMAIL_PROCESS = 'processInboundEmailEndpoint';
    const ROUTE_INBOUND_EMAIL_PING = 'pingInboundEmailEndpoint';

    public function configure(App $app)
    {
        $app->post('/inbound-emails', 'InboundEmailsController:processInboundEmailAction')
            ->setName(self::ROUTE_INBOUND_EMAIL_PROCESS);

        $app->get('/inbound-emails', function (Request $request, Response $response, $args) use ($app) {
            return $response->withStatus(200);
        })->setName(self::ROUTE_INBOUND_EMAIL_PING);
    }
}
