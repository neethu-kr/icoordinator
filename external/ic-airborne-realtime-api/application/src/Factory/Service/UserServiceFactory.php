<?php

namespace iCoordinator\Factory\Service;

use iCoordinator\Service\UserService;
use Psr\Container\ContainerInterface;

class UserServiceFactory implements ServiceFactoryInterface
{
    /**
     * @param ContainerInterface $c
     * @return UserService
     */
    public static function createService(ContainerInterface $c)
    {
        $service = new UserService();
        $service->setContainer($c);

        return $service;
    }
}
