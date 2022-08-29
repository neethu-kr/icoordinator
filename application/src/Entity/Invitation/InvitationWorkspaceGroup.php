<?php

namespace iCoordinator\Entity\Invitation;

use iCoordinator\Entity\AbstractEntity;
use iCoordinator\Entity\Invitation;

/**
 * @Entity
 * @Table(name="invitation_workspace_groups", options={"collate"="utf8_general_ci"})
 * @HasLifecycleCallbacks
 */
class InvitationWorkspaceGroup extends AbstractEntity
{
    const ENTITY_NAME = 'entity:Invitation\InvitationWorkspaceGroup';

    const RESOURCE_ID = 'invitationworkspacegroup';

    /**
     * @var \iCoordinator\Entity\Group
     * @ManyToOne(targetEntity="\iCoordinator\Entity\Group")
     * @JoinColumn(name="group_id", referencedColumnName="id")
     */
    protected $group = null;

    /**
     * @var \iCoordinator\Entity\Invitation
     * @ManyToOne(targetEntity="\iCoordinator\Entity\Invitation", cascade={"persist"})
     * @JoinColumn(name="invitation_id", referencedColumnName="id")
     */
    protected $invitation;

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
            'group' => $this->getGroup(),
            'invitation' => $this->getInvitation()
        );
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
     * @return $this
     */
    public function setGroup($group)
    {
        $this->group = $group;
        return $this;
    }

    /**
     * @return Invitation
     */
    public function getInvitation()
    {
        return $this->invitation;
    }

    /**
     * @param Invitation $invitation
     * @return $this
     */
    public function setInvitation($invitation)
    {
        $this->invitation = $invitation;
        return $this;
    }
}
