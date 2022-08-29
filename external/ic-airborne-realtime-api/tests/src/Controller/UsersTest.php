<?php

namespace iCoordinator;

use iCoordinator\Config\Route\PortalsRouteConfig;
use iCoordinator\Config\Route\UsersRouteConfig;
use iCoordinator\Config\Route\WorkspacesRouteConfig;
use iCoordinator\Entity\Acl\AclResource\AclPortalResource;
use iCoordinator\Entity\Acl\AclResource\AclWorkspaceResource;
use iCoordinator\Entity\Acl\AclRole\AclUserRole;
use iCoordinator\Entity\Portal;
use iCoordinator\Entity\Workspace;
use iCoordinator\Permissions\BitMask;
use iCoordinator\Permissions\PermissionType;
use iCoordinator\PHPUnit\DbUnit\DataSet\ArrayDataSet;
use iCoordinator\PHPUnit\TestCase;
use Rhumsaa\Uuid\Uuid;
use Laminas\Json\Json;

class UsersTest extends TestCase
{
    const USER_ID = 1;
    const USER_ID2 = 2;
    const USER_ID3 = 3;
    const PORTAL_ID = 1;
    const PORTAL_ID2 = 2;
    const USERNAME = 'test@icoordinator.com';
    const USERNAME2 = 'test2@icoordinator.com';
    const USERNAME3 = 'test3@icoordinator.com';
    const PASSWORD = 'password';
    const PUBLIC_CLIENT_ID = 'public_test';

