<?php

namespace iCoordinator\Factory\Service;

use iCoordinator\Service\HistoryEventService;
use Psr\Container\ContainerInterface;

class HistoryEventServiceFactory implements ServiceFactoryInterface
{
    /**
     * @param ContainerInterface $c
     * @return HistoryEventService
     */
    public static function createService(ContainerInterface $c)
    {
        $service = new HistoryEventService();
        $service->setContainer($c);

        return $service;
    }
}
