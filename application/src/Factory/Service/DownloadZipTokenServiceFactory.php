<?php

namespace iCoordinator\Factory\Service;

use iCoordinator\Service\DownloadZipTokenService;
use Psr\Container\ContainerInterface;

class DownloadZipTokenServiceFactory implements ServiceFactoryInterface
{
    /**
     * @param ContainerInterface $c
     * @return DownloadZipTokenService
     */
    public static function createService(ContainerInterface $c)
    {
        $service = new DownloadZipTokenService();
        $service->setContainer($c);

        return $service;
    }
}
