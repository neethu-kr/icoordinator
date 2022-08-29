<?php

namespace iCoordinator\Factory\Service;

use iCoordinator\Service\DownloadTokenService;
use Psr\Container\ContainerInterface;

class DownloadTokenServiceFactory implements ServiceFactoryInterface
{
    /**
     * @param ContainerInterface $c
     * @return DownloadTokenService
     */
    public static function createService(ContainerInterface $c)
    {
        $service = new DownloadTokenService();
        $service->setContainer($c);

        return $service;
    }
}
