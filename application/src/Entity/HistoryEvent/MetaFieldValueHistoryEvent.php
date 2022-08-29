<?php

namespace iCoordinator\Entity\HistoryEvent;

use iCoordinator\Entity\HistoryEvent;
use iCoordinator\Entity\MetaFieldValue;

/**
 * @Entity
 */
class MetaFieldValueHistoryEvent extends HistoryEvent
{
    const ENTITY_NAME = 'entity:HistoryEvent\MetaFieldValueHistoryEvent';

    const TYPE_VALUE_ASSIGN = 'METAFIELD_VALUE_ASSIGN';
    const TYPE_VALUE_CHANGE = 'METAFIELD_VALUE_CHANGE';
    const TYPE_VALUE_REMOVE = 'METAFIELD_VALUE_REMOVE';

    /**
     * @ManyToOne(targetEntity="iCoordinator\Entity\MetaFieldValue")
     * @JoinColumn(name="source_id", referencedColumnName="id")
     **/
    protected $source;

    /**
     * @return array
     */
    public static function getEventTypes()
    {
        return array(
            self::TYPE_VALUE_ASSIGN,
            self::TYPE_VALUE_CHANGE,
            self::TYPE_VALUE_REMOVE
        );
    }

    public static function getEntityName()
    {
        return self::ENTITY_NAME;
    }

    /**
     * @return \iCoordinator\Entity\MetaFieldValue
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
        if (!$source instanceof MetaFieldValue) {
            throw new \InvalidArgumentException(
                '$source should be instance of iCoordinator\\Entity\\MetaFieldValue'
            );
        }
        $this->source = $source;
        return $this;
    }
}
