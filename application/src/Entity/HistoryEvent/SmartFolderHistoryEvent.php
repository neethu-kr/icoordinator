<?php

namespace iCoordinator\Entity\HistoryEvent;

use iCoordinator\Entity\HistoryEvent;
use iCoordinator\Entity\SmartFolder;

/**
 * @Entity
 */
class SmartFolderHistoryEvent extends HistoryEvent
{
    const ENTITY_NAME = 'entity:HistoryEvent\SmartFolderHistoryEvent';

    const TYPE_CREATE = 'SMART_FOLDER_CREATE';
    const TYPE_DELETE = 'SMART_FOLDER_DELETE';
    const TYPE_UPDATE = 'SMART_FOLDER_UPDATE';
    const TYPE_ADD_CRITERION = 'SMART_FOLDER_ADD_CRITERION';
    const TYPE_UPDATE_CRITERION = 'SMART_FOLDER_UPDATE_CRITERION';
    const TYPE_DELETE_CRITERION = 'SMART_FOLDER_DELETE_CRITERION';

    /**
     * @ManyToOne(targetEntity="iCoordinator\Entity\SmartFolder", fetch="EAGER")
     * @JoinColumn(name="source_id", referencedColumnName="id")
     **/
    protected $source = null;

    /**
     * @return array
     */
    public static function getEventTypes()
    {
        return array(
            self::TYPE_CREATE,
            self::TYPE_DELETE,
            self::TYPE_UPDATE,
            self::TYPE_ADD_CRITERION,
            self::TYPE_UPDATE_CRITERION,
            self::TYPE_DELETE_CRITERION
        );
    }

    public static function getEntityName()
    {
        return self::ENTITY_NAME;
    }

    /**
     * @return \iCoordinator\Entity\SmartFolder
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
        if (!$source instanceof SmartFolder) {
            throw new \InvalidArgumentException('$source should be instance of iCoordinator\\Entity\\SmartFolder');
        }
        $this->source = $source;
        return $this;
    }
}
