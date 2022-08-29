<?php

namespace iCoordinator;

use iCoordinator\Entity\User;
use iCoordinator\Permissions\PermissionType;
use iCoordinator\PHPUnit\DbUnit\DataSet\ArrayDataSet;
use iCoordinator\PHPUnit\TestCase;

class PortalServiceTest extends TestCase
{
    const USER_ID = 1;
    const USER_ID2 = 2;
    const USERNAME = 'test@user.com';
    const USERNAME2 = 'test2@user.com';
    const PASSWORD = 'password';
    const PUBLIC_CLIENT_ID = 'public_test';

    public function testCreatePortal()
    {
        $name =  'Test Portal';
        $portalService = $this->getContainer()->get('PortalService');
        $portal = $portalService->createPortal(array(
            'name' => $name
        ), self::USER_ID);

        $this->assertEquals($name, $portal->getName());
        $this->assertEquals(self::USER_ID, $portal->getOwnedBy()->getId());
    }

    public function testGetUserPortals()
    {
        //add portal
        $name =  'Test Portal';
        $portalService = $this->getContainer()->get('PortalService');
        $portal = $portalService->createPortal(array(
            'name' => $name
        ), self::USER_ID);

        $user = $this->getEntityManager()->find(User::getEntityName(), self::USER_ID2);

        //add permission for User 2
        $permissionService = $this->getContainer()->get('PermissionService');
        $permission = $permissionService->addPermission(
            $portal,
            $user,
            [PermissionType::PORTAL_ACCESS],
            self::USER_ID,
            $portal
        );

        $portals1 = $portalService->getPortalsAvailableForUser(self::USER_ID, true);
        $portals2 = $portalService->getPortalsAvailableForUser(self::USER_ID2, true);

        $this->assertCount(1, $portals1);
        $this->assertCount(1, $portals2);
    }

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    protected function getDataSet()
    {
        return new ArrayDataSet(array(
            'oauth_clients' => array(
                array(
                    'client_id' => self::PUBLIC_CLIENT_ID
                )
            ),
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
            'acl_resources' => array(),
            'acl_roles' => array(),
            'acl_permissions' => array(),
            'email_confirmations' => array(),
            'workspaces' => array(),
            'events' => array(),
            'portals' => array()
        ));
    }
}
