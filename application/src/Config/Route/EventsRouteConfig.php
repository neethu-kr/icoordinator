<?php

namespace iCoordinator\Config\Route;

use iCoordinator\Config\AbstractConfig;
use Slim\App;

class EventsRouteConfig extends AbstractConfig
{
    const ROUTE_EVENTS_GET = 'getEventsEndpoint';
    const ROUTE_EVENTS_REAL_TIME_SERVER_GET = 'getEventsRealTimeServerEndpoint';
    const ROUTE_HISTORY_GET = 'getHistoryEndpoint';
    const ROUTE_EVENTS_FOR_OBJECT_GET = 'getEventsForObjectEndpoint';

    public function configure(App $app)
    {
        $app->options('/events', 'EventsController:getEventsRealTimeServerAction')
            ->setName(self::ROUTE_EVENTS_REAL_TIME_SERVER_GET);

        $app->get('/events', 'EventsController:getEventsAction')
            ->setName(self::ROUTE_EVENTS_GET);

        $app->get('/history', 'EventsController:getHistoryAction')
            ->setName(self::ROUTE_HISTORY_GET);

        $app->get('/eventsForObject', 'EventsController:getEventsForObjectAction')
            ->setName(self::ROUTE_EVENTS_FOR_OBJECT_GET);
    }
}
