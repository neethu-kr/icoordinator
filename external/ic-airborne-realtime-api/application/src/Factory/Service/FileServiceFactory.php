<?php

namespace iCoordinator\Factory\Service;

use iCoordinator\Service\FileService;
use Psr\Container\ContainerInterface;

class FileServiceFactory implements ServiceFactoryInterface
{
    /**
     * @param ContainerInterface $c
     * @return FileService
     */
    public static function createService(ContainerInterface $c)
    {
        $service = new FileService();
        $service->setContainer($c);

        return $service;
    }
}
