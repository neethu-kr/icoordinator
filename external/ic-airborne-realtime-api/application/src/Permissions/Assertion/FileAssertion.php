<?php

namespace iCoordinator\Permissions\Assertion;

use iCoordinator\Entity\File;
use iCoordinator\Entity\Group\WorkspaceGroup;
use iCoordinator\Permissions\PermissionType;
use iCoordinator\Permissions\Privilege\FilePrivilege;
use iCoordinator\Permissions\Privilege\HavingDynamicPermissionsResourcePrivilege;
use iCoordinator\Permissions\Resource\FileResource;
use iCoordinator\Permissions\Role\GroupRole;
use iCoordinator\Permissions\Role\UserRole;
use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Assertion\AssertionAggregate;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;

class FileAssertion extends AssertionAggregate
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
     * @var File
     */
    private $resource;

    public function __construct()
    {
        $this->setMode(self::MODE_AT_LEAST_ONE);

        $this->addAssertions(array(
            new SharedLinkAssertion()
        ));
    }

    public function assert(Acl $acl, RoleInterface $role = null, ResourceInterface $resource = null, $privilege = null)
    {
        if (!$acl instanceof \iCoordinator\Permissions\Acl) {
            throw new \Exception('$acl should be instance of \iCoordinator\Permissions\Acl');
        }

        if (!$resource instanceof File) {
            return false;
        }

        $this->acl = $acl;
        $this->role = $role;
        $this->resource = $resource;

        //can be accessed by UserRole and GuestRole
        if (parent::assert($acl, $role, $resource, $privilege)) {
            return true;
        }

        $permissionManager = $acl->getPermissionManager();
        $hasPermissions = false;
        $noPermissions = false;

        if ($role instanceof UserRole) {
            if ((!$this->hasPortalAccess())) {
                return false;
            }

            if ($this->isWorkspaceAdmin()) {
                return true;
            }

            if (!$this->hasWorkspaceAccess()) {
                return false;
            }

            if ($this->isFileOwner()) {
                return true;
            }
            $hasPermissions = $permissionManager->hasPermission(
                $role->getUserId(),
                $resource,
                array(
                    PermissionType::FILE_READ,
                    PermissionType::FILE_EDIT,
                    PermissionType::FILE_GRANT_READ,
                    PermissionType::FILE_GRANT_EDIT
                )
            );
            $noPermissions = $permissionManager->hasPermission(
                $role->getUserId(),
                $resource,
                array(
                    PermissionType::FILE_NONE
                )
            );
        }

        if (!$hasPermissions && !$noPermissions) {
            if ($resource->getParent()) {
                if ($acl->isAllowed($role, $resource->getParent(), $privilege)) {
                    return true;
                }
            }
        }
        switch ($privilege) {
            case null:
            case FilePrivilege::PRIVILEGE_READ:
            case FilePrivilege::PRIVILEGE_DOWNLOAD:
            case FilePrivilege::PRIVILEGE_READ_VERSIONS:
            case FilePrivilege::PRIVILEGE_READ_PERMISSIONS:
            case FilePrivilege::PRIVILEGE_READ_META_FIELDS_VALUES:
            case FilePrivilege::PRIVILEGE_READ_META_FIELDS_CRITERIA:
                if (!$role instanceof UserRole) {
                    return false;
                }
                return $hasPermissions;
                break;
            case FilePrivilege::PRIVILEGE_MODIFY:
            case FilePrivilege::PRIVILEGE_DELETE:
            case FilePrivilege::PRIVILEGE_CREATE_FILES:
            case FilePrivilege::PRIVILEGE_CREATE_FOLDERS:
            case FilePrivilege::PRIVILEGE_CREATE_SMART_FOLDERS:
            case FilePrivilege::PRIVILEGE_ADD_META_FIELDS_VALUES:
            case FilePrivilege::PRIVILEGE_ADD_META_FIELDS_CRITERIA:
                if (!$role instanceof UserRole) {
                    return false;
                }
                return $permissionManager->hasPermission(
                    $role->getUserId(),
                    $resource,
                    PermissionType::FILE_EDIT
                );
                break;
            case FilePrivilege::PRIVILEGE_CREATE_SHARED_LINK:
                if (!$role instanceof UserRole) {
                    return false;
                }
                return $permissionManager->hasPermission(
                    $role->getUserId(),
                    $resource,
                    PermissionType::FILE_EDIT
                ) || $permissionManager->hasPermission(
                    $role->getUserId(),
                    $resource,
                    PermissionType::FILE_GRANT_READ
                );
                break;
            case FilePrivilege::PRIVILEGE_GRANT_READ_PERMISSION:
                if (!$role instanceof UserRole) {
                    return false;
                }
                return $permissionManager->hasPermission(
                    $role->getUserId(),
                    $resource,
                    PermissionType::FILE_GRANT_READ
                );
                break;
            case FilePrivilege::PRIVILEGE_GRANT_EDIT_PERMISSION:
                if (!$role instanceof UserRole) {
                    return false;
                }
                return $permissionManager->hasPermission(
                    $role->getUserId(),
                    $resource,
                    PermissionType::FILE_GRANT_EDIT
                );
                break;
            case HavingDynamicPermissionsResourcePrivilege::PRIVILEGE_HAVE_PERMISSIONS:
                if ($role instanceof GroupRole) {
                    $group = $role->getGroup();
                    if ($group instanceof WorkspaceGroup) {
                        return ($resource->getWorkspace()->getId() == $group->getScope()->getId());
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
        $result = $portalAccessAssertion->assert($this->acl, $this->role, $this->resource->getWorkspace()->getPortal());
        return $result;
    }

    private function hasWorkspaceAccess()
    {
        $workspaceAccessAssertion = new WorkspaceAccessAssertion();
        $result = $workspaceAccessAssertion->assert($this->acl, $this->role, $this->resource->getWorkspace());
        return $result;
    }

    private function isWorkspaceAdmin()
    {
        $workspaceAdminAssertion = new WorkspaceAdminAssertion();
        $result = $workspaceAdminAssertion->assert($this->acl, $this->role, $this->resource->getWorkspace());
        return $result;
    }

    private function isFileOwner()
    {
        $ownershipAssertion = new OwnershipAssertion();
        $result = $ownershipAssertion->assert($this->acl, $this->role, $this->resource);
        return $result;
    }
}
