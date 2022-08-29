<?php

namespace iCoordinator\Permissions\Resource;

use iCoordinator\Entity\Acl\AclResource;
use Laminas\Permissions\Acl\Resource\ResourceInterface;

interface HavingDynamicPermissionsResourceInterface extends ResourceInterface
{
    /**
     * @param $permissionType
     * @return null|string
     */
    public static function getPrivilegeForGrantingPermission($permissionType);

    /**
     * @return string
     */
    public static function getPrivilegeForReadingPermissions();

    public function getId();

    /**
     * @return AclResource
     */
    public function getAclResource();

    /**
     * @param AclResource $aclResource
     * @return $this
     */
    public function setAclResource(AclResource $aclResource);
}
