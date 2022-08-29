<?php

namespace iCoordinator\Factory\Service;

use iCoordinator\Service\FileEmailOptionsService;
use Psr\Container\ContainerInterface;

class FileEmailOptionsServiceFactory implements ServiceFactoryInterface
{
    /**
     * @param ContainerInterface $c
     * @return FileEmailOptionsService
     */
    public static function createService(ContainerInterface $c)
    {
        $service = new FileEmailOptionsService();
        $service->setContainer($c);

        return $service;
    }
}
