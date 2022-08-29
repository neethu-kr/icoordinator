<?php

namespace iCoordinator\Entity\Acl\AclResource;

use iCoordinator\Entity\Acl\AclResource;

/**
 * @Entity
 */
class AclPortalResource extends AclResource
{
    const ENTITY_NAME = 'entity:Acl\AclResource\AclPortalResource';

    const ACL_RESOURCE_ENTITY_TYPE = 'portal';

    /**
     * @var \iCoordinator\Entity\Portal
     * @OneToOne(targetEntity="\iCoordinator\Entity\Portal")
     * @JoinColumn(name="entity_id", referencedColumnName="id")
     */
    protected $portal;

    /**
     * @return string
     */
    public function getAclResourceEntityType()
    {
        return self::ACL_RESOURCE_ENTITY_TYPE;
    }

    /**
     * @return \iCoordinator\Entity\Portal
     */
    public function getResource()
    {
        return $this->getPortal();
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
     * @return AclPortalResource
     */
    public function setPortal($portal)
    {
        $this->portal = $portal;
        return $this;
    }

    public function jsonSerialize()
    {
        return $this->getPortal()->jsonSerialize();
    }
}
