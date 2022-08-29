<?php

namespace iCoordinator\Factory\Service;

use iCoordinator\Service\PermissionService;
use Psr\Container\ContainerInterface;

class PermissionServiceFactory implements ServiceFactoryInterface
{
    /**
     * @param ContainerInterface $c
     * @return PermissionService
     */
    public static function createService(ContainerInterface $c)
    {
        $service = new PermissionService();
        $service->setContainer($c);

        return $service;
    }
}
