<?php

namespace iCoordinator\Factory\Service;

use iCoordinator\Service\SharedLinkService;
use Psr\Container\ContainerInterface;

class SharedLinkServiceFactory implements ServiceFactoryInterface
{
    /**
     * @param ContainerInterface $c
     * @return SharedLinkService
     */
    public static function createService(ContainerInterface $c)
    {
        $service = new SharedLinkService();
        $service->setContainer($c);

        return $service;
    }
}
