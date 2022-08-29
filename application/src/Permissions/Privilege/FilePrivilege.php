<?php

namespace iCoordinator\Permissions\Privilege;

use iCoordinator\Permissions\PermissionType;

class FilePrivilege
{
    const PRIVILEGE_MODIFY = 'modify';
    const PRIVILEGE_DELETE = 'delete';
    const PRIVILEGE_READ = 'read';
    const PRIVILEGE_READ_VERSIONS = 'read_versions';
    const PRIVILEGE_DOWNLOAD = 'download';
    const PRIVILEGE_CREATE_FILES = 'create_files';
    const PRIVILEGE_CREATE_FOLDERS = 'create_folders';
    const PRIVILEGE_CREATE_SMART_FOLDERS = 'create_smart_folders';
    const PRIVILEGE_READ_META_FIELDS_VALUES = 'read_meta_fields_values';
    const PRIVILEGE_ADD_META_FIELDS_VALUES = 'add_meta_fields_values';
    const PRIVILEGE_READ_META_FIELDS_CRITERIA = 'read_meta_fields_criteria';
    const PRIVILEGE_ADD_META_FIELDS_CRITERIA = 'add_meta_fields_criteria';
    const PRIVILEGE_READ_PERMISSIONS = 'read_permissions';
    const PRIVILEGE_GRANT_READ_PERMISSION = 'grant_read_permission';
    const PRIVILEGE_GRANT_EDIT_PERMISSION = 'grant_edit_permission';
    const PRIVILEGE_GRANT_GRANT_READ_PERMISSION = 'grant_grant_read_permission';
    const PRIVILEGE_GRANT_GRANT_EDIT_PERMISSION = 'grant_grant_edit_permission';
    const PRIVILEGE_CREATE_SHARED_LINK = 'create_shared_link';

//    const RESOURCE_ID = 'file';

    private static $privilegesForGrantingPermission = array(
        PermissionType::FILE_READ => self::PRIVILEGE_GRANT_READ_PERMISSION,
        PermissionType::FILE_EDIT => self::PRIVILEGE_GRANT_EDIT_PERMISSION,
        PermissionType::FILE_GRANT_READ => self::PRIVILEGE_GRANT_GRANT_READ_PERMISSION,
        PermissionType::FILE_GRANT_EDIT => self::PRIVILEGE_GRANT_GRANT_EDIT_PERMISSION
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
