<?php

namespace iCoordinator\Entity;

use Carbon\Carbon;
use Doctrine\Common\Collections\ArrayCollection;
use iCoordinator\Entity\Acl\AclResource;
use iCoordinator\Entity\Acl\AclResource\AclPortalResource;
use iCoordinator\Entity\Portal\AllowedClient;
use iCoordinator\Permissions\Privilege\PortalPrivilege;
use iCoordinator\Permissions\Resource\HavingDynamicPermissionsResourceInterface;
use iCoordinator\Permissions\Resource\HavingOwnerResourceInterface;

/**
 * @Entity
 * @Table(name="portals", options={"collate"="utf8_general_ci"})
 * @HasLifecycleCallbacks
 */
class Portal extends AbstractEntity implements HavingDynamicPermissionsResourceInterface, HavingOwnerResourceInterface
{
    const ENTITY_NAME = 'entity:Portal';

    const RESOURCE_ID = 'portal';

    /**
     * @var string
     * @Column(type="string", length=255, nullable=false)
     */
    protected $name;

    /**
     * @var string
     * @Column(type="uuid", unique=true, length=36, nullable=true)
     */
    protected $uuid;

    /**
     * @var User
     * @ManyToOne(targetEntity="User")
     * @JoinColumn(name="owned_by", referencedColumnName="id")
     */
    protected $owned_by;

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
     * @var AclPortalResource
     * @OneToOne(targetEntity="\iCoordinator\Entity\Acl\AclResource\AclPortalResource", mappedBy="portal")
     */
    protected $acl_resource = null;

    /**
     * @var Subscription
     * @OneToOne(targetEntity="\iCoordinator\Entity\Subscription", mappedBy="portal", cascade={"persist", "remove"})
     **/
    protected $subscription;

    /**
     * @var AllowedClient
     * @OneToMany(
     *     targetEntity="\iCoordinator\Entity\Portal\AllowedClient",
     *      mappedBy="portal",
     *      indexBy="uuid",
     *      cascade={"persist", "remove"}
     *     )
     */
    protected $allowed_clients;

    protected $state;

    public function __construct()
    {
        $this->allowed_clients = new ArrayCollection();
        $this->state = array();
    }

    /**
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * @param string $uuid
     * @return Portal
     */
    public function setUuid($uuid)
    {
        $this->uuid = $uuid;
        return $this;
    }

    /**
     * Implementing HavingDynamicPermissionsResourceInterface
     *
     * @param $permissionType
     * @return null|string
     */
    public static function getPrivilegeForGrantingPermission($permissionType)
    {
        return PortalPrivilege::getPrivilegeForGrantingPermission($permissionType);
    }

    /**
     * Implementing HavingDynamicPermissionsResourceInterface
     *
     * @return string
     */
    public static function getPrivilegeForReadingPermissions()
    {
        return PortalPrivilege::getPrivilegeForReadingPermissions();
    }

    /**
     * @return string
     */
    public static function getEntityName()
    {
        return self::ENTITY_NAME;
    }

    /**
     * @return User
     */
    public function getOwnedBy()
    {
        return $this->owned_by;
    }

    /**
     * @param $owned_by
     * @return $this
     */
    public function setOwnedBy($owned_by)
    {
        $this->owned_by = $owned_by;
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
     * @param $modified_at
     * @return $this
     */
    public function setModifiedAt($modified_at)
    {
        $this->modified_at = $modified_at;
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
     * @return AclPortalResource
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
     * @param $created_at
     * @return $this
     */
    public function setCreatedAt($created_at)
    {
        $this->created_at = $created_at;
        return $this;
    }

    /**
     * @return Subscription
     */
    public function getSubscription()
    {
        return $this->subscription;
    }

    /**
     * @param $subscription
     * @return $this
     */
    public function setSubscription($subscription)
    {
        $this->subscription = $subscription;
        return $this;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return array(
            'entity_type' => 'portal',
            'id' => $this->getId(),
            'name' => $this->getName(),
            'subscription' => $this->getSubscription() ? $this->getSubscription()->jsonSerialize(true) : null,
            'state' => $this->getState()
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
     * @return ArrayCollection
     */
    public function getAllowedClients()
    {
        return $this->allowed_clients->toArray();
    }

    /**
     * @param ArrayCollection
     * @return $this
     */
    public function setAllowedClients($allowed_clients)
    {
        $this->allowed_clients = $allowed_clients;
        return $this;
    }

    public function addAllowedClient(AllowedClient $allowed_client)
    {
        $this->allowed_clients[$allowed_client->getUuid()] = $allowed_client;
    }

    public function getAllowedClient($uuid)
    {
        if (!isset($this->allowed_clients[$uuid])) {
            return null;
        }

        return $this->allowed_clients[$uuid];
    }

    public function setState($state)
    {
        $this->state = $state;
        return $this;
    }

    public function getState()
    {
        return $this->state;
    }
}
