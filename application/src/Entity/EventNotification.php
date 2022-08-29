<?php

namespace iCoordinator\Entity;

use Carbon\Carbon;
use Datetime;

/**
 * @Entity
 * @Table(name="event_notifications", options={"collate"="utf8_general_ci"})
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="source_type", type="string", length=100)
 * @DiscriminatorMap({
 *  "file" = "iCoordinator\Entity\EventNotification\FileEventNotification"
 * })
 * @HasLifecycleCallbacks
 */

abstract class EventNotification extends AbstractEntity
{
    const ENTITY_NAME = 'entity:EventNotification';

    const RESOURCE_ID = 'eventnotification';

    /**
     * @var Portal
     * @ManyToOne(targetEntity="Portal")
     * @JoinColumn(name="portal_id", referencedColumnName="id")
     */
    protected $portal;

    /**
     * @var Workspace
     * @ManyToOne(targetEntity="Workspace")
     * @JoinColumn(name="workspace_id", referencedColumnName="id")
     */
    protected $workspace;


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

    /**
     * @var string
     * @Column(type="string", length=128)
     */
    protected $brand;

    /**
     * @var bool
     * @Column(type="boolean")
     */
    protected $instant_notification = false;

    public static function getEventNotificationTypes()
    {
        throw new \Exception('Event notification types are not defined');
    }

    public static function getEntityName()
    {
        return self::ENTITY_NAME;
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
        return array(
            'entity_type' => self::RESOURCE_ID,
            'id' => $this->getId(),
            'type' => $this->getType(),
            'source' => $this->getSource()->jsonSerialize(),
            'created_at' => ($this->getCreatedAt()) ? $this->getCreatedAt()->format(DateTime::ISO8601) : null,
            'created_by' => $this->getCreatedBy(),
            'instant_notification' => $this->getInstantNotification()
        );
    }

    /**
     * @return \iCoordinator\Entity\Portal
     */
    public function getPortal()
    {
        return $this->portal;
    }

    /**
     * @param $portal
     * @returns EventNotification
     */
    public function setPortal($portal)
    {
        $this->portal = $portal;
        return $this;
    }

    /**
     * @return \iCoordinator\Entity\Workspace
     */
    public function getWorkspace()
    {
        return $this->workspace;
    }

    /**
     * @param $workspace
     * @returns EventNotification
     */
    public function setWorkspace($workspace)
    {
        $this->workspace = $workspace;
        return $this;
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
     * @return EventNotification
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
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     * @return EventNotification
     */
    public function setUser($user)
    {
        $this->user = $user;
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
     * @return EventNotification
     */
    public function setCreatedBy($created_by)
    {
        $this->created_by = $created_by;
        return $this;
    }

    /**
 * @return string
 */
    public function getBrand()
    {
        return $this->brand;
    }

    /**
     * @param $brand
     * @return $this
     */
    public function setBrand($brand)
    {
        $this->brand = $brand;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getInstantNotification()
    {
        return $this->instant_notification;
    }

    /**
     * @param $instant_notification
     * @return $this
     */
    public function setInstantNotification($instant_notification)
    {
        $this->instant_notification = $instant_notification;
        return $this;
    }
}
