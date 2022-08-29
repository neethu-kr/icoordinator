<?php

namespace iCoordinator\Entity\Event;

use iCoordinator\Entity\Event;
use iCoordinator\Entity\Folder;

/**
 * @Entity
 */
class FolderEvent extends Event
{
    const ENTITY_NAME = 'entity:Event\FolderEvent';

    const TYPE_CREATE = 'FOLDER_CREATE';
    const TYPE_DELETE = 'FOLDER_DELETE';
    const TYPE_RENAME = 'FOLDER_RENAME';
    const TYPE_MOVE = 'FOLDER_MOVE';
    const TYPE_SELECTIVESYNC_CREATE = 'FOLDER_SELECTIVESYNC_CREATE';
    const TYPE_SELECTIVESYNC_DELETE = 'FOLDER_SELECTIVESYNC_DELETE';

    /**
     * @ManyToOne(targetEntity="iCoordinator\Entity\Folder", fetch="EAGER")
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
            self::TYPE_RENAME,
            self::TYPE_MOVE,
            self::TYPE_SELECTIVESYNC_CREATE,
            self::TYPE_SELECTIVESYNC_DELETE
        );
    }

    public static function getEntityName()
    {
        return self::ENTITY_NAME;
    }

    /**
     * @return \iCoordinator\Entity\Folder
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
        if (!$source instanceof Folder) {
            throw new \InvalidArgumentException('$source should be instance of iCoordinator\\Entity\\Folder');
        }
        $this->source = $source;
        return $this;
    }
}
