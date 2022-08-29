<?php

namespace iCoordinator\Entity;

use Carbon\Carbon;
use Datetime;
use iCoordinator\Entity\Acl\AclRole\AclUserRole;
use iCoordinator\Entity\User\Locale;
use iCoordinator\Permissions\Role\HavingAclRoleInterface;
use Rhumsaa\Uuid\Uuid;

/**
 * @Entity
 * @Table(name="users", options={"collate"="utf8_general_ci"})
 * @HasLifecycleCallbacks
 */
class User extends AbstractEntity implements HavingAclRoleInterface
{
    const ENTITY_NAME = 'entity:User';

    const RESOURCE_ID = 'user';

    /**
     * @var string
     * @Column(type="string", unique=true, nullable=true, length=100)
     */
    protected $uuid;

    /**
     * @var string
     * @Column(type="string", unique=true,  length=255)
     */
    protected $email;

    /**
     * @var string
     * @Column(type="string", length=128)
     */
    protected $password;

    /**
     * @var string
     * @Column(type="string", nullable=true, length=255)
     */
    protected $name = null;

    /**
     * @var string
     * @Column(type="string", nullable=true, length=255)
     */
    protected $job_title = null;

    /**
     * @var string
     * @Column(type="string", nullable=true, length=50)
     */
    protected $phone = null;

    /**
     * @var string
     * @Column(type="string", nullable=true, length=255)
     */
    protected $address = null;

    /**
     * @var string
     * @Column(type="string", nullable=true, length=255)
     */
    protected $avatar_url = null;

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
    protected $email_confirmed = false;

    /**
     * @var bool
     * @Column(type="boolean")
     */
    protected $is_deleted = false;


    /**
     * @var bool
     * @Column(type="boolean")
     */
    protected $instant_notification = false;

    /**
     * @var Locale
     * @OneToOne(targetEntity="\iCoordinator\Entity\User\Locale", mappedBy="user", cascade={"persist", "remove"})
     **/
    protected $locale;

    /**
     * @var AclUserRole
     * @OneToOne(targetEntity="\iCoordinator\Entity\Acl\AclRole\AclUserRole", mappedBy="user", cascade={"remove"})
     */
    protected $acl_role = null;

    /**
     * @var string;
     */
    private $rawPassword;

    /**
     * @var boolean;
     */
    private $isOwner = false;

    private $desktop = true;

    private $mobile = true;

    public function __construct()
    {
        $this->locale = new Locale();
        $this->locale->setUser($this);
    }

    public static function getEntityName()
    {
        return self::ENTITY_NAME;
    }

    /**
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * @param $uuid
     * @return $this
     */
    public function setUuid($uuid)
    {
        $this->uuid = $uuid;
        return $this;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Is accessible only for newly created entities
     *
     * @return string
     */
    public function getRawPassword()
    {
        return $this->rawPassword;
    }

    /**
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->rawPassword  = $password;
        $this->password     = password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * @return boolean
     */
    public function getIsDeleted()
    {
        return $this->is_deleted;
    }

    /**
     * @param $is_deleted
     * @return $this
     */
    public function setIsDeleted($is_deleted)
    {
        $this->is_deleted = $is_deleted;
        return $this;
    }

    /**
     * @PrePersist
     */
    public function createUuid()
    {
        $this->setUuid(Uuid::uuid4()->toString());
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
     * @param $created_at
     * @return $this
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

    public function getAclRole()
    {
        return $this->acl_role;
    }

    public function setAclRole($aclRole)
    {
        $this->acl_role = $aclRole;
    }

    /**
     * @return Locale
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param $locale
     * @return $this
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
        return $this;
    }

    public function jsonSerialize($mini = false)
    {
        $result = array(
            'entity_type' => self::RESOURCE_ID,
            'id' => $this->getId(),
            'email' => $this->getEmail(),
            'name' => $this->getName(),
            'is_owner' => $this->getIsOwner(),
            'instant_notification' => $this->getInstantNotification()
        );

        if (!$mini) {
            $result = array_merge($result, array(
                'job_title' => $this->getJobTitle(),
                'phone' => $this->getPhone(),
                'address' => $this->getAddress(),
                'avatar_url' => $this->getAvatarUrl(),
                'created_at' => $this->getCreatedAt()->format(DateTime::ISO8601),
                'modified_at' => $this->getModifiedAt()->format(DateTime::ISO8601),
                'email_confirmed' => $this->isEmailConfirmed(),
                'locale' => $this->getLocale() != null ? $this->getLocale()->jsonSerialize():null,
                'is_owner' => $this->getIsOwner(),
                'instant_notification' => $this->getInstantNotification(),
                'desktop' => $this->getDesktop(),
                'mobile' => $this->getMobile()
            ));
        }

        return $result;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param $email
     * @return $this
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
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
     * @return string
     */
    public function getJobTitle()
    {
        return $this->job_title;
    }

    /**
     * @param $job_title
     * @return $this
     */
    public function setJobTitle($job_title)
    {
        $this->job_title = $job_title;
        return $this;
    }

    /**
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param $phone
     * @return $this
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
        return $this;
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param $address
     * @return $this
     */
    public function setAddress($address)
    {
        $this->address = $address;
        return $this;
    }

    /**
     * @return string
     */
    public function getAvatarUrl()
    {
        return $this->avatar_url;
    }

    /**
     * @param $avatar_url
     * @return $this
     */
    public function setAvatarUrl($avatar_url)
    {
        $this->avatar_url = $avatar_url;
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
     * @param $modified_at
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
    public function isEmailConfirmed()
    {
        return $this->email_confirmed;
    }

    /**
     * @param $email_confirmed
     * @return $this
     */
    public function setEmailConfirmed($email_confirmed)
    {
        $this->email_confirmed = $email_confirmed;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getIsOwner()
    {
        return $this->isOwner;
    }

    /**
     * @param $isOwner
     * @return $this
     */
    public function setIsOwner($isOwner)
    {
        $this->isOwner = $isOwner;
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

    /**
     * @return boolean
     */
    public function getDesktop()
    {
        return $this->desktop;
    }

    /**
     * @param $desktop
     * @return $this
     */
    public function setDesktop($desktop)
    {
        $this->desktop = $desktop;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * @param $mobile
     * @return $this
     */
    public function setMobile($mobile)
    {
        $this->mobile = $mobile;
        return $this;
    }
}
