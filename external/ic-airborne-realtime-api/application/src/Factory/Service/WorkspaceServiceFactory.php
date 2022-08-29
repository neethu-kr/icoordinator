<?php

namespace iCoordinator\Factory\Service;

use iCoordinator\Service\WorkspaceService;
use Psr\Container\ContainerInterface;

class WorkspaceServiceFactory implements ServiceFactoryInterface
{
    /**
     * @param ContainerInterface $c
     * @return WorkspaceService
     */
    public static function createService(ContainerInterface $c)
    {
        $service = new WorkspaceService();
        $service->setContainer($c);

        return $service;
    }
}
