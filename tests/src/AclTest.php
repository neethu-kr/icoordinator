<?php

namespace iCoordinator;

use iCoordinator\Config\Route\AuthRouteConfig;
use iCoordinator\Entity\Acl\AclResource\AclPortalResource;
use iCoordinator\Entity\Acl\AclRole\AclUserRole;
use iCoordinator\Entity\Portal;
use iCoordinator\Entity\Workspace;
use iCoordinator\Permissions\Acl;
use iCoordinator\Permissions\BitMask;
use iCoordinator\Permissions\PermissionType;
use iCoordinator\Permissions\Privilege\PortalPrivilege;
use iCoordinator\Permissions\Resource\RouteResource;
use iCoordinator\Permissions\Role\GuestRole;
use iCoordinator\Permissions\Role\UserRole;
use iCoordinator\PHPUnit\DbUnit\DataSet\ArrayDataSet;
use iCoordinator\PHPUnit\TestCase;

class AclTest extends TestCase
{
    const USER_ID = 1;
    const USER_ID2 = 2;
    const PORTAL_ID = 1;

    public function testUnauthorizedRoleAcl()
    {
        $acl = $this->getContainer()->get('acl');

        $router = $this->getContainer()->get('router');

        $authRoute = $router->getNamedRoute(AuthRouteConfig::ROUTE_AUTH_TOKEN);
        $protectedRoute = $router->getNamedRoute(AuthRouteConfig::ROUTE_AUTH_PROTECTED_RESOURCE);

        $this->assertTrue($acl->isAllowed(GuestRole::ROLE_ID, new RouteResource($authRoute)));
        $this->assertFalse($acl->isAllowed(GuestRole::ROLE_ID, new RouteResource($protectedRoute)));
    }

    public function testBitMask()
    {
        $resourceType = Workspace::RESOURCE_ID;
        $permissions = PermissionType::getPermissionTypes($resourceType);
        $howManyPermissionsRemove = rand(0, count($permissions) - 1);

        for ($i = 0; $i < $howManyPermissionsRemove; $i++) {
            unset($permissions[rand(0, count($permissions) - 1)]);
        }

        $bitMask = new BitMask($resourceType);
        $bitMaskValue = $bitMask->getBitMask($permissions);
        $permissionsFromBitMask = $bitMask->getPermissions($bitMaskValue);

        $this->assertEmpty(array_diff($permissionsFromBitMask, $permissions));
    }

    public function testCreateWorkspacePrivilege()
    {
        /** @var Acl $acl */
        $acl = $this->getContainer()->get('acl');

        $portalService = $this->getContainer()->get('PortalService');
        $portal = $portalService->getPortal(self::PORTAL_ID);

        $this->assertTrue($acl->isAllowed(
            new UserRole(self::USER_ID),
            $portal,
            PortalPrivilege::PRIVILEGE_CREATE_WORKSPACES
        ));
    }

    protected function getDataSet()
    {
        return new ArrayDataSet(array(
            'users' => array(
                array(
                    'id' => self::USER_ID,
                    'email' => '1@test.com',
                    'password' => 'qwe',
                    'email_confirmed' => 1
                ),
                array(
                    'id' => self::USER_ID2,
                    'email' => '2@test.com',
                    'password' => 'qwe',
                    'email_confirmed' => 1
                )
            ),
            'portals' => array(
                array(
                    'id' => self::PORTAL_ID,
                    'owned_by' => self::USER_ID2,
                    'name' => 'Test Portal'
                )
            ),
            'acl_roles' => array(
                array(
                    'id' => 1,
                    'entity_type' => AclUserRole::ACL_ROLE_ENTITY_TYPE,
                    'entity_id' => self::USER_ID
                )
            ),
            'acl_resources' => array(
                array(
                    'id' => 1,
                    'entity_type' => AclPortalResource::ACL_RESOURCE_ENTITY_TYPE,
                    'entity_id' => self::PORTAL_ID
                )
            ),
            'acl_permissions' => array(
                //workspace permissions
                array(
                    'acl_role_id' => self::USER_ID,
                    'acl_resource_id' => 1,
                    'bit_mask' => PermissionType::getPermissionTypeBitMask(
                        Portal::RESOURCE_ID,
                        PermissionType::PORTAL_ADMIN
                    ),
                    'portal_id' => self::PORTAL_ID
                )
            )
        ));
    }
}