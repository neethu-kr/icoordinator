<?php

namespace iCoordinator\Entity;

use Carbon\Carbon;
use Datetime;
use Laminas\Stdlib\JsonSerializable;

/**
 * @Entity
 * @Table(name="file_uploads", options={"collate"="utf8_general_ci"})
 * @HasLifecycleCallbacks
 */
class FileUpload implements JsonSerializable
{
    const ENTITY_NAME = 'entity:FileUpload';
    const RESOURCE_ID = 'file_uplaod';

    /**
     * @var string
     * @Id
     * @Column(type="string", length=32)
     */
    private $id;

    /**
     * @var bigint
     * @Column(type="bigint", nullable=false)
     */
    private $offset;

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
    protected $expires_at;

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
     * @var string
     * @Column(type="string", length=256)
     */
    protected $hash = null;

    public function __construct()
    {
        $this->expires_at = Carbon::now()->addDay();
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     * @return FileUpload
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @param int $offset
     * @return FileUpload
     */
    public function setOffset($offset)
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * @return User
     */
    public function getCreatedBy()
    {
        return $this->created_by;
    }

    /**
     * @param User $created_by
     * @return FileUpload
     */
    public function setCreatedBy($created_by)
    {
        $this->created_by = $created_by;
        return $this;
    }

    /**
     * @return Carbon
     */
    public function getExpiresAt()
    {
        return $this->expires_at;
    }

    /**
     * @param Carbon $expires_at
     * @return FileUpload
     */
    public function setExpiresAt($expires_at)
    {
        $this->expires_at = $expires_at;
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
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @param string $hash
     * @return File
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
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

    public function jsonSerialize()
    {
        return array(
            'entity_type' => 'file_upload',
            'id' => $this->getId(),
            'offset' => $this->getOffset(),
            'expires_at' => ($this->getExpiresAt()) ? $this->getExpiresAt()->format(DateTime::ISO8601) : null,
            'hash' => $this->getHash()
        );
    }
}
