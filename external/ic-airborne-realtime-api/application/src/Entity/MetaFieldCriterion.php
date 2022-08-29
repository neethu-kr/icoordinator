<?php

namespace iCoordinator\Entity;

use Carbon\Carbon;
use Datetime;

/**
 * @Entity
 * @Table(name="meta_fields_criteria", options={"collate"="utf8_general_ci"})
 * @HasLifecycleCallbacks
 */
class MetaFieldCriterion extends AbstractEntity
{
    const ENTITY_NAME = 'entity:MetaFieldCriterion';

    const RESOURCE_ID = 'meta_field_criterion';

    const CONDITION_EQUALS = '=';
    const CONDITION_NOT_EQUALS = '!=';
    const CONDITION_LESS_OR_EQUALS = '<=';
    const CONDITION_GREATER_OR_EQUALS = '>=';
    const CONDITION_LESS = '<';
    const CONDITION_GREATER = '>';
    const CONDITION_CONTAINS = 'contains';
    const CONDITION_NOT_CONTAINS = 'notContains';
    const CONDITION_STARTS_WITH = 'startsWith';
    const CONDITION_NOT_STARTS_WITH = 'notStartsWith';

    /**
     * @var Portal
     * @ManyToOne(targetEntity="Portal")
     * @JoinColumn(name="portal_id", referencedColumnName="id")
     */
    protected $portal;

    /**
     * @var MetaField
     * @ManyToOne(targetEntity="MetaField")
     * @JoinColumn(name="meta_field_id", referencedColumnName="id")
     */
    protected $meta_field;

    /**
     * @var SmartFolder
     * @ManyToOne(targetEntity="SmartFolder")
     * @JoinColumn(name="smart_folder_id", referencedColumnName="id")
     */
    protected $smart_folder;

    /**
     * @var string
     * @Column(type="string", length=30)
     */
    protected $condition_type = null;

    /**
     * @var string
     * @Column(type="text", nullable=true)
     */
    protected $value = null;

    /**
     * @var Carbon
     * @Column(type="datetime")
     */
    protected $created_at;

    /**
     * @var Carbon
     * @Column(type="datetime")
     */
    protected $modified_at;


    public function __construct()
    {
        // TODO: Implement __construct() method.
    }

    public static function getEntityName()
    {
        return self::ENTITY_NAME;
    }

    /**
     * @PrePersist
     * @PreUpdate
     */
    public function updatedTimestamps()
    {
        $this->setModifiedAt(new Carbon());

        if ($this->getCreatedAt() == null) {
            $this->setCreatedAt(new Carbon());
        }
        return $this;
    }

    /**
     * @return \Carbon\Carbon
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * @param \Carbon\Carbon $created_at
     * @return $this
     */
    public function setCreatedAt($created_at)
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function getResourceId()
    {
        return self::RESOURCE_ID;
    }

    public function jsonSerialize()
    {
        return array(
            'entity_type' => $this->getResourceId(),
            'id' => $this->getId(),
            'meta_field' => $this->getMetaField()->jsonSerialize(),
            'smart_folder' => $this->getSmartFolder()->jsonSerialize(true),
            'condition_type' => $this->getConditionType(),
            'value' => $this->getValue(),
            'created_at' => ($this->getCreatedAt()) ? $this->getCreatedAt()->format(DateTime::ISO8601) : null,
            'modified_at' => ($this->getModifiedAt()) ? $this->getModifiedAt()->format(DateTime::ISO8601) : null
        );
    }

    /**
     * @return MetaField
     */
    public function getMetaField()
    {
        return $this->meta_field;
    }

    /**
     * @param $meta_field
     * @return $this
     */
    public function setMetaField($meta_field)
    {
        $this->meta_field = $meta_field;
        return $this;
    }

    /**
     * @return SmartFolder
     */
    public function getSmartFolder()
    {
        return $this->smart_folder;
    }

    /**
     * @param $smartFolder
     * @return $this
     */
    public function setSmartFolder($smartFolder)
    {
        $this->smart_folder = $smartFolder;
        return $this;
    }

    /**
     * @return string
     */
    public function getConditionType()
    {
        return $this->condition_type;
    }

    /**
     * @param $conditionType
     * @return $this
     */
    public function setConditionType($conditionType)
    {
        $this->condition_type = $conditionType;
        return $this;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @return \Carbon\Carbon
     */
    public function getModifiedAt()
    {
        return $this->modified_at;
    }

    /**
     * @param \Carbon\Carbon $modified_at
     * @return $this
     */
    public function setModifiedAt($modified_at)
    {
        $this->modified_at = $modified_at;
        return $this;
    }
}
