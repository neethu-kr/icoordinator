<?php

namespace iCoordinator\Config\Route;

use iCoordinator\Config\AbstractConfig;
use Slim\App;

class HistoryEventsRouteConfig extends AbstractConfig
{
    const ROUTE_HISTORY_EVENTS_GET = 'getHistoryEventsEndpoint';

    public function configure(App $app)
    {
        $app->get('/historyevents', 'HistoryEventsController:getHistoryEventsAction')
            ->setName(self::ROUTE_HISTORY_EVENTS_GET);
    }
}
