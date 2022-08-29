<?php

namespace iCoordinator\Entity;

use Carbon\Carbon;
use Datetime;
use iCoordinator\Entity\Acl\AclResource;
use iCoordinator\Entity\Acl\AclResource\AclWorkspaceResource;
use iCoordinator\Permissions\Privilege\WorkspacePrivilege;
use iCoordinator\Permissions\Resource\HavingDynamicPermissionsResourceInterface;

/**
 * @Entity
 * @Table(name="workspaces", options={"collate"="utf8_general_ci"})
 * @HasLifecycleCallbacks
 */
class Workspace extends AbstractEntity implements HavingDynamicPermissionsResourceInterface
{
    const ENTITY_NAME = 'entity:Workspace';

    const RESOURCE_ID = 'workspace';

    /**
     * @var Portal
     * @ManyToOne(targetEntity="Portal")
     * @JoinColumn(name="portal_id", referencedColumnName="id")
     */
    protected $portal;

    /**
     * @var string
     * @Column(type="string", length=255, nullable=false)
     */
    protected $name;

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
     * @var bool
     * @Column(type="boolean")
     */
    protected $is_deleted = false;

    /**
     * @var bool
     * @Column(type="boolean")
     */
    protected $desktop_sync = true;

    /**
     * @var AclWorkspaceResource
     * @OneToOne(targetEntity="\iCoordinator\Entity\Acl\AclResource\AclWorkspaceResource",
     *      mappedBy="workspace", cascade={"persist"})
     */
    protected $acl_resource = null;

    public function __construct()
    {
    }
    /**
     * Implementing HavingDynamicPermissionsResourceInterface
     *
     * @param $permissionType
     * @return null|string
     */
    public static function getPrivilegeForGrantingPermission($permissionType)
    {
        return WorkspacePrivilege::getPrivilegeForGrantingPermission($permissionType);
    }

    /**
     * Implementing HavingDynamicPermissionsResourceInterface
     *
     * @return string
     */
    public static function getPrivilegeForReadingPermissions()
    {
        return WorkspacePrivilege::getPrivilegeForReadingPermissions();
    }

    public static function getEntityName()
    {
        return self::ENTITY_NAME;
    }

    /**
     * @return boolean
     */
    public function getIsDeleted()
    {
        return $this->is_deleted;
    }

    /**
     * @param boolean $is_deleted
     * @return $this
     */
    public function setIsDeleted($is_deleted)
    {
        $this->is_deleted = $is_deleted;
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
     * @return File
     */
    public function setCreatedAt($created_at)
    {
        $this->created_at = $created_at;
        return $this;
    }

    /**
     * Implementing Zend ACL ResourceInterface
     * @return string
     */
    public function getResourceId()
    {
        return self::RESOURCE_ID;
    }

    /**
     * @return AclWorkspaceResource
     */
    public function getAclResource()
    {
        return $this->acl_resource;
    }

    /**
     * @param AclResource $aclResource
     * @return $this
     */
    public function setAclResource(AclResource $aclResource)
    {
        $this->acl_resource = $aclResource;
        return $this;
    }

    /**
     * Implementing JsonSerializable interface
     * @param bool $mini
     * @param array $fields
     * @return array|mixed
     */
    public function jsonSerialize($mini = false, $fields = array())
    {
        $result = array(
            'entity_type' => self::RESOURCE_ID,
            'id' => $this->getId(),
            'name' => $this->getName()
        );
        if (!$mini) {
            $result = array_merge($result, array(
                'portal' => $this->getPortal()->jsonSerialize(true),
                'created_at' => $this->getCreatedAt()->format(DateTime::ISO8601),
                'modified_at' => $this->getModifiedAt()->format(DateTime::ISO8601),
                'desktop_sync' => $this->getDesktopSync()
            ));
        }

        //TODO refactoring for better custom fields extracting
        if (!empty($fields)) {
            foreach (array_keys($result) as $key) {
                if (!in_array($key, $fields)) {
                    unset($result[$key]);
                }
            }
        }

        return $result;
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
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
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
     * @param $portal
     * @return $this
     */
    public function setPortal($portal)
    {
        $this->portal = $portal;
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
     * @return boolean
     */
    public function getDesktopSync()
    {
        return $this->desktop_sync;
    }

    /**
     * @param boolean $desktop_sync
     * @return $this
     */
    public function setDesktopSync($desktop_sync)
    {
        $this->desktop_sync = $desktop_sync;
        return $this;
    }
}
