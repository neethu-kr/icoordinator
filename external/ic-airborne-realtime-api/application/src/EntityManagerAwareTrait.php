<?php

namespace iCoordinator;

use Doctrine\ORM\EntityManager;
use Psr\Container\ContainerInterface;
use RuntimeException;

trait EntityManagerAwareTrait
{
    use ContainerAwareTrait;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        if ($this->entityManager === null) {
            if ($this->container instanceof ContainerInterface) {
                $this->entityManager = $this->container->get('entityManager');
            } else {
                throw new RuntimeException('Cannot get entity manager');
            }
        }
        return $this->entityManager;
    }
}
