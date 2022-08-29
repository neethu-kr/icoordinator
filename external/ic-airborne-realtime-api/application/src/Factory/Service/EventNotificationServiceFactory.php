<?php

namespace iCoordinator\Factory\Service;

use iCoordinator\Service\EventNotificationService;
use Psr\Container\ContainerInterface;

class EventNotificationServiceFactory implements ServiceFactoryInterface
{
    /**
     * @param ContainerInterface $c
     * @return EventNotificationService
     */
    public static function createService(ContainerInterface $c)
    {
        $service = new EventNotificationService();
        $service->setContainer($c);

        return $service;
    }
}
