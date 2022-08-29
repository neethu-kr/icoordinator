<?php

namespace iCoordinator\Factory\Service;

use iCoordinator\Service\FolderService;
use Psr\Container\ContainerInterface;

class FolderServiceFactory implements ServiceFactoryInterface
{
    /**
     * @param ContainerInterface $c
     * @return FolderService
     */
    public static function createService(ContainerInterface $c)
    {
        $service = new FolderService();
        $service->setContainer($c);

        return $service;
    }
}
