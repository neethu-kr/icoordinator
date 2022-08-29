<?php

namespace iCoordinator\Factory\Service;

use iCoordinator\Service\InboundEmailService;
use Psr\Container\ContainerInterface;

class InboundEmailServiceFactory implements ServiceFactoryInterface
{
    /**
     * @param ContainerInterface $c
     * @return InboundEmailService
     */
    public static function createService(ContainerInterface $c)
    {
        $service = new InboundEmailService();
        $service->setContainer($c);

        return $service;
    }
}
