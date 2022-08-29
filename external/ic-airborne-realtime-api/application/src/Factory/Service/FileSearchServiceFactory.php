<?php

namespace iCoordinator\Factory\Service;

use iCoordinator\Service\FileSearchService;
use Psr\Container\ContainerInterface;

class FileSearchServiceFactory implements ServiceFactoryInterface
{
    /**
     * @param ContainerInterface $c
     * @return FileSearchService
     */
    public static function createService(ContainerInterface $c)
    {
        $service = new FileSearchService();
        $service->setContainer($c);

        return $service;
    }
}
