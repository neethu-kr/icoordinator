<?php

namespace iCoordinator\PHPUnit\Helper;

use Doctrine\ORM\EntityManager;
use Laminas\Hydrator\ClassMethodsHydrator;

abstract class AbstractDataHelper
{
    const RANDOMIZER_TYPE_APPEND = 'append';
    const RANDOMIZER_TYPE_PREPEND = 'prepend';

    /**
     * @var string
     */
    protected $defaultRandomizer = self::RANDOMIZER_TYPE_APPEND;

    /**
     * @var string
     */
    protected $defaultSeparator = ' ';

    /**
     * @var array
     */
    protected $defaults = [];

    /**
     * @var array
     */
    private $randomizableFields = [];

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * @param $object
     * @param $data
     * @param $useDefaults
     * @param $randomizeDefaults
     * @return object
     */
    protected function hydrate($object, $data, $useDefaults, $randomizeDefaults)
    {
        if ($useDefaults) {
            $defaults = $this->defaults;
            if ($randomizeDefaults) {
                array_walk($defaults, array($this, 'randomize'));
            }
            $data = array_merge($defaults, $data);
        }

        $hydrator = new ClassMethodsHydrator();
        $object = $hydrator->hydrate($data, $object);

        return $object;
    }

    /**
     * @param $value
     * @param $key
     */
    protected function randomize(&$value, $key)
    {
        if (!array_key_exists($key, $this->randomizableFields)) {
            return;
        }

        $randomizer = $this->randomizableFields[$key];

        $separator = $this->defaultSeparator;
        if (isset($randomizer['separator'])) {
            $separator = $randomizer['separator'];
        }

        switch ($randomizer['type']) {
            case self::RANDOMIZER_TYPE_PREPEND:
                $value = uniqid() . $separator . $value;
                break;
            case self::RANDOMIZER_TYPE_APPEND:
            default:
                $value = $value . $separator . uniqid();
                break;
        }
    }
}
