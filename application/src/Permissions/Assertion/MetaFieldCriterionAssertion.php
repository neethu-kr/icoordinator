<?php

namespace iCoordinator\Permissions\Assertion;

use iCoordinator\Entity\MetaFieldCriterion;
use iCoordinator\Permissions\Privilege\FilePrivilege;
use iCoordinator\Permissions\Privilege\MetaFieldCriterionPrivilege;
use iCoordinator\Permissions\Role\UserRole;
use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Assertion\AssertionInterface;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;

class MetaFieldCriterionAssertion implements AssertionInterface
{
    public function assert(Acl $acl, RoleInterface $role = null, ResourceInterface $resource = null, $privilege = null)
    {
        if (!$role instanceof UserRole) {
            return false;
        }

        if (!$resource instanceof MetaFieldCriterion) {
            return false;
        }

        switch ($privilege) {
            case null:
            case MetaFieldCriterionPrivilege::PRIVILEGE_READ:
                return $acl->isAllowed($role, $resource->getSmartFolder(), FilePrivilege::PRIVILEGE_READ);
                break;
            default:
                return $acl->isAllowed($role, $resource->getSmartFolder(), FilePrivilege::PRIVILEGE_MODIFY);
                break;
        }
    }
}
