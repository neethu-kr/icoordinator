<?php

namespace iCoordinator\Permissions\Assertion;

use iCoordinator\Permissions\Resource\HavingOwnerResourceInterface;
use iCoordinator\Permissions\Role\UserRole;
use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Assertion\AssertionInterface;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;

class OwnershipAssertion implements AssertionInterface
{
    public function assert(Acl $acl, RoleInterface $role = null, ResourceInterface $resource = null, $privilege = null)
    {
        if (!$role instanceof UserRole) {
            return false;
        }

        if (!$resource instanceof HavingOwnerResourceInterface) {
            return false;
        }

        $userId = $role->getUserId();
        $ownerId = $resource->getOwnedBy()->getId();

        if ($userId == $ownerId) {
            return true;
        }

        return false;
    }
}
