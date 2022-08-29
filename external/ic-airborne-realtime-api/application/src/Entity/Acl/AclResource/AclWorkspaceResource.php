<?php

namespace iCoordinator\Entity\Acl\AclResource;

use iCoordinator\Entity\Acl\AclResource;

/**
 * @Entity
 */
class AclWorkspaceResource extends AclResource
{
    const ENTITY_NAME = 'entity:Acl\AclResource\AclWorkspaceResource';

    const ACL_RESOURCE_ENTITY_TYPE = 'workspace';

    /**
     * @var \iCoordinator\Entity\Workspace
     * @OneToOne(targetEntity="\iCoordinator\Entity\Workspace")
     * @JoinColumn(name="entity_id", referencedColumnName="id")
     */
    protected $workspace;

    /**
     * @return string
     */
    public function getAclResourceEntityType()
    {
        return self::ACL_RESOURCE_ENTITY_TYPE;
    }

    /**
     * @return \iCoordinator\Entity\Workspace
     */
    public function getResource()
    {
        return $this->getWorkspace();
    }

    /**
     * @return \iCoordinator\Entity\Workspace
     */
    public function getWorkspace()
    {
        return $this->workspace;
    }

    /**
     * @param \iCoordinator\Entity\Workspace $workspace
     * @return AclWorkspaceResource
     */
    public function setWorkspace($workspace)
    {
        $this->workspace = $workspace;
        return $this;
    }

    public function jsonSerialize()
    {
        return $this->getWorkspace()->jsonSerialize(true);
    }
}