    protected function getDataSet()
    {
        $workspaceBitMask = new BitMask(Workspace::RESOURCE_ID);
        $portalBitMask = new BitMask(Portal::RESOURCE_ID);
        $uuid1 = Uuid::uuid4()->toString();
        return new ArrayDataSet(array(
            'oauth_clients' => array(
                array(
                    'client_id' => self::PUBLIC_CLIENT_ID
                )
            ),
            'oauth_access_tokens' => array(),
            'users' => array(
                array(
                    'id' => self::USER_ID,
                    'email' => self::USERNAME,
                    'name' => 'John Dow',
                    'password' => password_hash(self::PASSWORD, PASSWORD_DEFAULT),
                    'email_confirmed' => 1,
                    'uuid' => $uuid1
                ),
                array(
                    'id' => self::USER_ID2,
                    'email' => self::USERNAME2,
                    'name' => 'John Dow',
                    'password' => password_hash(self::PASSWORD, PASSWORD_DEFAULT),
                    'email_confirmed' => 1
                ),
                array(
                    'id' => self::USER_ID3,
                    'email' => self::USERNAME3,
                    'name' => 'John Dow',
                    'password' => password_hash(self::PASSWORD, PASSWORD_DEFAULT),
                    'email_confirmed' => 0
                )
            ),
            'user_locales' => array(
                array(
                    'id' => self::USER_ID,
                    'user_id' => self::USER_ID,
                    'lang' => 'en',
                    'date_format' => 'dd/mm/yyyy',
                    'time_format' => 'HH:MM',
                    'first_week_day' => 1
                ),
                array(
                    'id' => self::USER_ID2,
                    'user_id' => self::USER_ID2,
                    'lang' => 'en',
                    'date_format' => 'dd/mm/yyyy',
                    'time_format' => 'HH:MM',
                    'first_week_day' => 1
                ),
                array(
                    'id' => self::USER_ID3,
                    'user_id' => self::USER_ID3,
                    'lang' => 'en',
                    'date_format' => 'dd/mm/yyyy',
                    'time_format' => 'HH:MM',
                    'first_week_day' => 1
                )
            ),
            'portals' => array(
                array(
                    'id' => 1,
                    'name' => 'Test Portal',
                    'owned_by' => self::USER_ID
                ),
                array(
                    'id' => 2,
                    'name' => 'Test Portal 2',
                    'owned_by' => self::USER_ID2
                )
            ),
            'workspaces' => array(
                array(
                    'id' => 1,
                    'name' => 'Workspace 1',
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'id' => 2,
                    'name' => 'Workspace 2',
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'id' => 3,
                    'name' => 'Workspace 3',
                    'portal_id' => self::PORTAL_ID
                )
            ),
            'acl_roles' => array(
                array(
                    'id' => 1,
                    'entity_type' => AclUserRole::ACL_ROLE_ENTITY_TYPE,
                    'entity_id' => self::USER_ID
                ),
                array(
                    'id' => 2,
                    'entity_type' => AclUserRole::ACL_ROLE_ENTITY_TYPE,
                    'entity_id' => self::USER_ID2
                )
            ),
            'acl_resources' => array(
                array(
                    'id' => 1,
                    'entity_type' => AclPortalResource::ACL_RESOURCE_ENTITY_TYPE,
                    'entity_id' => self::PORTAL_ID
                ),
                array(
                    'id' => 2,
                    'entity_type' => AclPortalResource::ACL_RESOURCE_ENTITY_TYPE,
                    'entity_id' => self::PORTAL_ID2
                ),
                array(
                    'id' => 3,
                    'entity_type' => AclWorkspaceResource::ACL_RESOURCE_ENTITY_TYPE,
                    'entity_id' => 1
                ),
                array(
                    'id' => 4,
                    'entity_type' => AclWorkspaceResource::ACL_RESOURCE_ENTITY_TYPE,
                    'entity_id' => 2
                )
            ),
            'acl_permissions' => array(
                //portal permissions
                array(
                    'acl_role_id' => self::USER_ID,
                    'acl_resource_id' => 1,
                    'bit_mask' => $portalBitMask->getBitMask(PermissionType::PORTAL_ADMIN),
                    'is_deleted' => 0,
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'acl_role_id' => self::USER_ID2,
                    'acl_resource_id' => 1,
                    'bit_mask' => $portalBitMask->getBitMask(PermissionType::PORTAL_ACCESS),
                    'is_deleted' => 0,
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'acl_role_id' => self::USER_ID2,
                    'acl_resource_id' => 2,
                    'bit_mask' => $portalBitMask->getBitMask(PermissionType::PORTAL_ACCESS),
                    'is_deleted' => 0,
                    'portal_id' => self::PORTAL_ID2
                ),
                //workspace permissions
                array(
                    'acl_role_id' => self::USER_ID2,
                    'acl_resource_id' => 3,
                    'bit_mask' => $workspaceBitMask->getBitMask(PermissionType::WORKSPACE_ACCESS),
                    'is_deleted' => 1,
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'acl_role_id' => self::USER_ID,
                    'acl_resource_id' => 3,
                    'bit_mask' => $workspaceBitMask->getBitMask(PermissionType::WORKSPACE_ACCESS),
                    'is_deleted' => 0,
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'acl_role_id' => self::USER_ID,
                    'acl_resource_id' => 4,
                    'bit_mask' => $workspaceBitMask->getBitMask(PermissionType::WORKSPACE_ACCESS),
                    'is_deleted' => 0,
                    'portal_id' => self::PORTAL_ID
                )
            ),
            'files' => array(),
            'events' => array(),
            'portal_allowed_clients' => [
                array(
                    'id' => 1,
                    'uuid' => $uuid1,
                    'portal_id' => self::PORTAL_ID,
                    'user_id' => self::USER_ID,
                    'mobile' => false,
                    'desktop' => true
                )
            ]
        ));
    }

    public function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function testGetPortalUsers()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->get(
            $this->urlFor(PortalsRouteConfig::ROUTE_PORTAL_USERS_LIST, array('portal_id' => self::PORTAL_ID)),
            array(),
            $headers
        );

