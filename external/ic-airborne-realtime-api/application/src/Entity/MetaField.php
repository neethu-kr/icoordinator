<?php

namespace iCoordinator\Entity;

use Carbon\Carbon;
use Datetime;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table(name="meta_fields", options={"collate"="utf8_general_ci"})
 * @HasLifecycleCallbacks
 */
class MetaField extends AbstractEntity
{
    const ENTITY_NAME = 'entity:MetaField';

    const RESOURCE_ID = 'meta_field';

    /**
     * Meta fields types
     */
    const TYPE_STRING = 'string';
    const TYPE_NUMBER = 'number';
    const TYPE_DATE = 'date';
    const TYPE_LIST = 'list';

    /**
     * @var Portal
     * @ManyToOne(targetEntity="Portal")
     * @JoinColumn(name="portal_id", referencedColumnName="id")
     */
    protected $portal;

    /**
     * @var string
     * @Column(type="string", length=255)
     */
    protected $name;

    /**
     * @var string
     * @Column(type="string", length=100)
     */
    protected $type;

    /**
     * @var string
     * @Column(type="text", nullable=true)
     */
    protected $options = null;

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

    /**
     * @var ArrayCollection
     * @OneToMany(
     *  targetEntity="iCoordinator\Entity\MetaFieldValue",
     *  mappedBy="meta_field",
     *  fetch="EXTRA_LAZY",
     *  cascade={"all"}
     * )
     */
    protected $meta_fields_values;


    public function __construct()
    {
        // TODO: Implement __construct() method.
    }

    public static function getEntityName()
    {
        return self::ENTITY_NAME;
    }

    /**
     * @return Portal
     */
    public function getPortal()
    {
        return $this->portal;
    }

    /**
     * @param $portal
     * @return $this
     */
    public function setPortal($portal)
    {
        $this->portal = $portal;
        return $this;
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
     * @return $this;
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
            'entity_type' => 'meta_field',
            'id' => $this->getId(),
            'type' => $this->getType(),
            'name' => $this->getName(),
            'options' => $this->getOptions()->toArray(),
            'created_at' => ($this->getCreatedAt()) ? $this->getCreatedAt()->format(DateTime::ISO8601) : null,
            'modified_at' => ($this->getModifiedAt()) ? $this->getModifiedAt()->format(DateTime::ISO8601) : null
        );
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return array|ArrayCollection
     */
    public function getOptions()
    {
        if (!empty($this->options)) {
            return new ArrayCollection(explode(PHP_EOL, $this->options));
        }
        return new ArrayCollection();
    }

    /**
     * @param $options
     * @return $this
     * @throws \Exception
     */
    public function setOptions($options)
    {
        if (!empty($options)) {
            if ($this->getType() != self::TYPE_LIST) {
                throw new \Exception('getOptions method should be used only for meta fields with type "list"');
            } else {
                $this->options = implode(PHP_EOL, $options);
            }
        } else {
            $this->options = $options;
        }

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
     * @return $this;
     */
    public function setModifiedAt($modified_at)
    {
        $this->modified_at = $modified_at;
        return $this;
    }
}
