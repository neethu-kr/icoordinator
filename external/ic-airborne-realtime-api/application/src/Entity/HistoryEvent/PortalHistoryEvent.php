<?php

namespace iCoordinator\Entity\HistoryEvent;

use iCoordinator\Entity\HistoryEvent;
use iCoordinator\Entity\Portal;

/**
 * @Entity
 */
class PortalHistoryEvent extends HistoryEvent
{
    const ENTITY_NAME = 'entity:HistoryEvent\PortalHistoryEvent';

    const TYPE_CREATE = 'PORTAL_CREATE';
    const TYPE_DELETE = 'PORTAL_DELETE';
    const TYPE_USER_ADDED = 'PORTAL_USER_ADDED';
    const TYPE_USER_REMOVED = 'PORTAL_USER_REMOVED';
    const TYPE_USER_CHANGE_ACCESS = 'PORTAL_CHANGE_ACCESS';
    const TYPE_USER_ALLOWED_CLIENTS_UPDATE = 'PORTAL_ALLOWED_CLIENTS_UPDATE';

    /**
     * @ManyToOne(targetEntity="iCoordinator\Entity\Portal")
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
            self::TYPE_DELETE,
            self::TYPE_USER_ADDED,
            self::TYPE_USER_REMOVED,
            self::TYPE_USER_CHANGE_ACCESS,
            self::TYPE_USER_ALLOWED_CLIENTS_UPDATE
        );
    }

    public static function getEntityName()
    {
        return self::ENTITY_NAME;
    }

    /**
     * @return \iCoordinator\Entity\Portal
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
        if (!$source instanceof Portal) {
            throw new \InvalidArgumentException(
                '$source should be instance of iCoordinator\\Entity\\Portal'
            );
        }
        $this->source = $source;
        return $this;
    }
}
