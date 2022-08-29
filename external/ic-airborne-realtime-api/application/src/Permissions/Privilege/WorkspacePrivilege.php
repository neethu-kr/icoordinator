<?php

namespace iCoordinator\Permissions\Privilege;

use iCoordinator\Permissions\PermissionType;

class WorkspacePrivilege
{
    const PRIVILEGE_MODIFY = 'modify';
    const PRIVILEGE_DELETE = 'delete';
    const PRIVILEGE_READ = 'read';
    const PRIVILEGE_READ_GROUPS = 'read_groups';
    const PRIVILEGE_READ_USER_GROUPS = 'read_user_groups';
    const PRIVILEGE_CREATE_GROUPS = 'create_groups';
    const PRIVILEGE_READ_USERS = 'read_users';
    const PRIVILEGE_READ_SHARED_FILES = 'read_shared_files';
    const PRIVILEGE_READ_ALL_FILES = 'read_all_files';
    const PRIVILEGE_READ_TRASHED_FILES = 'read_trashed_files';
    const PRIVILEGE_CREATE_FILES = 'create_files';
    const PRIVILEGE_CREATE_FOLDERS = 'create_folders';
    const PRIVILEGE_CREATE_SMART_FOLDERS = 'create_smart_folders';
    const PRIVILEGE_READ_PERMISSIONS = 'read_permissions';
    const PRIVILEGE_GRAND_ADMIN_PERMISSION = 'grant_admin_permission';
    const PRIVILEGE_GRANT_ACCESS_PERMISSION = 'grant_access_permission';
    const PRIVILEGE_GRANT_GRANT_ACCESS_PERMISSION = 'grant_grant_access_permission';

    private static $privilegesForGrantingPermission = array(
        PermissionType::WORKSPACE_ADMIN => self::PRIVILEGE_GRAND_ADMIN_PERMISSION,
        PermissionType::WORKSPACE_ACCESS => self::PRIVILEGE_GRANT_ACCESS_PERMISSION,
        PermissionType::WORKSPACE_GRANT_ACCESS => self::PRIVILEGE_GRANT_GRANT_ACCESS_PERMISSION
    );

    /**
     * @param $permissionType
     * @return null|string
     */
    public static function getPrivilegeForGrantingPermission($permissionType)
    {
        if (isset(self::$privilegesForGrantingPermission[$permissionType])) {
            return self::$privilegesForGrantingPermission[$permissionType];
        }
        return null;
    }

    /**
     * @return string
     */
    public static function getPrivilegeForReadingPermissions()
    {
        return self::PRIVILEGE_READ_PERMISSIONS;
    }
}
