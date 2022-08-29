<?php

namespace iCoordinator\Factory\Service;

use iCoordinator\Service\ChargifyWebhookService;
use Psr\Container\ContainerInterface;

class ChargifyWebhookServiceFactory implements ServiceFactoryInterface
{
    /**
     * @param ContainerInterface $c
     * @return ChargifyWebhookService
     */
    public static function createService(ContainerInterface $c)
    {
        $service = new ChargifyWebhookService();
        $service->setContainer($c);

        return $service;
    }
}
