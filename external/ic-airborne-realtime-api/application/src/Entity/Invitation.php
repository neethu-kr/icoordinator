<?php

namespace iCoordinator\Entity;

use Carbon\Carbon;
use Datetime;
use Doctrine\Common\Collections\ArrayCollection;
use iCoordinator\Entity\Invitation\InvitationWorkspace;
use iCoordinator\Entity\Invitation\InvitationWorkspaceGroup;

/**
 * @Entity
 * @Table(name="invitations", options={"collate"="utf8_general_ci"})
 * @HasLifecycleCallbacks
 */
class Invitation extends AbstractEntity
{
    const ENTITY_NAME = 'entity:Invitation';

    const RESOURCE_ID = 'invitation';

    /**
     * @var string
     * //TODO: change length to 32
     * @Column(type="string", unique=true, length=36, nullable=false)
     */
    protected $token;

    /**
     * @var string
     * @Column(type="string", length=100, nullable=false)
     */
    protected $email;

    /**
     * @var string
     * @Column(type="string", length=100, nullable=true)
     */
    protected $first_name = null;

    /**
     * @var string
     * @Column(type="string", length=100, nullable=true)
     */
    protected $last_name = null;

    /**
     * @var Portal
     * @ManyToOne(targetEntity="Portal")
     * @JoinColumn(name="portal_id", referencedColumnName="id")
     */
    protected $portal;

    /**
     * @var ArrayCollection
     * @OneToMany(targetEntity="iCoordinator\Entity\Invitation\InvitationWorkspaceGroup",
     *      mappedBy="invitation", fetch="EXTRA_LAZY", cascade={"persist", "remove"})
     */
    protected $workspace_groups;

    /**
     * @var ArrayCollection
     * @OneToMany(targetEntity="iCoordinator\Entity\Invitation\InvitationWorkspace",
     *     mappedBy="invitation", fetch="EXTRA_LAZY", cascade={"persist", "remove"})
     */
    protected $workspaces;
    
    /**
     * @var User
     * @ManyToOne(targetEntity="User")
     * @JoinColumn(name="created_by", referencedColumnName="id")
     */
    protected $created_by;

    /**
     * @var Carbon
     * @Column(type="datetime")
     */
    protected $created_at;

    /**
     * @var int
     */
    protected $user_id;

    public function __construct()
    {
        $this->workspaces = new ArrayCollection();
        $this->workspace_groups = new ArrayCollection();
    }
    public static function getEntityName()
    {
        return self::ENTITY_NAME;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param $token
     * @return $this
     */
    public function setToken($token)
    {
        $this->token = $token;
        return $this;
    }

    public function getResourceId()
    {
        return self::RESOURCE_ID;
    }

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
     * @param $created_at
     * @return $this
     */
    public function setCreatedAt($created_at)
    {
        $this->created_at = $created_at;
        return $this;
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
     * @return User
     */
    public function getCreatedBy()
    {
        return $this->created_by;
    }

    /**
     * @param $created_by
     * @return $this
     */
    public function setCreatedBy($created_by)
    {
        $this->created_by = $created_by;
        return $this;
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->first_name;
    }

    /**
     * @param string $first_name
     * @return Invitation
     */
    public function setFirstName($first_name)
    {
        $this->first_name = $first_name;
        return $this;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->last_name;
    }

    /**
     * @param string $last_name
     * @return Invitation
     */
    public function setLastName($last_name)
    {
        $this->last_name = $last_name;
        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getWorkspaceGroups()
    {
        return $this->workspace_groups;
    }

    /**
     * @return ArrayCollection
     */
    public function getWorkspaces()
    {
        return $this->workspaces;
    }

    /**
     * @return int
     */
    public function getInvitedUserId()
    {
        return $this->user_id;
    }

    /**
     * @param $user_id
     * @return $this
     */
    public function setInvitedUserId($user_id)
    {
        $this->user_id = $user_id;
        return $this;
    }

    public function jsonSerialize()
    {
        $workspaceArray = array();
        $groupArray = array();

        $invitationWorkspaces = $this->getWorkspaces();
        foreach ($invitationWorkspaces as $invitationWorkspace) {
            $workspaceArray[] = $invitationWorkspace->getWorkspace()->jsonSerialize(true);
        }
        $invitationWorkspaceGroups = $this->getWorkspaceGroups();
        foreach ($invitationWorkspaceGroups as $invitationWorkspaceGroup) {
            $groupArray[] = $invitationWorkspaceGroup->getGroup()->jsonSerialize(true);
        }
        return [
            'entity_type' => self::RESOURCE_ID,
            'id' => $this->getId(),
            'email' => $this->getEmail(),
            'first_name' => $this->getFirstName(),
            'last_name' => $this->getLastName(),
            'portal' => $this->getPortal()->jsonSerialize(true),
            'workspaces' => $workspaceArray,
            'groups' => $groupArray,
            'created_by' => $this->getCreatedBy()->jsonSerialize(true),
            'created_at' => ($this->getCreatedAt()) ? $this->getCreatedAt()->format(DateTime::ISO8601) : null,
            'user_id' => $this->getInvitedUserId()
        ];
    }
    /**
     * @param InvitationWorkspace $invitationWorkspace
     * @return $this
     */
    public function addWorkspace($invitationWorkspace)
    {
        $this->workspaces[] = $invitationWorkspace;
        $invitationWorkspace->setInvitation($this);
        return $this;
    }
    /**
     * @param InvitationWorkspaceGroup $invitationWorkspaceGroup
     * @return $this
     */
    public function addWorkspaceGroup($invitationWorkspaceGroup)
    {
        $this->workspace_groups[] = $invitationWorkspaceGroup;
        $invitationWorkspaceGroup->setInvitation($this);
        return $this;
    }
}
