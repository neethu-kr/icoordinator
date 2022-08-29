<?php

namespace iCoordinator\Factory\Service;

use iCoordinator\Service\GroupService;
use Psr\Container\ContainerInterface;

class GroupServiceFactory implements ServiceFactoryInterface
{
    /**
     * @param ContainerInterface $c
     * @return GroupService
     */
    public static function createService(ContainerInterface $c)
    {
        $service = new GroupService();
        $service->setContainer($c);

        return $service;
    }
}
