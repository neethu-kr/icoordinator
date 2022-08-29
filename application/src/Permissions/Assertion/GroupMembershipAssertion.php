<?php

namespace iCoordinator\Permissions\Assertion;

use iCoordinator\Entity\GroupMembership;
use iCoordinator\Permissions\Privilege\GroupPrivilege;
use iCoordinator\Permissions\Role\UserRole;
use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Assertion\AssertionInterface;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;

class GroupMembershipAssertion implements AssertionInterface
{
    public function assert(Acl $acl, RoleInterface $role = null, ResourceInterface $resource = null, $privilege = null)
    {
        if (!$role instanceof UserRole) {
            return false;
        }

        if (!$resource instanceof GroupMembership) {
            return false;
        }

        $group = $resource->getGroup();

        return $acl->isAllowed($role, $group, GroupPrivilege::PRIVILEGE_MANAGE_USERS);
    }
}
