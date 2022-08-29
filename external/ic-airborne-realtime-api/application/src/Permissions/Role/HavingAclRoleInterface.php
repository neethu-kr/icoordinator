<?php

namespace iCoordinator\Permissions\Role;

use iCoordinator\Entity\Acl\AclRole;

interface HavingAclRoleInterface
{
    /**
     * @return AclRole
     */
    public function getAclRole();
}
