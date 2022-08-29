<?php

namespace iCoordinator\Entity\Event;

use iCoordinator\Entity\Event;
use iCoordinator\Entity\File;

/**
 * @Entity
 */
class FileEvent extends Event
{
    const ENTITY_NAME = 'entity:Event\FileEvent';

    const TYPE_CREATE = 'FILE_CREATE';
    const TYPE_DELETE = 'FILE_DELETE';
    const TYPE_RENAME = 'FILE_RENAME';
    const TYPE_MOVE = 'FILE_MOVE';
    const TYPE_CONTENT_UPDATE = 'FILE_CONTENT_UPDATE';
    const TYPE_SELECTIVESYNC_CREATE = 'FILE_SELECTIVESYNC_CREATE';
    const TYPE_SELECTIVESYNC_DELETE = 'FILE_SELECTIVESYNC_DELETE';
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
            self::TYPE_SELECTIVESYNC_DELETE
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
