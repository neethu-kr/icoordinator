<?php

namespace iCoordinator\Permissions\Privilege;

use iCoordinator\Permissions\PermissionType;

class PortalPrivilege
{
    const PRIVILEGE_READ_WORKSPACES = 'read_workspaces';
    const PRIVILEGE_MANAGE_WORKSPACES = 'manage_workspaces';
    const PRIVILEGE_CREATE_WORKSPACES = 'create_workspaces';
    const PRIVILEGE_READ_USERS = 'read_users';
    const PRIVILEGE_CREATE_USERS = 'create_users';
    const PRIVILEGE_READ_GROUPS = 'read_groups';
    const PRIVILEGE_READ_USER_GROUPS = 'read_user_groups';
    const PRIVILEGE_CREATE_GROUPS = 'create_groups';
    const PRIVILEGE_CREATE_GROUP_MEMBERSHIPS = 'create_group_memberships';
    const PRIVILEGE_READ_META_FIELDS = 'read_meta_fields';
    const PRIVILEGE_CREATE_META_FIELDS = 'create_meta_fields';
    const PRIVILEGE_READ_PERMISSIONS = 'read_permissions';
    const PRIVILEGE_GRANT_ADMIN_PERMISSION = 'grant_admin_permission';
    const PRIVILEGE_GRANT_ACCESS_PERMISSION = 'grant_access_permission';
    const PRIVILEGE_GRANT_GRANT_ACCESS_PERMISSION = 'grant_grant_access_permission';

    private static $privilegesForGrantingPermission = array(
        PermissionType::PORTAL_ADMIN => self::PRIVILEGE_GRANT_ADMIN_PERMISSION,
        PermissionType::PORTAL_ACCESS=> self::PRIVILEGE_GRANT_ACCESS_PERMISSION,
        PermissionType::PORTAL_GRANT_ACCESS => self::PRIVILEGE_GRANT_GRANT_ACCESS_PERMISSION
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
