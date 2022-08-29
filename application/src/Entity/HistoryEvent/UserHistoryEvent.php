<?php

namespace iCoordinator\Entity\HistoryEvent;

use iCoordinator\Entity\HistoryEvent;
use iCoordinator\Entity\User;

/**
 * @Entity
 */
class UserHistoryEvent extends HistoryEvent
{
    const ENTITY_NAME = 'entity:HistoryEvent\UserHistoryEvent';

    const TYPE_CREATE = 'USER_CREATE';

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
            self::TYPE_CREATE
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
        if (!$source instanceof User && $source != null) {
            throw new \InvalidArgumentException(
                '$source should be instance of iCoordinator\\Entity\\User'
            );
        }
        $this->source = $source;
        return $this;
    }
}
