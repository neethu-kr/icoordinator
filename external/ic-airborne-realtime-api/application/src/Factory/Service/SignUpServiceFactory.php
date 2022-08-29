<?php

namespace iCoordinator\Factory\Service;

use iCoordinator\Service\SignUpService;
use Psr\Container\ContainerInterface;

class SignUpServiceFactory implements ServiceFactoryInterface
{
    /**
     * @param ContainerInterface $c
     * @return SignUpService
     */
    public static function createService(ContainerInterface $c)
    {
        $service = new SignUpService();
        $service->setContainer($c);

        return $service;
    }
}
