<?php

namespace iCoordinator;

use iCoordinator\Entity\Acl\AclPermission;
use iCoordinator\Entity\User;
use iCoordinator\Entity\Workspace;
use iCoordinator\Permissions\PermissionType;
use iCoordinator\PHPUnit\DbUnit\DataSet\ArrayDataSet;
use iCoordinator\PHPUnit\TestCase;
use iCoordinator\Service\PermissionService;


class PermissionServiceTest extends TestCase
{
    const USER_ID = 1;
    const USER_ID2 = 2;
    const USERNAME = 'test@user.com';
    const USERNAME2 = 'test2@user.com';
    const PASSWORD = 'password';
    const PUBLIC_CLIENT_ID = 'public_test';
    const PORTAL_ID = 1;

    public function testPermissionAdd()
    {
        $workspaceId = 1;

        $permissionService = $this->getPermissionService();

        $workspace  = $this->getEntityManager()->find(Workspace::getEntityName(), $workspaceId);
        $user       = $this->getEntityManager()->find(User::getEntityName(), self::USER_ID2);

        $permission = $permissionService->addPermission(
            $workspace,
            $user,
            [PermissionType::WORKSPACE_ACCESS],
            self::USER_ID,
            $workspace->getPortal()
        );

        $this->assertTrue($permission instanceof AclPermission);
        $this->assertEquals($permission->getAclRole()->getUser()->getId(), self::USER_ID2);
        $this->assertEquals($permission->getAclResource()->getWorkspace()->getId(), $workspaceId);
        $this->assertContains(PermissionType::WORKSPACE_ACCESS, $permission->getActions());
    }

    public function testPermissionUpdate()
    {
        $workspaceId = 1;

        $permissionService = $this->getPermissionService();

        $workspace = $this->getEntityManager()->find(Workspace::getEntityName(), $workspaceId);
        $user       = $this->getEntityManager()->find(User::getEntityName(), self::USER_ID2);

        $permission = $permissionService->addPermission(
            $workspace,
            $user,
            [PermissionType::WORKSPACE_ACCESS],
            self::USER_ID,
            $workspace->getPortal()
        );

        $permission = $permissionService->updatePermission(
            $permission,
            [
                PermissionType::WORKSPACE_ACCESS,
                PermissionType::WORKSPACE_ADMIN
            ],
            self::USER_ID
        );

        $this->assertTrue($permission instanceof AclPermission);
        $this->assertEquals($permission->getAclRole()->getUser()->getId(), self::USER_ID2);
        $this->assertEquals($permission->getAclResource()->getWorkspace()->getId(), $workspaceId);
        $this->assertContains(PermissionType::WORKSPACE_ACCESS, $permission->getActions());
        $this->assertContains(PermissionType::WORKSPACE_ADMIN, $permission->getActions());
    }

    public function testRemovePermission()
    {
        $workspaceId = 1;

        $permissionService = $this->getPermissionService();

        $workspace = $this->getEntityManager()->find(Workspace::getEntityName(), $workspaceId);
        $user       = $this->getEntityManager()->find(User::getEntityName(), self::USER_ID2);

        $permission = $permissionService->addPermission(
            $workspace,
            $user,
            [PermissionType::WORKSPACE_ACCESS],
            self::USER_ID,
            $workspace->getPortal()
        );

        $permissionId = $permission->getId();

        $permissionService->deletePermission($permission, self::USER_ID);

        $permission = $permissionService->getPermission($permissionId);

        $this->assertTrue($permission->isIsDeleted());
    }

    public function testHasPermission()
    {
        $workspaceId = 1;

        $permissionService = $this->getPermissionService();

        $workspace  = $this->getEntityManager()->find(Workspace::getEntityName(), $workspaceId);
        $user       = $this->getEntityManager()->find(User::getEntityName(), self::USER_ID2);

        $permissionService = $this->getContainer()->get('PermissionService');
        $permission = $permissionService->addPermission(
            $workspace,
            $user,
            [
                PermissionType::WORKSPACE_ACCESS,
                PermissionType::WORKSPACE_GRANT_ACCESS
            ],
            self::USER_ID,
            $workspace->getPortal()
        );

        //create new entity manager
        $workspace = $this->getEntityManager()->find(Workspace::ENTITY_NAME, $workspaceId);

        $this->assertTrue($permissionService->hasPermission(
            self::USER_ID2,
            $workspace,
            [PermissionType::WORKSPACE_ACCESS],
            $workspace->getPortal()
        ));

        $this->assertTrue($permissionService->hasPermission(
            self::USER_ID2,
            $workspace,
            [PermissionType::WORKSPACE_GRANT_ACCESS],
            $workspace->getPortal()
        ));

        $this->assertFalse($permissionService->hasPermission(
            self::USER_ID2,
            $workspace,[PermissionType::WORKSPACE_ADMIN],
            $workspace->getPortal()
        ));
    }

    public function testAclResourceUsers()
    {
        //create group with one user
        $groupService = $this->getContainer()->get('GroupService');
        $group = $groupService->createPortalGroup(self::PORTAL_ID, array('name' => 'Test Group'), self::USER_ID);
        $groupService->createGroupMembership($group, self::USER_ID2, self::USER_ID);

        $workspaceId = 1;

        $workspace  = $this->getEntityManager()->find(Workspace::getEntityName(), $workspaceId);
        $user       = $this->getEntityManager()->find(User::getEntityName(), self::USER_ID);


        //add user permission
        $permissionService = $this->getContainer()->get('PermissionService');
        $permission = $permissionService->addPermission(
            $workspace,
            $user,
            [PermissionType::WORKSPACE_ACCESS],
            self::USER_ID,
            $workspace->getPortal()
        );


        //add group permission
        $permissionService = $this->getContainer()->get('PermissionService');
        $permission = $permissionService->addPermission(
            $workspace,
            $group,
            [PermissionType::WORKSPACE_ACCESS],
            self::USER_ID,
            $workspace->getPortal()
        );

        $aclResourceUsers = $permissionService->getResourceUsers($workspace);

        $this->assertCount(2, $aclResourceUsers);
    }

    /**
     * @return PermissionService
     */
    private function getPermissionService()
    {
        return $this->getContainer()->get('PermissionService');
    }


    protected function getDataSet()
    {
        return new ArrayDataSet(array(
            'users' => array(
                array(
                    'id' => self::USER_ID,
                    'email' => self::USERNAME,
                    'password' => password_hash(self::PASSWORD, PASSWORD_DEFAULT),
                    'email_confirmed' => 1
                ),
                array(
                    'id' => self::USER_ID2,
                    'email' => self::USERNAME2,
                    'password' => password_hash(self::PASSWORD, PASSWORD_DEFAULT),
                    'email_confirmed' => 1
                )
            ),
            'portals' => array(
                array(
                    'id' => 1,
                    'name' => 'Test Portal',
                    'owned_by' => self::USER_ID
                )
            ),
            'workspaces' => array(
                array(
                    'id' => 1,
                    'name' => 'Workspace 1',
                    'portal_id' => self::PORTAL_ID
                ),
            ),
            'acl_permissions' => array(),
            'acl_resources' => array(),
            'acl_roles' => array(),
            'events' => array()
        ));
    }
}
