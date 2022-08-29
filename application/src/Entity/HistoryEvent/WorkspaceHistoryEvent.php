<?php

namespace iCoordinator\Entity\HistoryEvent;

use iCoordinator\Entity\HistoryEvent;
use iCoordinator\Entity\Workspace;

/**
 * @Entity
 */
class WorkspaceHistoryEvent extends HistoryEvent
{
    const ENTITY_NAME = 'entity:HistoryEvent\WorkspaceHistoryEvent';

    const TYPE_CREATE = 'WORKSPACE_CREATE';
    const TYPE_DELETE = 'WORKSPACE_DELETE';
    const TYPE_RENAME = 'WORKSPACE_RENAME';
    const TYPE_USER_ADDED = 'WORKSPACE_USER_ADDED';
    const TYPE_USER_REMOVED = 'WORKSPACE_USER_REMOVED';
    const TYPE_USER_CHANGE_ACCESS = 'WORKSPACE_CHANGE_ACCESS';

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
            self::TYPE_RENAME,
            self::TYPE_USER_ADDED,
            self::TYPE_USER_REMOVED,
            self::TYPE_USER_CHANGE_ACCESS
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
