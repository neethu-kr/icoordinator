<?php

namespace iCoordinator\Entity\HistoryEvent;

use iCoordinator\Entity\HistoryEvent;
use iCoordinator\Entity\MetaField;

/**
 * @Entity
 */
class MetaFieldHistoryEvent extends HistoryEvent
{
    const ENTITY_NAME = 'entity:HistoryEvent\MetaFieldHistoryEvent';

    const TYPE_CREATE = 'METAFIELD_CREATE';
    const TYPE_CHANGE_NAME = 'METAFIELD_CHANGE_NAME';
    const TYPE_DELETE = 'METAFIELD_DELETE';
    const TYPE_VALUES_EDIT = 'METAFIELD_VALUES_EDIT';
    const TYPE_TAG_ASSIGN = 'METAFIELD_TAG_ASSIGN';
    const TYPE_TAG_CHANGE = 'METAFIELD_TAG_CHANGE';
    const TYPE_TAG_REMOVE = 'METAFIELD_TAG_REMOVE';

    /**
     * @ManyToOne(targetEntity="iCoordinator\Entity\MetaField")
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
            self::TYPE_CHANGE_NAME,
            self::TYPE_DELETE,
            self::TYPE_VALUES_EDIT,
            self::TYPE_TAG_ASSIGN,
            self::TYPE_TAG_CHANGE,
            self::TYPE_TAG_REMOVE
        );
    }

    public static function getEntityName()
    {
        return self::ENTITY_NAME;
    }

    /**
     * @return \iCoordinator\Entity\MetaField
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
        if (!$source instanceof MetaField) {
            throw new \InvalidArgumentException(
                '$source should be instance of iCoordinator\\Entity\\MetaField'
            );
        }
        $this->source = $source;
        return $this;
    }
}
