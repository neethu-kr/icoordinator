<?php

namespace iCoordinator\Entity;

use Carbon\Carbon;
use Datetime;
use Exception;

/**
 * @Entity
 * @Table(name="events", options={"collate"="utf8_general_ci"})
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="source_type", type="string", length=100)
 * @DiscriminatorMap({
 *  "file" = "iCoordinator\Entity\Event\FileEvent",
 *  "folder" = "iCoordinator\Entity\Event\FolderEvent",
 *  "workspace" = "iCoordinator\Entity\Event\WorkspaceEvent",
 *  "portal" = "iCoordinator\Entity\Event\PortalEvent",
 *  "permission" = "iCoordinator\Entity\Event\PermissionEvent",
 *  "user" = "iCoordinator\Entity\Event\UserEvent"
 * })
 * @HasLifecycleCallbacks
 */

abstract class Event extends AbstractEntity
{
    const ENTITY_NAME = 'entity:Event';

    const RESOURCE_ID = 'event';

    /**
     * @var Portal
     * @ManyToOne(targetEntity="Portal")
     * @JoinColumn(name="portal_id", referencedColumnName="id")
     */
    protected $portal;

    /**
     * @var string
     * @Column(type="string", length=100, nullable=false)
     */
    protected $type;

    /**
     * @var User
     * @ManyToOne(targetEntity="User", cascade={"all"})
     * @JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    /**
     * @var User
     * @ManyToOne(targetEntity="User", cascade={"all"})
     * @JoinColumn(name="created_by", referencedColumnName="id")
     */
    protected $created_by;

    /**
     * @var Carbon
     * @Column(type="datetime")
     */
    protected $created_at;

    public static function getEventTypes()
    {
        throw new \Exception('Event types are not defined');
    }

    public static function getEntityName()
    {
        return self::ENTITY_NAME;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     * @return Event
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    abstract public function setSource($source);

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
        $source = $this->getSource();
        try {
            $serializeSource = $source->jsonSerialize();
        } catch (Exception $e) {
            $serializeSource = null;
        }
        return array(
            'entity_type' => self::RESOURCE_ID,
            'id' => $this->getId(),
            'type' => $this->getType(),
            'source' => $serializeSource,
            'created_at' => ($this->getCreatedAt()) ? $this->getCreatedAt()->format(DateTime::ISO8601) : null,
            'created_by' => $this->getCreatedBy()
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
     * @param $type
     * @return Event
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    abstract public function getSource();

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
}
