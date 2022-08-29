<?php

namespace iCoordinator\Entity\Event;

use iCoordinator\Entity\Acl\AclPermission;
use iCoordinator\Entity\Event;

/**
 * @Entity
 */
class PermissionEvent extends Event
{
    const ENTITY_NAME = 'entity:Event\PermissionEvent';

    const TYPE_CREATE = 'PERMISSION_CREATE';
    const TYPE_DELETE = 'PERMISSION_DELETE';
    const TYPE_CHANGE = 'PERMISSION_CHANGE';

    /**
     * @ManyToOne(targetEntity="iCoordinator\Entity\Acl\AclPermission")
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
            self::TYPE_CHANGE
        );
    }

    public static function getEntityName()
    {
        return self::ENTITY_NAME;
    }

    /**
     * @return \iCoordinator\Entity\Acl\AclPermission
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
        if (!$source instanceof AclPermission) {
            throw new \InvalidArgumentException(
                '$source should be instance of iCoordinator\\Entity\\Acl\\AclPermission'
            );
        }
        $this->source = $source;
        return $this;
    }
}
