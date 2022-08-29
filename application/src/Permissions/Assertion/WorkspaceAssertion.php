<?php

namespace iCoordinator\Permissions\Assertion;

use iCoordinator\Entity\Group;
use iCoordinator\Entity\Workspace;
use iCoordinator\Permissions\PermissionType;
use iCoordinator\Permissions\Privilege\HavingDynamicPermissionsResourcePrivilege;
use iCoordinator\Permissions\Privilege\WorkspacePrivilege;
use iCoordinator\Permissions\Role\GroupRole;
use iCoordinator\Permissions\Role\UserRole;
use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Assertion\AssertionAggregate;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;

class WorkspaceAssertion extends AssertionAggregate
{
    /**
     * @var Acl
     */
    private $acl;

    /**
     * @var RoleInterface
     */
    private $role;

    /**
     * @var Workspace
     */
    private $resource;

    public function __construct()
    {
        $this->setMode(self::MODE_AT_LEAST_ONE);

        $this->addAssertions(array(
            new PortalAccessAssertion()
        ));
    }

    /**
     * @param Acl $acl
     * @param RoleInterface $role
     * @param ResourceInterface $resource
     * @param null $privilege
     * @return bool
     */
    public function assert(Acl $acl, RoleInterface $role = null, ResourceInterface $resource = null, $privilege = null)
    {
        if (!$resource instanceof Workspace) {
            return false;
        }

        $this->acl = $acl;
        $this->role = $role;
        $this->resource = $resource;

        //check if user has access to the portal
        if ($role instanceof UserRole) {
            if (!parent::assert($acl, $role, $resource->getPortal())) {
                return false;
            }
        }

        switch ($privilege) {
            case WorkspacePrivilege::PRIVILEGE_DELETE:
            case WorkspacePrivilege::PRIVILEGE_MODIFY:
                return $this->isPortalAdmin();

            case WorkspacePrivilege::PRIVILEGE_GRAND_ADMIN_PERMISSION:
            case WorkspacePrivilege::PRIVILEGE_GRANT_GRANT_ACCESS_PERMISSION:
                return $this->isPortalAdmin() || $this->isWorkspaceAdmin();

            case WorkspacePrivilege::PRIVILEGE_GRANT_ACCESS_PERMISSION:
            case WorkspacePrivilege::PRIVILEGE_READ_PERMISSIONS:
                if (!$role instanceof UserRole) {
                    return false;
                }
                $userId = $role->getUserId();
                $permissionManager = $acl->getPermissionManager();
                return $this->isPortalAdmin() || $this->isWorkspaceAdmin() || $permissionManager->hasPermission(
                    $userId,
                    $resource,
                    PermissionType::WORKSPACE_GRANT_ACCESS,
                    $resource->getPortal()
                );

            case WorkspacePrivilege::PRIVILEGE_READ:
            case WorkspacePrivilege::PRIVILEGE_READ_USERS:
            case WorkspacePrivilege::PRIVILEGE_READ_GROUPS:
                return $this->isPortalAdmin() || $this->hasWorkspaceAccess();

            case WorkspacePrivilege::PRIVILEGE_CREATE_FILES:
            case WorkspacePrivilege::PRIVILEGE_CREATE_FOLDERS:
            case WorkspacePrivilege::PRIVILEGE_CREATE_SMART_FOLDERS:
            case WorkspacePrivilege::PRIVILEGE_CREATE_GROUPS:
            case WorkspacePrivilege::PRIVILEGE_READ_USER_GROUPS:
            case WorkspacePrivilege::PRIVILEGE_READ_ALL_FILES:
                return $this->isWorkspaceAdmin();

            case HavingDynamicPermissionsResourcePrivilege::PRIVILEGE_HAVE_PERMISSIONS:
                if ($role instanceof GroupRole) {
                    $group = $role->getGroup();
                    switch (true) {
                        case $group instanceof Group\PortalGroup:
                            return ($resource->getPortal()->getId() == $group->getScope()->getId());
                        case $group instanceof Group\WorkspaceGroup:
                            return ($resource->getId() == $group->getScope()->getId());
                        default:
                            return false;
                    }
                }
                return true;

            default:
                return $this->hasWorkspaceAccess();
        }
    }

    private function isPortalAdmin()
    {
        $portalAdminAssertion = new PortalAdminAssertion();
        $result = $portalAdminAssertion->assert($this->acl, $this->role, $this->resource->getPortal());
        return $result;
    }

    private function isWorkspaceAdmin()
    {
        $workspaceAdminAssertion = new WorkspaceAdminAssertion();
        $result = $workspaceAdminAssertion->assert($this->acl, $this->role, $this->resource);
        return $result;
    }

    private function hasWorkspaceAccess()
    {
        $workspaceAccessAssertion = new WorkspaceAccessAssertion();
        $result = $workspaceAccessAssertion->assert($this->acl, $this->role, $this->resource);
        return $result;
    }
}
