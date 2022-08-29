<?php

namespace iCoordinator\Factory\Service;

use iCoordinator\Service\StateService;
use Psr\Container\ContainerInterface;

class StateServiceFactory implements ServiceFactoryInterface
{
    /**
     * @param ContainerInterface $c
     * @return StateService
     */
    public static function createService(ContainerInterface $c)
    {
        $service = new StateService();
        $service->setContainer($c);

        return $service;
    }
}
