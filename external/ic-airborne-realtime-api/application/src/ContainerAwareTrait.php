<?php

namespace iCoordinator;

use Psr\Container\ContainerInterface;

trait ContainerAwareTrait
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @return ContainerInterface
     */
    final protected function getContainer()
    {
        return $this->container;
    }

    /**
     * @param ContainerInterface $container
     */
    final public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }
}
