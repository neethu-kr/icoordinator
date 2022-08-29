<?php

namespace iCoordinator\Factory\Service;

use iCoordinator\Service\SmartFolderService;
use Psr\Container\ContainerInterface;

class SmartFolderServiceFactory implements ServiceFactoryInterface
{
    /**
     * @param ContainerInterface $c
     * @return SmartFolderService
     */
    public static function createService(ContainerInterface $c)
    {
        $service = new SmartFolderService();
        $service->setContainer($c);

        return $service;
    }
}
