<?php

namespace iCoordinator\Entity\Event;

use iCoordinator\Entity\Event;
use iCoordinator\Entity\Workspace;

/**
 * @Entity
 */
class WorkspaceEvent extends Event
{
    const ENTITY_NAME = 'entity:Event\WorkspaceEvent';

    const TYPE_CREATE = 'WORKSPACE_CREATE';
    const TYPE_DELETE = 'WORKSPACE_DELETE';
    const TYPE_RENAME = 'WORKSPACE_RENAME';

    /**
     * @ManyToOne(targetEntity="iCoordinator\Entity\Workspace")
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
            self::TYPE_RENAME
        );
    }

    public static function getEntityName()
    {
        return self::ENTITY_NAME;
    }

    /**
     * @return \iCoordinator\Entity\Workspace
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
        if (!$source instanceof Workspace) {
            throw new \InvalidArgumentException(
                '$source should be instance of iCoordinator\\Entity\\Workspace'
            );
        }
        $this->source = $source;
        return $this;
    }
}
