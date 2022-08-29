<?php

namespace iCoordinator\Entity\Acl;

use Carbon\Carbon;
use iCoordinator\Entity\AbstractEntity;
use iCoordinator\Permissions\BitMask;

/**
 * @Entity
 * @Table(name="acl_permissions", options={"collate"="utf8_general_ci"})
 * @HasLifecycleCallbacks
 */
class AclPermission extends AbstractEntity
{
    const ENTITY_NAME = 'entity:Acl\AclPermission';

    const RESOURCE_ID = 'permission';

    /**
     * @var \iCoordinator\Entity\Portal
     *
     * @ManyToOne(targetEntity="\iCoordinator\Entity\Portal")
     * @JoinColumn(name="portal_id", referencedColumnName="id", nullable=false)
     */
    protected $portal;


    /**
     * @var AclRole
     * @ManyToOne(targetEntity="AclRole", cascade={"persist"})
     * @JoinColumn(name="acl_role_id", referencedColumnName="id")
     */
    protected $acl_role;

    /**
     * @var AclResource
     * @ManyToOne(targetEntity="AclResource", cascade={"persist"})
     * @JoinColumn(name="acl_resource_id", referencedColumnName="id")
     */
    protected $acl_resource;

    /**
     * @var int
     * @Column(type="integer", nullable=false)
     */
    protected $bit_mask;

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
     * @var \iCoordinator\Entity\User
     * @ManyToOne(targetEntity="\iCoordinator\Entity\User")
     * @JoinColumn(name="granted_by", referencedColumnName="id")
     */
    protected $granted_by;

    /**
     * @var bool
     * @Column(type="boolean")
     */
    protected $is_deleted = false;

    public static function getEntityName()
    {
        return self::ENTITY_NAME;
    }

    /**
     * @return \iCoordinator\Entity\Portal
     */
    public function getPortal()
    {
        return $this->portal;
    }

    /**
     * @param \iCoordinator\Entity\Portal $portal
     * @return $this
     */
    public function setPortal($portal)
    {
        $this->portal = $portal;
        return $this;
    }

    /**
     * @return \iCoordinator\Entity\User
     */
    public function getGrantedBy()
    {
        return $this->granted_by;
    }

    /**
     * @param \iCoordinator\Entity\User $granted_by
     * @return AclPermission
     */
    public function setGrantedBy($granted_by)
    {
        $this->granted_by = $granted_by;
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
     */
    public function setModifiedAt($modified_at)
    {
        $this->modified_at = $modified_at;
    }

    /**
     * @return boolean
     */
    public function isIsDeleted()
    {
        return $this->is_deleted;
    }

    /**
     * @param boolean $is_deleted
     * @return AclPermission
     */
    public function setIsDeleted($is_deleted)
    {
        $this->is_deleted = $is_deleted;
        return $this;
    }

    /**
     * @param $actions
     * @return $this
     * @throws \Exception
     */
    public function setActions($actions)
    {
        if (!$this->getAclResource()) {
            throw new \Exception("Can't set permissions when permission resource is not set");
        }

        if (empty($actions)) {
            $this->setBitMask(0);
        } else {
            $bitMask = new BitMask($this->getAclResource()->getAclResourceEntityType());
            $this->setBitMask($bitMask->getBitMask($actions));
        }

        return $this;
    }

    /**
     * @return AclResource
     */
    public function getAclResource()
    {
        return $this->acl_resource;
    }

    /**
     * @param AclResource $acl_resource
     * @return $this
     */
    public function setAclResource($acl_resource)
    {
        $this->acl_resource = $acl_resource;
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

    /**
     * Implementing Zend ACL ResourceInterface
     * @return string
     */
    public function getResourceId()
    {
        return self::RESOURCE_ID;
    }

    public function jsonSerialize()
    {
        return array(
            'entity_type' => self::RESOURCE_ID,
            'id' => $this->getId(),
            'grant_to' => $this->getAclRole()->jsonSerialize(),
            'resource' => $this->getAclResource()->jsonSerialize(),
            'actions' => $this->getActions()
        );
    }

    /**
     * @return AclRole
     */
    public function getAclRole()
    {
        return $this->acl_role;
    }

    /**
     * @param AclRole $acl_role
     * @return $this
     */
    public function setAclRole($acl_role)
    {
        $this->acl_role = $acl_role;
        return $this;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getActions()
    {
        if (!$this->getAclResource()) {
            throw new \Exception("Can't get actions when permission resource is not set");
        }
        $bitMask = new BitMask($this->getAclResource()->getAclResourceEntityType());
        return $bitMask->getPermissions($this->getBitMask());
    }

    /**
     * @return int
     */
    public function getBitMask()
    {
        return $this->bit_mask;
    }

    /**
     * @param int $bitMask
     */
    public function setBitMask($bitMask)
    {
        $this->bit_mask = $bitMask;
    }
}
