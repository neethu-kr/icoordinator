<?php

namespace iCoordinator\Entity\Invitation;

use iCoordinator\Entity\AbstractEntity;
use iCoordinator\Entity\Invitation;

/**
 * @Entity
 * @Table(name="invitation_workspaces", options={"collate"="utf8_general_ci"})
 * @HasLifecycleCallbacks
 */
class InvitationWorkspace extends AbstractEntity
{
    const ENTITY_NAME = 'entity:Invitation\InvitationWorkspace';

    const RESOURCE_ID = 'invitationworkspace';

    /**
     * @var \iCoordinator\Entity\Workspace
     * @ManyToOne(targetEntity="\iCoordinator\Entity\Workspace")
     * @JoinColumn(name="workspace_id", referencedColumnName="id")
     */
    protected $workspace = null;

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
            'workspace' => $this->getWorkspace(),
            'invitation' => $this->getInvitation()
        );
    }

    /**
     * @return Workspace
     */
    public function getWorkspace()
    {
        return $this->workspace;
    }

    /**
     * @param Workspace $workspace
     * @return $this
     */
    public function setWorkspace($workspace)
    {
        $this->workspace = $workspace;
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
