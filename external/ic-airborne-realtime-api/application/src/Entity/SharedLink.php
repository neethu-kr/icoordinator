<?php

namespace iCoordinator\Entity;

use Carbon\Carbon;
use Datetime;

/**
 * @Entity
 * @Table(name="shared_links", options={"collate"="utf8_general_ci"})
 * @HasLifecycleCallbacks
 */
class SharedLink extends AbstractEntity
{
    const ENTITY_NAME = 'entity:SharedLink';

    const RESOURCE_ID = 'shared_link';

    /**
     * Access types
     */
    const ACCESS_TYPE_PUBLIC = 'public';
    const ACCESS_TYPE_PORTAL = 'portal';
    const ACCESS_TYPE_RESTRICTED = 'restricted';

    /**
     * @var Portal
     * @ManyToOne(targetEntity="Portal")
     * @JoinColumn(name="portal_id", referencedColumnName="id")
     */
    protected $portal;

    /**
     * @var string
     * @Column(type="string", length=100)
     */
    protected $access_type;

    /**
     * @var string
     * @Column(type="string", unique=true, length=32, nullable=false)
     */
    protected $token;

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
     * @ManyToOne(targetEntity="User", cascade={"persist"})
     * @JoinColumn(name="created_by", referencedColumnName="id")
     */
    protected $created_by;

    /**
     * @var File
     * @OneToOne(targetEntity="iCoordinator\Entity\File", inversedBy="shared_link")
     * @JoinColumn(name="file_id", referencedColumnName="id",  onDelete="SET NULL")
     **/
    protected $file;


    public function __construct()
    {
        // TODO: Implement __construct() method.
    }

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
            'entity_type' => 'shared_link',
            'id' => $this->getId(),
            'access_type' => $this->getAccessType(),
            'token' => $this->getToken(),
            'created_by' => ($this->getCreatedBy()) ? $this->getCreatedBy()->jsonSerialize(true) : null,
            'created_at' => ($this->getCreatedAt()) ? $this->getCreatedAt()->format(DateTime::ISO8601) : null,
            'modified_at' => ($this->getModifiedAt()) ? $this->getModifiedAt()->format(DateTime::ISO8601) : null
        );
    }

    /**
     * @return string
     */
    public function getAccessType()
    {
        return $this->access_type;
    }

    /**
     * @param $access_type
     * @return $this
     */
    public function setAccessType($access_type)
    {
        $this->access_type = $access_type;
        return $this;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param $token
     * @return $this
     */
    public function setToken($token)
    {
        $this->token = $token;
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
     * @return $this
     */
    public function setCreatedBy($created_by)
    {
        $this->created_by = $created_by;
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
