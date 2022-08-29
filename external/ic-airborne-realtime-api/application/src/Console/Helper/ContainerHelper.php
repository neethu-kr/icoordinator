<?php

namespace iCoordinator\Console\Helper;

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Helper\Helper;

class ContainerHelper extends Helper
{

    /**
     * @var ContainerInterface $container
     */
    protected $container;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'container';
    }
}
