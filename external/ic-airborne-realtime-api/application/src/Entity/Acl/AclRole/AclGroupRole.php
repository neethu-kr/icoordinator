<?php

namespace iCoordinator\Entity\Acl\AclRole;

use iCoordinator\Entity\Acl\AclRole;

/**
 * @Entity
 */
class AclGroupRole extends AclRole
{
    const ENTITY_NAME = 'entity:Acl\AclRole\AclGroupRole';

    const ACL_ROLE_ENTITY_TYPE = 'group';

    const RESOURCE_ID = 'acl_group_role';

    const ROLE_ID = 'group';

    /**
     * @var \iCoordinator\Entity\Group
     * @OneToOne(targetEntity="\iCoordinator\Entity\Group")
     * @JoinColumn(name="entity_id", referencedColumnName="id")
     */
    protected $group;

    public static function getEntityName()
    {
        return self::ENTITY_NAME;
    }

    public function getEntityType()
    {
        return self::ACL_ROLE_ENTITY_TYPE;
    }

    public function jsonSerialize()
    {
        return $this->getGroup()->jsonSerialize(true);
    }

    /**
     * @return \iCoordinator\Entity\Group
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param \iCoordinator\Entity\Group $group
     * @return \iCoordinator\Entity\Group
     */
    public function setGroup($group)
    {
        $this->group = $group;
        return $this;
    }

    public function getRoleId()
    {
        return self::ROLE_ID;
    }
}
