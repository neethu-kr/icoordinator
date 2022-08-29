<?php

namespace iCoordinator\Permissions\Assertion;

use iCoordinator\Entity\Acl\AclPermission;
use iCoordinator\Entity\Permission;
use iCoordinator\Permissions\Privilege\PermissionPrivilege;
use iCoordinator\Permissions\Resource\HavingDynamicPermissionsResourceInterface;
use iCoordinator\Permissions\Resource\PermissionResource;
use iCoordinator\Permissions\Role\UserRole;
use iCoordinator\Service\PermissionService;
use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Assertion\AssertionAggregate;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;

class PermissionAssertion extends AssertionAggregate
{
    public function __construct()
    {
        $this->setMode(self::MODE_AT_LEAST_ONE);

        $this->addAssertions(array(
            new PortalAdminAssertion()
        ));
    }

    public function assert(Acl $acl, RoleInterface $role = null, ResourceInterface $resource = null, $privilege = null)
    {
        if (!$role instanceof UserRole) {
            return false;
        }

        if (!$resource instanceof AclPermission) {
            return false;
        }

        if (parent::assert($acl, $role, $resource->getPortal(), $privilege)) {
            return true;
        }

        $permission = $resource;

        $relatedResource = $permission->getAclResource()->getResource();

        if (!$relatedResource instanceof HavingDynamicPermissionsResourceInterface) {
            return false;
        }

        switch ($privilege) {
            case null:
            case PermissionPrivilege::PRIVILEGE_READ:
                /** @var PermissionService $permissionManager */
                $permissionManager = $acl->getPermissionManager();
                //permission is connected with user acl role
                if ($permissionManager->hasAclRole($role->getUserId(), $permission->getAclRole())) {
                    return true;
                }

                if ($acl->isAllowed($role, $relatedResource, $relatedResource::getPrivilegeForReadingPermissions())) {
                    return true;
                }
                break;
            case PermissionPrivilege::PRIVILEGE_MODIFY:
            case PermissionPrivilege::PRIVILEGE_DELETE:
                $permissionTypes = $permission->getActions();
                foreach ($permissionTypes as $permissionType) {
                    $privilege = $relatedResource::getPrivilegeForGrantingPermission($permissionType);
                    if (!$acl->isAllowed($role, $relatedResource, $privilege)) {
                        return false;
                    }
                }
                return true;
                break;
        }

        return false;
    }
}
