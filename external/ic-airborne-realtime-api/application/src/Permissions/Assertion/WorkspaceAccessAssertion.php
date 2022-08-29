<?php

namespace iCoordinator\Permissions\Assertion;

use iCoordinator\Entity\Workspace;
use iCoordinator\Permissions\PermissionType;
use iCoordinator\Permissions\Resource\HavingWorkspaceResourceInterface;
use iCoordinator\Permissions\Role\UserRole;
use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Assertion\AssertionAggregate;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;

class WorkspaceAccessAssertion extends AssertionAggregate
{
    private $workspaceAdminAssertion = null;

    public function __construct()
    {
        $this->setMode(self::MODE_AT_LEAST_ONE);

        $this->addAssertions(array(
            new PortalAccessAssertion()
        ));
    }

    public function assert(Acl $acl, RoleInterface $role = null, ResourceInterface $resource = null, $privilege = null)
    {
        if (!$role instanceof UserRole) {
            return false;
        }

        if ($resource instanceof Workspace) {
            $workspace = $resource;
        } elseif ($resource instanceof HavingWorkspaceResourceInterface) {
            $workspace = $resource->getWorkspace();
        } else {
            return false;
        }

        if (!parent::assert($acl, $role, $workspace->getPortal())) {
            return false;
        }

        //check if user is workspace admin
        if ($this->getWorkspaceAdminAssertion()->assert($acl, $role, $resource)) {
            return true;
        }

        $userId = $role->getUserId();
        $permissionManager = $acl->getPermissionManager();

        $result = $permissionManager->hasPermission(
            $userId,
            $resource,
            PermissionType::WORKSPACE_ACCESS,
            $resource->getPortal()
        );

        return $result;
    }

    private function getWorkspaceAdminAssertion()
    {
        if ($this->workspaceAdminAssertion === null) {
            $this->workspaceAdminAssertion = new WorkspaceAdminAssertion();
        }

        return $this->workspaceAdminAssertion;
    }
}