        $result = json_decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(2, $result);
    }

    public function testGetWorkspaceUsers()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        //for Workspace 1
        $workspaceId = 1;

        $response = $this->get(
            $this->urlFor(WorkspacesRouteConfig::ROUTE_WORKSPACE_USERS_LIST, array('workspace_id' => $workspaceId)),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(1, $result);
    }

    public function testUserGet()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->get(
            $this->urlFor(UsersRouteConfig::ROUTE_USER_GET, array('user_id' => self::USER_ID2)),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(self::USER_ID2, $result->id);
    }

    public function testCurrentUserGet()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->get(
            $this->urlFor(UsersRouteConfig::ROUTE_USER_CURRENT_GET),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(self::USER_ID2, $result->id);
    }

    public function testUserUpdateLocaleInformation()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $lang = 'se';
        $date_format = 'm/d/y';
        $time_format = 'H:i';
        $first_week_day = 1;
        $locale = array('lang' => $lang,
            'date_format' => $date_format,
            'time_format' => $time_format,
            'first_week_day' => $first_week_day);
        $response = $this->put(
            $this->urlFor(UsersRouteConfig::ROUTE_USER_UPDATE, array('user_id' => self::USER_ID)),
            array(
                'locale' => $locale
            ),
            $headers
        );

        $result = Json::decode($response->getBody());


        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($result->id);
        $this->assertEquals($lang, $result->locale->lang);
        $this->assertEquals($date_format, $result->locale->date_format);
        $this->assertEquals($time_format, $result->locale->time_format);
        $this->assertEquals($first_week_day, $result->locale->first_week_day);
    }

    public function testUserResetPassword()
    {
        $response = $this->post(
            $this->urlFor(UsersRouteConfig::ROUTE_USER_RESET_PASSWORD)
            ,array('email' => self::USERNAME)
            ,array()
        );

        $result = Json::decode($response->getBody());


        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($result->id);
    }

    public function testUserChangePassword()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->put(
            $this->urlFor(UsersRouteConfig::ROUTE_USER_UPDATE, array('user_id' => self::USER_ID)),
            array(
                'password' => 'password3'
            ),
            $headers
        );

        $result = Json::decode($response->getBody());


        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($result->id);
        $this->assertNotFalse(
            $this->getAuthorizationHeaders(self::USERNAME, 'password3', self::PUBLIC_CLIENT_ID)
        );
    }

    public function testUserChangeOwnPassword()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->put(
            $this->urlFor(UsersRouteConfig::ROUTE_USER_UPDATE, array('user_id' => self::USER_ID2)),
            array(
                'password' => 'password3'
            ),
            $headers
        );

        $result = Json::decode($response->getBody());


        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($result->id);
        $this->assertNotFalse(
            $this->getAuthorizationHeaders(self::USERNAME2, 'password3', self::PUBLIC_CLIENT_ID)
        );
    }

    public function testUserChangePasswordAccessDenied()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->put(
            $this->urlFor(UsersRouteConfig::ROUTE_USER_UPDATE, array('user_id' => self::USER_ID)),
            array(
                'password' => 'password3'
            ),
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testPortalAdminChangeUserPassword()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->put(
            $this->urlFor(UsersRouteConfig::ROUTE_USER_UPDATE, array('user_id' => self::USER_ID2)),
            array(
                'password' => 'password3'
            ),
            $headers
        );

        $result = Json::decode($response->getBody());


        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($result->id);
        $this->assertNotFalse(
            $this->getAuthorizationHeaders(self::USERNAME2, 'password3', self::PUBLIC_CLIENT_ID)
        );
    }

    public function testUserSelfDelete()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->delete(
            $this->urlFor(UsersRouteConfig::ROUTE_USER_DELETE, array('user_id' => self::USER_ID)),
            null,
            $headers
        );

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testUserDelete()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->delete(
            $this->urlFor(UsersRouteConfig::ROUTE_USER_DELETE, array('user_id' => self::USER_ID2)),
            null,
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testUserDeleteAccessDenied()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->delete(
            $this->urlFor(UsersRouteConfig::ROUTE_USER_DELETE, array('user_id' => self::USER_ID)),
            null,
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }
}
