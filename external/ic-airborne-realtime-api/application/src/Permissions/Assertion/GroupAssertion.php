<?php

namespace iCoordinator\Permissions\Assertion;

use iCoordinator\Entity\Group;
use iCoordinator\Permissions\Privilege\GroupPrivilege;
use iCoordinator\Permissions\Role\UserRole;
use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Assertion\AssertionAggregate;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;

class GroupAssertion extends AssertionAggregate
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
     * @var Group
     */
    private $resource;

    public function assert(Acl $acl, RoleInterface $role = null, ResourceInterface $resource = null, $privilege = null)
    {
        if (!$role instanceof UserRole) {
            return false;
        }

        if (!$resource instanceof Group) {
            return false;
        }

        $this->acl = $acl;
        $this->role = $role;
        $this->resource = $resource;

        switch ($privilege) {
            case null:
            case GroupPrivilege::PRIVILEGE_READ:
            case GroupPrivilege::PRIVILEGE_READ_USERS:
            case GroupPrivilege::PRIVILEGE_BECOME_MEMBER:
                //check group type
                switch (true) {
                    case ($resource instanceof Group\PortalGroup):
                        return $this->hasPortalAccess();
                    case ($resource instanceof Group\WorkspaceGroup):
                        return $this->hasWorkspaceAccess();
                }
                return false;
            case GroupPrivilege::PRIVILEGE_DELETE:
            case GroupPrivilege::PRIVILEGE_MODIFY:
            case GroupPrivilege::PRIVILEGE_MANAGE_USERS:
                switch (true) {
                    case ($resource instanceof Group\PortalGroup):
                        return $this->isPortalAdmin();
                    case ($resource instanceof Group\WorkspaceGroup):
                        return $this->isWorkspaceAdmin();
                }
                return false;
        }
    }

    private function isPortalAdmin()
    {
        $portalAdminAssertion = new PortalAdminAssertion();
        return $portalAdminAssertion->assert($this->acl, $this->role, $this->resource->getScope());
    }

    private function isWorkspaceAdmin()
    {
        $workspaceAdminAssertion = new WorkspaceAdminAssertion();
        return $workspaceAdminAssertion->assert($this->acl, $this->role, $this->resource->getScope());
    }

    private function hasPortalAccess()
    {
        $portalAccessAssertion = new PortalAccessAssertion();
        return $portalAccessAssertion->assert($this->acl, $this->role, $this->resource->getScope());
    }

    private function hasWorkspaceAccess()
    {
        $workspaceAccessAssertion = new WorkspaceAccessAssertion();
        return $workspaceAccessAssertion->assert($this->acl, $this->role, $this->resource->getScope());
    }
}
