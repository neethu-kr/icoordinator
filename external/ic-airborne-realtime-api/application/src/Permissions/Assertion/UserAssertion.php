<?php

namespace iCoordinator\Permissions\Assertion;

use iCoordinator\Entity\User;
use iCoordinator\Permissions\Privilege\UserPrivilege;
use iCoordinator\Permissions\Role\UserRole;
use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Assertion\AssertionInterface;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;

class UserAssertion implements AssertionInterface
{
    public function assert(Acl $acl, RoleInterface $role = null, ResourceInterface $resource = null, $privilege = null)
    {
        if (!$role instanceof UserRole) {
            return false;
        }

        if (!$resource instanceof User) {
            return false;
        }

        if ($role->getUserId() == $resource->getId()) {
            return true;
        }

        switch ($privilege) {
            case UserPrivilege::PRIVILEGE_READ:
                return true;
                break;
        }

        return false;
    }
}
