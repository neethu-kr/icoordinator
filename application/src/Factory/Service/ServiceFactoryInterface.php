<?php

namespace iCoordinator\Factory\Service;

use Psr\Container\ContainerInterface;

interface ServiceFactoryInterface
{
    public static function createService(ContainerInterface $c);
}
