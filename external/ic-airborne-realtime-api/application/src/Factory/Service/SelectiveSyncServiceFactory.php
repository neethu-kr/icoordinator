<?php

namespace iCoordinator\Factory\Service;

use iCoordinator\Service\SelectiveSyncService;
use Psr\Container\ContainerInterface;

class SelectiveSyncServiceFactory implements ServiceFactoryInterface
{
    /**
     * @param ContainerInterface $c
     * @return SelectiveSyncService
     */
    public static function createService(ContainerInterface $c)
    {
        $service = new SelectiveSyncService();
        $service->setContainer($c);

        return $service;
    }
}
