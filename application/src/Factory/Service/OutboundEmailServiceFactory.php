<?php

namespace iCoordinator\Factory\Service;

use iCoordinator\Service\OutboundEmailService;
use Psr\Container\ContainerInterface;

class OutboundEmailServiceFactory implements ServiceFactoryInterface
{
    /**
     * @param ContainerInterface $c
     * @return OutboundEmailService
     */
    public static function createService(ContainerInterface $c)
    {
        $service = new OutboundEmailService();
        $service->setContainer($c);

        return $service;
    }
}
