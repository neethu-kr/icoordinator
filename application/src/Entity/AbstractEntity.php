<?php

namespace iCoordinator\Entity;

use Doctrine\ORM\Internal\Hydration\HydrationException;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Stdlib\JsonSerializable;

abstract class AbstractEntity implements JsonSerializable, ResourceInterface
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @param $id
     * @return $this
     * @throws HydrationException
     */
    public function setId($id)
    {
        if ($this->id !== null) {
            throw new HydrationException("Entity ID can't be modified");
        }
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public static function getEntityName()
    {
        throw new \Exception('Static getEntityName() method is not defined');
        return '';
    }

    abstract public function jsonSerialize();
}
