<?php

namespace iCoordinator\Entity;

use Carbon\Carbon;
use Datetime;

/**
 * @Entity
 * @Table(name="file_versions", options={"collate"="utf8_general_ci"})
 * @HasLifecycleCallbacks
 */
class FileVersion extends AbstractEntity
{
    const ENTITY_NAME = 'entity:FileVersion';

    const RESOURCE_ID = 'file_version';

    /**
     * @var File
     * @ManyToOne(targetEntity="File")
     * @JoinColumn(name="file_id", referencedColumnName="id")
     */
    protected $file;

    /**
     * @var string
     * @Column(type="string", length=255, nullable=false)
     */
    protected $name;

    /**
     * @var bigint
     * @Column(type="bigint", nullable=false)
     */
    protected $size;

    /**
     * @var string
     * @Column(type="string", length=255, nullable=true)
     */
    protected $storage_path;

    /**
     * @var
     * @Column(type="string", length=24, nullable=true)
     */
    protected $iv;

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
     * @var User
     * @ManyToOne(targetEntity="User")
     * @JoinColumn(name="modified_by", referencedColumnName="id")
     */
    protected $modified_by;

    /**
     * @var string
     * @Column(type="text", nullable=true)
     */
    protected $comment = null;

    public static function getEntityName()
    {
        return self::ENTITY_NAME;
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
     * @return string
     */
    public function getStoragePath()
    {
        return $this->storage_path;
    }

    /**
     * @param $storage_path
     * @return $this
     */
    public function setStoragePath($storage_path)
    {
        $this->storage_path = $storage_path;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIv()
    {
        return $this->iv;
    }

    /**
     * @param mixed $iv
     * @return FileVersion
     */
    public function setIv($iv)
    {
        $this->iv = $iv;
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
            'entity_type' => 'file_version',
            'id' => $this->getId(),
            'name' => $this->getName(),
            'size' => $this->getSize(),
            'modified_by' => $this->getModifiedBy()->jsonSerialize(true),
            'created_at' => ($this->getCreatedAt()) ? $this->getCreatedAt()->format(DateTime::ISO8601) : null,
            'modified_at' => ($this->getModifiedAt()) ? $this->getModifiedAt()->format(DateTime::ISO8601) : null,
            'comment' => $this->getComment()
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @param $size
     * @return $this
     */
    public function setSize($size)
    {
        $this->size = $size;
        return $this;
    }

    /**
     * @return User
     */
    public function getModifiedBy()
    {
        return $this->modified_by;
    }

    /**
     * @param $modified_by
     * @return $this
     */
    public function setModifiedBy($modified_by)
    {
        $this->modified_by = $modified_by;
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
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param $comment
     * @return $this
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
        return $this;
    }
}
