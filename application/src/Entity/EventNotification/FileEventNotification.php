<?php

namespace iCoordinator\Entity\EventNotification;

use iCoordinator\Entity\EventNotification;
use iCoordinator\Entity\File;

/**
 * @Entity
 */
class FileEventNotification extends EventNotification
{
    const ENTITY_NAME = 'entity:EventNotification\FileEventNotification';

    const TYPE_CREATE = 'FILE_CREATE';
    const TYPE_UPDATE = 'FILE_UPDATE';
    const TYPE_DELETE = 'FILE_DELETE';

    /**
     * @ManyToOne(targetEntity="iCoordinator\Entity\File")
     * @JoinColumn(name="source_id", referencedColumnName="id")
     **/
    protected $source;

    /**
     * @return array
     */
    public static function getEventNotificationTypes()
    {
        return array(
            self::TYPE_CREATE,
            self::TYPE_UPDATE,
            self::TYPE_DELETE
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
