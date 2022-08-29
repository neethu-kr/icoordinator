<?php

namespace iCoordinator\Entity;

use Carbon\Carbon;
use Datetime;

/**
 * @Entity
 * @Table(name="group_memberships", options={"collate"="utf8_general_ci"})
 * @HasLifecycleCallbacks
 */
class GroupMembership extends AbstractEntity
{
    const ENTITY_NAME = 'entity:GroupMembership';

    const RESOURCE_ID = 'group_membership';

    /**
     * @var User
     * @ManyToOne(targetEntity="User")
     * @JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    /**
     * @var Group
     * @ManyToOne(targetEntity="Group")
     * @JoinColumn(name="group_id", referencedColumnName="id")
     */
    protected $group;

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


    public function __construct()
    {
    }

    public static function getEntityName()
    {
        return self::ENTITY_NAME;
    }

    /**
     * @param boolean $is_deleted
     * @return File
     */
    public function setIsDeleted($is_deleted)
    {
        $this->is_deleted = $is_deleted;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getIsDeleted()
    {
        return $this->is_deleted;
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
            'user' => $this->getUser()->jsonSerialize(true),
            'group' => $this->getGroup()->jsonSerialize(true)
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
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     * @return GroupMembership
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return Group
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param Group $group
     * @return GroupMembership
     */
    public function setGroup($group)
    {
        $this->group = $group;
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
