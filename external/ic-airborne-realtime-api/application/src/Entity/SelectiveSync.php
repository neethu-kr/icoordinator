<?php

namespace iCoordinator\Entity;

use Carbon\Carbon;
use Datetime;

/**
 * @Entity
 * @Table(name="selective_sync", options={"collate"="utf8_general_ci"})
 * @HasLifecycleCallbacks
 */

class SelectiveSync extends AbstractEntity
{
    const ENTITY_NAME = 'entity:SelectiveSync';

    const RESOURCE_ID = 'selective_sync';

    /**
     * @var File
     * @ManyToOne(targetEntity="File", inversedBy="lock")
     * @JoinColumn(name="file_id", referencedColumnName="id")
     */
    protected $file = null;

    /**
     * @var User
     * @ManyToOne(targetEntity="User")
     * @JoinColumn(name="created_by", referencedColumnName="id")
     */
    protected $created_by;

    /**
     * @var Carbon
     * @Column(type="datetime")
     */
    protected $created_at;

    protected $inherited = false;

    public static function getEntityName()
    {
        return self::ENTITY_NAME;
    }

    /**
     * @PrePersist
     */
    public function updatedTimestamps()
    {
        if ($this->getCreatedAt() == null) {
            $this->setCreatedAt(new Carbon());
        }
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
     */
    public function setCreatedAt($created_at)
    {
        $this->created_at = $created_at;
    }

    public function getResourceId()
    {
        return self::RESOURCE_ID;
    }

    public function jsonSerialize()
    {
        return array(
            'entity_type' => self::RESOURCE_ID,
            'id' => $this->getId(),
            'file' => $this->getFile()->jsonSerialize(true),
            'created_at' => ($this->getCreatedAt()) ? $this->getCreatedAt()->format(DateTime::ISO8601) : null,
            'created_by' => $this->getCreatedBy()
        );
    }

    /**
     * @return File
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param $file
     * @return $this
     */
    public function setFile($file)
    {
        $this->file = $file;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCreatedBy()
    {
        return $this->created_by;
    }

    /**
     * @param mixed $created_by
     * @return Event
     */
    public function setCreatedBy($created_by)
    {
        $this->created_by = $created_by;
        return $this;
    }

    public function getInherited()
    {
        return $this->inherited;
    }
    public function setInherited($inherited)
    {
        $this->inherited = $inherited;
    }
}
