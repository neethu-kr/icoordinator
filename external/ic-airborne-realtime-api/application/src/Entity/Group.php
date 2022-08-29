<?php

namespace iCoordinator\Entity;

use Carbon\Carbon;
use Datetime;
use Doctrine\Common\Collections\ArrayCollection;
use iCoordinator\Entity\Acl\AclRole\AclGroupRole;
use iCoordinator\Permissions\Role\HavingAclRoleInterface;

/**
 * @Entity
 * @Table(name="groups", options={"collate"="utf8_general_ci"})
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="scope_type", type="string", length=30)
 * @DiscriminatorMap({
 *  "workspace" = "iCoordinator\Entity\Group\WorkspaceGroup",
 *  "portal" = "iCoordinator\Entity\Group\PortalGroup"
 * })
 * @HasLifecycleCallbacks
 */
abstract class Group extends AbstractEntity implements HavingAclRoleInterface
{
    const ENTITY_NAME = 'entity:Group';

    const RESOURCE_ID = 'group';

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
     * @var ArrayCollection
     * @OneToMany(targetEntity="GroupMembership", mappedBy="group", fetch="EXTRA_LAZY")
     */
    protected $group_memberships;

    /**
     * @var AclGroupRole
     * @OneToOne(targetEntity="\iCoordinator\Entity\Acl\AclRole\AclGroupRole", mappedBy="group", cascade={"remove"})
     */
    protected $acl_role = null;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    public static function getEntityName()
    {
        return self::ENTITY_NAME;
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
     * @return AbstractEntity
     */
    abstract public function getScope();

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

    public function getAclRole()
    {
        return $this->acl_role;
    }

    public function setAclRole($aclRole)
    {
        $this->acl_role = $aclRole;
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
                'created_at' => $this->getCreatedAt()->format(DateTime::ISO8601),
                'modified_at' => $this->getModifiedAt()->format(DateTime::ISO8601),
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
     * @return File
     */
    public function setName($name)
    {
        $this->name = $name;
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
     * @return File
     */
    public function setModifiedAt($modified_at)
    {
        $this->modified_at = $modified_at;
        return $this;
    }
}
