<?php

namespace iCoordinator\Entity;

use Carbon\Carbon;
use Datetime;
use Exception;

/**
 * @Entity
 * @Table(name="history_events", options={"collate"="utf8_general_ci"})
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="source_type", type="string", length=100)
 * @DiscriminatorMap({
 *  "file" = "iCoordinator\Entity\HistoryEvent\FileHistoryEvent",
 *  "folder" = "iCoordinator\Entity\HistoryEvent\FolderHistoryEvent",
 *  "workspace" = "iCoordinator\Entity\HistoryEvent\WorkspaceHistoryEvent",
 *  "portal" = "iCoordinator\Entity\HistoryEvent\PortalHistoryEvent",
 *  "permission" = "iCoordinator\Entity\HistoryEvent\PermissionHistoryEvent",
 *  "user" = "iCoordinator\Entity\HistoryEvent\UserHistoryEvent",
 *  "smartfolder" = "iCoordinator\Entity\HistoryEvent\SmartFolderHistoryEvent",
 *  "group" = "iCoordinator\Entity\HistoryEvent\GroupHistoryEvent",
 *  "metafield" = "iCoordinator\Entity\HistoryEvent\MetaFieldHistoryEvent",
 *  "metafieldvalue" = "iCoordinator\Entity\HistoryEvent\MetaFieldValueHistoryEvent",
 *  "invitation" = "iCoordinator\Entity\HistoryEvent\InvitationHistoryEvent"
 * })
 * @HasLifecycleCallbacks
 */

abstract class HistoryEvent extends AbstractEntity
{
    const ENTITY_NAME = 'entity:HistoryEvent';

    const RESOURCE_ID = 'historyevent';

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
     * @JoinColumn(name="group_user", referencedColumnName="id")
     */
    protected $group_user;

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
     * @Column(type="string", length=100, nullable=true)
     */
    protected $description;

    /**
     * @var string
     * @Column(type="string", length=100, nullable=true)
     */
    protected $client_id;

    /**
     * @var string
     * @Column(type="string", length=100, nullable=true)
     */
    protected $client_version;


    public static function getEventTypes()
    {
        throw new \Exception('HistoryEvent types are not defined');
    }

    public static function getEntityName()
    {
        return self::ENTITY_NAME;
    }

    /**
     * @param $portal
     * @return HistoryEvent
     */
    public function setPortal($portal)
    {
        $this->portal = $portal;
        return $this;
    }

    /**
     * @return Portal
     */
    public function getPortal()
    {
        return $this->portal;
    }

    /**
     * @param $workspace
     * @return HistoryEvent
     */
    public function setWorkspace($workspace)
    {
        $this->workspace = $workspace;
        return $this;
    }

    /**
     * @return Workspace
     */
    public function getWorkspace()
    {
        return $this->workspace;
    }

    /**
     * @param $group_user
     * @return HistoryEvent
     */
    public function setGroupUser($group_user)
    {
        $this->group_user = $group_user;
        return $this;
    }

    /**
     * @return User
     */
    public function getGroupUser()
    {
        return $this->group_user;
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
            'portal' => $this->getPortal(),
            'workspace' => $this->getWorkspace(),
            'group_user' => $this->getGroupUser(),
            'type' => $this->getType(),
            'source' => $serializeSource,
            'created_at' => ($this->getCreatedAt()) ? $this->getCreatedAt()->format(DateTime::ISO8601) : null,
            'created_by' => $this->getCreatedBy(),
            'description' => $this->getDescription()
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
     * @return HistoryEvent
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
     * @return HistoryEvent
     */
    public function setCreatedBy($created_by)
    {
        $this->created_by = $created_by;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param $description
     * @return HistoryEvent
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getClientId()
    {
        return $this->client_id;
    }

    /**
     * @param $client_id
     * @return HistoryEvent
     */
    public function setClientId($client_id)
    {
        $this->client_id = $client_id;
        return $this;
    }

    /**
     * @return string
     */
    public function getClientVersion()
    {
        return $this->client_version;
    }

    /**
     * @param $client_version
     * @return HistoryEvent
     */
    public function setClientVersion($client_version)
    {
        $this->client_version = $client_version;
        return $this;
    }
}
