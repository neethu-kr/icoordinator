<?php

namespace iCoordinator\Entity\HistoryEvent;

use iCoordinator\Entity\Group;
use iCoordinator\Entity\HistoryEvent;

/**
 * @Entity
 */
class GroupHistoryEvent extends HistoryEvent
{
    const ENTITY_NAME = 'entity:HistoryEvent\GroupHistoryEvent';

    const TYPE_CREATE = 'GROUP_CREATE';
    const TYPE_CHANGE_NAME = 'GROUP_CHANGE_NAME';
    const TYPE_DELETE = 'GROUP_DELETE';
    const TYPE_ADD_USER = 'GROUP_ADD_USER';
    const TYPE_REMOVE_USER = 'GROUP_REMOVE_USER';

    /**
     * @ManyToOne(targetEntity="iCoordinator\Entity\Group")
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
            self::TYPE_CHANGE_NAME,
            self::TYPE_DELETE,
            self::TYPE_ADD_USER,
            self::TYPE_REMOVE_USER
        );
    }

    public static function getEntityName()
    {
        return self::ENTITY_NAME;
    }

    /**
     * @return \iCoordinator\Entity\Group
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
        if (!$source instanceof Group) {
            throw new \InvalidArgumentException(
                '$source should be instance of iCoordinator\\Entity\\Group'
            );
        }
        $this->source = $source;
        return $this;
    }
}
