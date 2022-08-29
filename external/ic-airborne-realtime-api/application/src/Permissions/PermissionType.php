<?php

namespace iCoordinator\Permissions;

use iCoordinator\Entity\File;
use iCoordinator\Entity\Portal;
use iCoordinator\Entity\Workspace;

class PermissionType
{
    //portal level
    const PORTAL_ADMIN = 'admin';
    const PORTAL_ACCESS = 'access';
    const PORTAL_GRANT_ACCESS = 'grant_access';

    //workspace level
    const WORKSPACE_ADMIN = 'admin';
    const WORKSPACE_ACCESS = 'access';
    const WORKSPACE_GRANT_ACCESS = 'grant_access';

    //file level
    const FILE_READ = 'read';
    const FILE_EDIT = 'edit';
    const FILE_GRANT_READ = 'grant_read';
    const FILE_GRANT_EDIT = 'grant_edit';
    const FILE_NONE = 'none';

    private static $bitMasks = array(
        Portal::RESOURCE_ID => array(
            self::PORTAL_ADMIN => 1,
            self::PORTAL_ACCESS => 2,
            self::PORTAL_GRANT_ACCESS => 4
        ),
        Workspace::RESOURCE_ID => array(
            self::WORKSPACE_ADMIN => 1,
            self::WORKSPACE_ACCESS => 2,
            self::WORKSPACE_GRANT_ACCESS => 4
        ),
        File::RESOURCE_ID => array(
            self::FILE_READ => 1,
            self::FILE_EDIT => 2,
            self::FILE_GRANT_READ => 4,
            self::FILE_GRANT_EDIT => 8,
            self::FILE_NONE => 16
        )
    );

    public static function getPermissionTypes($resourceType)
    {
        if (isset(self::$bitMasks[$resourceType])) {
            return array_keys(self::$bitMasks[$resourceType]);
        } else {
            throw new \Exception("Resource type \"" . $resourceType . "\" doesn't exist");
        }
    }

    public static function getPermissionTypeBitMask($resourceType, $permissionType)
    {
        return self::$bitMasks[$resourceType][$permissionType];
    }
}
