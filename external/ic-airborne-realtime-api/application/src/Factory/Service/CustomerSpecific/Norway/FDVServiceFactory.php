<?php

namespace iCoordinator\Factory\Service\CustomerSpecific\Norway;

use iCoordinator\Factory\Service\ServiceFactoryInterface;
use iCoordinator\Service\CustomerSpecific\Norway\FDVService;
use Psr\Container\ContainerInterface;

class FDVServiceFactory implements ServiceFactoryInterface
{
    /**
     * @param ContainerInterface $c
     * @return FDVService
     */
    public static function createService(ContainerInterface $c)
    {
        $service = new FDVService();
        $service->setContainer($c);

        return $service;
    }
}
