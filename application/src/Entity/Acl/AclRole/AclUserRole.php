<?php

namespace iCoordinator\Entity\Acl\AclRole;

use iCoordinator\Entity\Acl\AclRole;

/**
 * @Entity
 */
class AclUserRole extends AclRole
{
    const ENTITY_NAME = 'entity:Acl\AclRole\AclUserRole';

    const ACL_ROLE_ENTITY_TYPE = 'user';

    const RESOURCE_ID = 'acl_user_role';

    const ROLE_ID = 'user';

    /**
     * @var \iCoordinator\Entity\User
     * @ManyToOne(targetEntity="\iCoordinator\Entity\User")
     * @JoinColumn(name="entity_id", referencedColumnName="id")
     */
    protected $user;

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
        return $this->getUser()->jsonSerialize(true);
    }

    /**
     * @return \iCoordinator\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param \iCoordinator\Entity\User $user
     * @return AclUserRole
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    public function getRoleId()
    {
        return self::ROLE_ID;
    }
}
