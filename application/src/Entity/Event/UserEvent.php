<?php

namespace iCoordinator\Entity\Event;

use iCoordinator\Entity\Event;
use iCoordinator\Entity\User;

/**
 * @Entity
 */
class UserEvent extends Event
{
    const ENTITY_NAME = 'entity:Event\UserEvent';

    const TYPE_CREATE = 'USER_CREATE';
    const TYPE_ADD = 'USER_ADD';
    const TYPE_REMOVE = 'USER_REMOVE';

    /**
     * @ManyToOne(targetEntity="iCoordinator\Entity\User")
     * @JoinColumn(name="source_id", referencedColumnName="id")
     **/
    protected $source;

    /**
     * @return array
     */
    public static function getEventTypes()
    {
        return array(
            self::TYPE_CREATE,
            self::TYPE_ADD,
            self::TYPE_REMOVE
        );
    }

    public static function getEntityName()
    {
        return self::ENTITY_NAME;
    }

    /**
     * @return \iCoordinator\Entity\User
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param $source
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setSource($source)
    {
        if (!$source instanceof User) {
            throw new \InvalidArgumentException(
                '$source should be instance of iCoordinator\\Entity\\User'
            );
        }
        $this->source = $source;
        return $this;
    }
}
