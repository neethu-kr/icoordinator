<?php

namespace iCoordinator\Permissions\Assertion;

use iCoordinator\Entity\Portal;
use iCoordinator\Permissions\Role\UserRole;
use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Assertion\AssertionAggregate;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;

class PortalAdminAssertion extends AssertionAggregate
{
    public function __construct()
    {
        $this->setMode(self::MODE_AT_LEAST_ONE);

        $this->addAssertions(array(
            new OwnershipAssertion()
        ));
    }

    public function assert(Acl $acl, RoleInterface $role = null, ResourceInterface $resource = null, $privilege = null)
    {
        if (parent::assert($acl, $role, $resource, $privilege)) {
            return true;
        }

        if (!$role instanceof UserRole) {
            return false;
        }

        if (!$resource instanceof Portal) {
            return false;
        }

        $userId = $role->getUserId();
        $permissionManager = $acl->getPermissionManager();

        return $permissionManager->isPortalAdmin($userId, $resource);
    }
}
