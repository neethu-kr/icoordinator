<?php

namespace iCoordinator\Controller\Helper;

use iCoordinator\ContainerAwareTrait;
use Psr\Container\ContainerInterface;

abstract class AbstractControllerHelper
{
    use ContainerAwareTrait;

    public function __construct(ContainerInterface $c = null)
    {
        $this->container = $c;
    }

    abstract public function getHelperId();
}
