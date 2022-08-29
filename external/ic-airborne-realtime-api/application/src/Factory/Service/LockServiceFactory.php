<?php

namespace iCoordinator\Factory\Service;

use iCoordinator\Service\LockService;
use Psr\Container\ContainerInterface;

class LockServiceFactory implements ServiceFactoryInterface
{
    /**
     * @param ContainerInterface $c
     * @return LockService
     */
    public static function createService(ContainerInterface $c)
    {
        $service = new LockService();
        $service->setContainer($c);

        return $service;
    }
}
