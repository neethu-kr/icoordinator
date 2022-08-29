<?php

namespace iCoordinator\Factory\Service;

use iCoordinator\Service\SubscriptionService;
use Psr\Container\ContainerInterface;

class SubscriptionServiceFactory implements ServiceFactoryInterface
{
    /**
     * @param ContainerInterface $c
     * @return SubscriptionService
     */
    public static function createService(ContainerInterface $c)
    {
        $service = new SubscriptionService();
        $service->setContainer($c);

        return $service;
    }
}
