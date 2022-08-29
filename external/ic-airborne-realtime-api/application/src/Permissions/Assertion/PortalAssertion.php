<?php

namespace iCoordinator\Permissions\Assertion;

use iCoordinator\Entity\Group;
use iCoordinator\Entity\Portal;
use iCoordinator\Permissions\PermissionType;
use iCoordinator\Permissions\Privilege\HavingDynamicPermissionsResourcePrivilege;
use iCoordinator\Permissions\Privilege\PortalPrivilege;
use iCoordinator\Permissions\Role\GroupRole;
use iCoordinator\Permissions\Role\UserRole;
use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Assertion\AssertionAggregate;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;

class PortalAssertion extends AssertionAggregate
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
     * @var Portal
     */
    private $resource;

    public function __construct()
    {
        $this->setMode(self::MODE_AT_LEAST_ONE);

        $this->addAssertions(array(
            new PortalAdminAssertion()
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
        if (!$resource instanceof Portal) {
            return false;
        }

        $this->acl = $acl;
        $this->role = $role;
        $this->resource = $resource;

        if (parent::assert($acl, $role, $resource)) {
            return true;
        }

        switch ($privilege) {
            case null:
            case PortalPrivilege::PRIVILEGE_READ_WORKSPACES:
            case PortalPrivilege::PRIVILEGE_READ_USERS:
            case PortalPrivilege::PRIVILEGE_READ_GROUPS:
            case PortalPrivilege::PRIVILEGE_READ_META_FIELDS:
                if (!$role instanceof UserRole) {
                    return false;
                }
                return $this->hasPortalAccess();

            case PortalPrivilege::PRIVILEGE_GRANT_ACCESS_PERMISSION:
                if (!$role instanceof UserRole) {
                    return false;
                }
                return $acl->getPermissionManager()->hasPermission(
                    $role->getUserId(),
                    $resource,
                    PermissionType::PORTAL_GRANT_ACCESS,
                    $resource
                );

            case HavingDynamicPermissionsResourcePrivilege::PRIVILEGE_HAVE_PERMISSIONS:
                if ($role instanceof GroupRole) {
                    $group = $role->getGroup();
                    if ($group instanceof Group\PortalGroup) {
                        return ($resource->getId() == $group->getScope()->getId());
                    } else {
                        return false;
                    }
                }
                return true;
        }

        return false;
    }

    private function hasPortalAccess()
    {
        $portalAccessAssertion = new PortalAccessAssertion();
        return $portalAccessAssertion->assert($this->acl, $this->role, $this->resource);
    }
}
