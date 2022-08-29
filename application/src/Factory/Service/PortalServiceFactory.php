<?php

namespace iCoordinator\Factory\Service;

use iCoordinator\Service\PortalService;
use Psr\Container\ContainerInterface;

class PortalServiceFactory implements ServiceFactoryInterface
{
    /**
     * @param ContainerInterface $c
     * @return PortalService
     */
    public static function createService(ContainerInterface $c)
    {
        $service = new PortalService();
        $service->setContainer($c);

        return $service;
    }
}
