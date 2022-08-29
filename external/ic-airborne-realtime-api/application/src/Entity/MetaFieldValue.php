<?php

namespace iCoordinator\Entity;

use Carbon\Carbon;
use Datetime;

/**
 * @Entity
 * @Table(name="meta_fields_values", options={"collate"="utf8_general_ci"})
 * @HasLifecycleCallbacks
 */
class MetaFieldValue extends AbstractEntity
{
    const ENTITY_NAME = 'entity:MetaFieldValue';

    const RESOURCE_ID = 'meta_field_value';

    /**
     * @var Portal
     * @ManyToOne(targetEntity="Portal")
     * @JoinColumn(name="portal_id", referencedColumnName="id")
     */
    protected $portal;

    /**
     * @var MetaField
     * @ManyToOne(targetEntity="MetaField")
     * @JoinColumn(name="meta_field_id", referencedColumnName="id", nullable=false)
     */
    public $meta_field;

    /**
     * @var File
     * @ManyToOne(targetEntity="File")
     * @JoinColumn(name="file_id", referencedColumnName="id", nullable=false)
     */
    protected $resource;

    /**
     * @var string
     * @Column(type="text", nullable=true)
     */
    public $value = null;

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

    /**
     * @return File
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @param $resource
     * @return $this
     */
    public function setResource($resource)
    {
        $this->resource = $resource;
        return $this;
    }

    public function jsonSerialize($mini = false)
    {
        $result = array(
            'entity_type' => 'meta_field_value',
            'id' => $this->getId(),
            'meta_field' => $this->getMetaField()->jsonSerialize(),
            'value' => $this->getValue(),
            'created_at' => ($this->getCreatedAt()) ? $this->getCreatedAt()->format(DateTime::ISO8601) : null,
            'modified_at' => ($this->getModifiedAt()) ? $this->getModifiedAt()->format(DateTime::ISO8601) : null
        );

        if (!$mini) {
            $result = array_merge($result, array(
                'resource' => $this->getResource()->jsonSerialize(true)
            ));
        }

        return $result;
    }
}
