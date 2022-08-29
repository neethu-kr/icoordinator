<?php

namespace iCoordinator\Factory\Service;

use iCoordinator\Service\EmailConfirmationService;
use Psr\Container\ContainerInterface;

class EmailConfirmationServiceFactory implements ServiceFactoryInterface
{
    /**
     * @param ContainerInterface $c
     * @return EmailConfirmationService
     */
    public static function createService(ContainerInterface $c)
    {
        $service = new EmailConfirmationService();
        $service->setContainer($c);

        return $service;
    }
}
