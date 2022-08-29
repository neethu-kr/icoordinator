<?php

namespace iCoordinator\Entity\Acl;

use Carbon\Carbon;
use Doctrine\Common\Collections\ArrayCollection;
use iCoordinator\Entity\Acl\AclResource\AclFileResource;
use iCoordinator\Entity\Acl\AclResource\AclPortalResource;
use iCoordinator\Entity\Acl\AclResource\AclWorkspaceResource;
use iCoordinator\Permissions\Resource\HavingDynamicPermissionsResourceInterface;
use Laminas\Stdlib\JsonSerializable;

/**
 * @Entity
 * @Table(
 *  name="acl_resources",
 *  uniqueConstraints={
 *      @UniqueConstraint(name="entity_ref", columns={"entity_id", "entity_type"})
 *  },
 *  options={"collate"="utf8_general_ci"}
 * )
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="entity_type", type="string", length=10)
 * @DiscriminatorMap({
 *  "portal" = "\iCoordinator\Entity\Acl\AclResource\AclPortalResource",
 *  "workspace" = "\iCoordinator\Entity\Acl\AclResource\AclWorkspaceResource",
 *  "file" = "\iCoordinator\Entity\Acl\AclResource\AclFileResource",
 * })
 *
 * @HasLifecycleCallbacks
 */
abstract class AclResource implements JsonSerializable
{
    const ENTITY_NAME = 'entity:Acl\AclResource';

    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var int
     * @Column(type="integer")
     */
    protected $entity_id;

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
     * @var ArrayCollection
     * @OneToMany(targetEntity="AclPermission", mappedBy="AclRole", fetch="EXTRA_LAZY")
     */
    protected $acl_permissions;

    public function __construct()
    {
        $this->acl_permissions = new ArrayCollection();
    }

    public static function getEntityName()
    {
        return self::ENTITY_NAME;
    }

    /**
     * @param $entityType
     * @return string
     * @throws \Exception
     */
    public static function getAclResourceEntityName($entityType)
    {
        switch ($entityType) {
            case AclPortalResource::ACL_RESOURCE_ENTITY_TYPE:
                return AclPortalResource::ENTITY_NAME;
                break;
            case AclWorkspaceResource::ACL_RESOURCE_ENTITY_TYPE:
                return AclWorkspaceResource::ENTITY_NAME;
                break;
            case AclFileResource::ACL_RESOURCE_ENTITY_TYPE:
                return AclFileResource::ENTITY_NAME;
                break;
            default:
                throw new \Exception('Incorrect entity_type: ' . $entityType);
                break;
        }
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getEntityId()
    {
        return $this->entity_id;
    }

    /**
     * @param int $entity_id
     * @return AclResource
     */
    public function setEntityId($entity_id)
    {
        $this->entity_id = $entity_id;
        return $this;
    }

    /**
     * @return Carbon
     */
    public function getModifiedAt()
    {
        return $this->modified_at;
    }

    /**
     * @param Carbon $modified_at
     * @return Carbon
     */
    public function setModifiedAt($modified_at)
    {
        $this->modified_at = $modified_at;
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
     * @return Carbon
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * @param Carbon $created_at
     * @return Carbon
     */
    public function setCreatedAt($created_at)
    {
        $this->created_at = $created_at;
        return $this;
    }

    /**
     * @return string
     */
    abstract public function getAclResourceEntityType();


    /**
     * @return HavingDynamicPermissionsResourceInterface
     */
    abstract public function getResource();

    abstract public function jsonSerialize();
}
