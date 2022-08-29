<?php

namespace iCoordinator\Factory\Service;

use iCoordinator\Service\EventService;
use Psr\Container\ContainerInterface;

class EventServiceFactory implements ServiceFactoryInterface
{
    /**
     * @param ContainerInterface $c
     * @return EventService
     */
    public static function createService(ContainerInterface $c)
    {
        $service = new EventService();
        $service->setContainer($c);

        return $service;
    }
}
