<?php

namespace iCoordinator\Entity\HistoryEvent;

use iCoordinator\Entity\File;
use iCoordinator\Entity\HistoryEvent;

/**
 * @Entity
 */
class FileHistoryEvent extends HistoryEvent
{
    const ENTITY_NAME = 'entity:HistoryEvent\FileHistoryEvent';

    const TYPE_CREATE = 'FILE_CREATE';
    const TYPE_DELETE = 'FILE_DELETE';
    const TYPE_RENAME = 'FILE_RENAME';
    const TYPE_MOVE = 'FILE_MOVE';
    const TYPE_CONTENT_UPDATE = 'FILE_CONTENT_UPDATE';
    const TYPE_SELECTIVESYNC_CREATE = 'FILE_SELECTIVESYNC_CREATE';
    const TYPE_SELECTIVESYNC_DELETE = 'FILE_SELECTIVESYNC_DELETE';
    const TYPE_FILE_LOCK = 'FILE_LOCK';
    const TYPE_FILE_UNLOCK = 'FILE_UNLOCK';
    const TYPE_FOLDER_LOCK = 'FOLDER_LOCK';
    const TYPE_FOLDER_UNLOCK = 'FOLDER_UNLOCK';
    const TYPE_FILE_SHARED_LINK_CREATE = 'FILE_SHARED_LINK_CREATE';
    const TYPE_FILE_SHARED_LINK_UPDATE = 'FILE_SHARED_LINK_UPDATE';
    const TYPE_FILE_SHARED_LINK_DELETE = 'FILE_SHARED_LINK_DELETE';
    /**
     * @ManyToOne(targetEntity="iCoordinator\Entity\File")
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
            self::TYPE_MOVE,
            self::TYPE_CONTENT_UPDATE,
            self::TYPE_SELECTIVESYNC_CREATE,
            self::TYPE_SELECTIVESYNC_DELETE,
            self::TYPE_FILE_LOCK,
            self::TYPE_FILE_UNLOCK,
            self::TYPE_FOLDER_LOCK,
            self::TYPE_FOLDER_UNLOCK,
            self::TYPE_FILE_SHARED_LINK_CREATE,
            self::TYPE_FILE_SHARED_LINK_UPDATE,
            self::TYPE_FILE_SHARED_LINK_DELETE
        );
    }

    public static function getEntityName()
    {
        return self::ENTITY_NAME;
    }

    /**
     * @return \iCoordinator\Entity\File
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
        if (!$source instanceof File) {
            throw new \InvalidArgumentException('$source should be instance of iCoordinator\\Entity\\File');
        }
        $this->source = $source;
        return $this;
    }
}
