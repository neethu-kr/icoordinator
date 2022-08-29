<?php

namespace iCoordinator\Service;

use Psr\Container\ContainerInterface;

interface ServiceInterface
{
    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container);
}
