<?php

namespace iCoordinator\Factory\Service;

use iCoordinator\Service\MetaFieldService;
use Psr\Container\ContainerInterface;

class MetaFieldServiceFactory implements ServiceFactoryInterface
{
    /**
     * @param ContainerInterface $c
     * @return MetaFieldService
     */
    public static function createService(ContainerInterface $c)
    {
        $service = new MetaFieldService();
        $service->setContainer($c);

        return $service;
    }
}
