<?php

namespace iCoordinator\Permissions\Assertion;

use iCoordinator\Entity\MetaFieldValue;
use iCoordinator\Permissions\Privilege\FilePrivilege;
use iCoordinator\Permissions\Role\UserRole;
use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Assertion\AssertionInterface;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;

class MetaFieldValueAssertion implements AssertionInterface
{
    public function assert(Acl $acl, RoleInterface $role = null, ResourceInterface $resource = null, $privilege = null)
    {
        if (!$role instanceof UserRole) {
            return false;
        }

        if (!$resource instanceof MetaFieldValue) {
            return false;
        }

        $file = $resource->getResource();

        return $acl->isAllowed($role, $file, FilePrivilege::PRIVILEGE_MODIFY);
    }
}
