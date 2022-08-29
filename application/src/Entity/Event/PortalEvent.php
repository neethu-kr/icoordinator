<?php

namespace iCoordinator\Entity\Event;

use iCoordinator\Entity\Event;
use iCoordinator\Entity\Portal;

/**
 * @Entity
 */
class PortalEvent extends Event
{
    const ENTITY_NAME = 'entity:Event\PortalEvent';

    const TYPE_CREATE = 'PORTAL_CREATE';
    const TYPE_DELETE = 'PORTAL_DELETE';

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
            self::TYPE_DELETE
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
