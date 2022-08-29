<?php

namespace iCoordinator\Permissions;

class BitMask
{

    private $resourceType = null;
    private $availablePermissions = array();

    public function __construct($resourceType)
    {
        $this->resourceType = $resourceType;
        $this->availablePermissions = PermissionType::getPermissionTypes($this->resourceType);
    }

    /**
     * This function will use an integer bitMask (as created by getBitMask())
     * to get array of permissions
     * @param int  $bitMask an integer representation of the users permisions.
     * @return array
     */
    public function getPermissions($bitMask = 0)
    {
        $permissions = array();
        foreach ($this->availablePermissions as $permission) {
            $permissionBitMask = PermissionType::getPermissionTypeBitMask($this->resourceType, $permission);
            if (($bitMask & $permissionBitMask) != 0) {
                array_push($permissions, $permission);
            }
        }
        return $permissions;
    }

    /**
     * This function will create and return and integer bitMask based on array of permissions
     * @param array|string $permissions
     * @return int
     * @throws \Exception
     */
    public function getBitMask($permissions)
    {
        $bitMask = 0;

        if (!is_array($permissions)) {
            $permissions = array($permissions);
        }

        foreach ($permissions as $permission) {
            if (in_array($permission, $this->availablePermissions)) {
                $permissionBitMask = PermissionType::getPermissionTypeBitMask($this->resourceType, $permission);
                $bitMask |= $permissionBitMask;
            } else {
                throw new \Exception(
                    "There is no permission called \"" . $permission .
                    "\" for resource type \"" . $this->resourceType . "\""
                );
            }
        }

        return $bitMask;
    }
}
