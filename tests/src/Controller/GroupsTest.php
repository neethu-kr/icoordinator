<?php

namespace iCoordinator;

use iCoordinator\Config\Route\GroupsRouteConfig;
use iCoordinator\Config\Route\UsersRouteConfig;
use iCoordinator\Entity\Acl\AclResource\AclPortalResource;
use iCoordinator\Entity\Acl\AclResource\AclWorkspaceResource;
use iCoordinator\Entity\Acl\AclRole\AclGroupRole;
use iCoordinator\Entity\Acl\AclRole\AclUserRole;
use iCoordinator\Entity\Portal;
use iCoordinator\Entity\Workspace;
use iCoordinator\Permissions\BitMask;
use iCoordinator\Permissions\PermissionType;
use iCoordinator\PHPUnit\DbUnit\DataSet\ArrayDataSet;
use iCoordinator\PHPUnit\TestCase;
use Laminas\Json\Json;


class GroupsTest extends TestCase
{
    const USER_ID = 1;
    const USER_ID2 = 2;
    const USER_ID3 = 3;
    const USER_ID4 = 4;
    const PORTAL_ID = 1;
    const PORTAL_ID2 = 2;
    const WORKSPACE_ID = 1;
    const GROUP_ID = 1;
    const GROUP_ID2 = 2;
    const GROUP_ID3 = 3;
    const GROUP_ID4 = 4;
    const GROUP_MEMBERSHIP_ID = 1;
    const GROUP_MEMBERSHIP_ID2 = 2;
    const USERNAME = 'test@icoordinator.com';
    const USERNAME2 = 'test2@icoordinator.com';
    const USERNAME3 = 'test3@icoordinator.com';
    const USERNAME4 = 'test4@icoordinator.com';
    const PASSWORD = 'password';
    const PUBLIC_CLIENT_ID = 'public_test';

    protected function getDataSet()
    {
        $workspaceBitMask = new BitMask(Workspace::RESOURCE_ID);
        $portalBitMask = new BitMask(Portal::RESOURCE_ID);
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
                    'email_confirmed' => 1
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
                    'email_confirmed' => 1
                ),
                array(
                    'id' => self::USER_ID4,
                    'email' => self::USERNAME4,
                    'name' => 'John Dow',
                    'password' => password_hash(self::PASSWORD, PASSWORD_DEFAULT),
                    'email_confirmed' => 1
                )
            ),
            'email_confirmations' => array(),
            'invitations' => array(),
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
                )
            ),
            'groups' => array(
                array(
                    'id' => 1,
                    'name' => 'Test Group 1',
                    'portal_id' => self::PORTAL_ID,
                    'scope_type' => 'portal',
                    'scope_id' => self::PORTAL_ID
                ),
                array(
                    'id' => 2,
                    'name' => 'Test Group 2',
                    'portal_id' => self::PORTAL_ID,
                    'scope_type' => 'portal',
                    'scope_id' => self::PORTAL_ID
                ),
                array(
                    'id' => 3,
                    'name' => 'Test Group 3',
                    'portal_id' => self::PORTAL_ID,
                    'scope_type' => 'portal',
                    'scope_id' => self::PORTAL_ID
                ),
                array(
                    'id' => 4,
                    'name' => 'Test Group 4',
                    'portal_id' => self::PORTAL_ID,
                    'scope_type' => 'workspace',
                    'scope_id' => self::WORKSPACE_ID
                )
            ),
            'group_memberships' => array(
                array(
                    'id' => 1,
                    'user_id' => self::USER_ID,
                    'group_id' => self::GROUP_ID
                ),
                array(
                    'id' => 2,
                    'user_id' => self::USER_ID,
                    'group_id' => self::GROUP_ID2
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
                ),
                array(
                    'id' => 3,
                    'entity_type' => AclUserRole::ACL_ROLE_ENTITY_TYPE,
                    'entity_id' => self::USER_ID3
                ),
                array(
                    'id' => 4,
                    'entity_type' => AclUserRole::ACL_ROLE_ENTITY_TYPE,
                    'entity_id' => self::USER_ID4
                ),
                array(
                    'id' => 5,
                    'entity_type' => AclGroupRole::ACL_ROLE_ENTITY_TYPE,
                    'entity_id' => 1
                ),
                array(
                    'id' => 6,
                    'entity_type' => AclGroupRole::ACL_ROLE_ENTITY_TYPE,
                    'entity_id' => 3
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
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'acl_role_id' => self::USER_ID,
                    'acl_resource_id' => 2,
                    'bit_mask' => $portalBitMask->getBitMask(PermissionType::PORTAL_ACCESS),
                    'portal_id' => self::PORTAL_ID2
                ),
                array(
                    'acl_role_id' => self::USER_ID2,
                    'acl_resource_id' => 1,
                    'bit_mask' => $portalBitMask->getBitMask(PermissionType::PORTAL_ACCESS),
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'acl_role_id' => self::USER_ID4,
                    'acl_resource_id' => 1,
                    'bit_mask' => $workspaceBitMask->getBitMask(PermissionType::PORTAL_ACCESS),
                    'portal_id' => self::PORTAL_ID
                ),
                //workspace permissions
                array(
                    'acl_role_id' => self::USER_ID,
                    'acl_resource_id' => 3,
                    'bit_mask' => $workspaceBitMask->getBitMask(PermissionType::WORKSPACE_ACCESS),
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'acl_role_id' => self::USER_ID,
                    'acl_resource_id' => 4,
                    'bit_mask' => $workspaceBitMask->getBitMask(PermissionType::WORKSPACE_ACCESS),
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'acl_role_id' => self::USER_ID2,
                    'acl_resource_id' => 3,
                    'bit_mask' => $workspaceBitMask->getBitMask(PermissionType::WORKSPACE_ADMIN),
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'acl_role_id' => 5,
                    'acl_resource_id' => 3,
                    'bit_mask' => $workspaceBitMask->getBitMask(PermissionType::WORKSPACE_ACCESS),
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'acl_role_id' => 6,
                    'acl_resource_id' => 4,
                    'bit_mask' => $workspaceBitMask->getBitMask(PermissionType::WORKSPACE_ACCESS),
                    'portal_id' => self::PORTAL_ID
                )
            ),
            'files' => array(),
            'events' => array(),
            'invitation_workspace_groups' => array()
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

    public function testCreatePortalGroupByPortalAdmin()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $groupName = 'Test Group';

        $response = $this->post(
            $this->urlFor(GroupsRouteConfig::ROUTE_PORTAL_GROUP_CREATE, array('portal_id' => self::PORTAL_ID)),
            array(
                'name' => $groupName
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals($result->name, $groupName);
        $this->assertNotEmpty($result->id);
    }

    public function testCreatePortalGroupByOtherUser()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $groupName = 'Test Group';

        $response = $this->post(
            $this->urlFor(GroupsRouteConfig::ROUTE_PORTAL_GROUP_CREATE, array('portal_id' => self::PORTAL_ID)),
            array(
                'name' => $groupName
            ),
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testCreateWorkspaceGroupByWorkspaceAdmin()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $groupName = 'Test Group';

        $response = $this->post(
            $this->urlFor(GroupsRouteConfig::ROUTE_WORKSPACE_GROUP_CREATE, array('workspace_id' => self::WORKSPACE_ID)),
            array(
                'name' => $groupName
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals($result->name, $groupName);
        $this->assertNotEmpty($result->id);
    }

    public function testCreateWorkspaceGroupByOtherUser()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $groupName = 'Test Group';

        $response = $this->post(
            $this->urlFor(GroupsRouteConfig::ROUTE_WORKSPACE_GROUP_CREATE, array('workspace_id' => self::WORKSPACE_ID)),
            array(
                'name' => $groupName
            ),
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testGetPortalGroupsListByUserWithPortalAccess()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->get(
            $this->urlFor(GroupsRouteConfig::ROUTE_PORTAL_GROUPS_LIST, array('portal_id' => self::PORTAL_ID)),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(3, $result->entries);
    }

    public function testGetPortalGroupsListByUserWithoutPortalAccess()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME3, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->get(
            $this->urlFor(GroupsRouteConfig::ROUTE_PORTAL_GROUPS_LIST, array('portal_id' => self::PORTAL_ID)),
            array(),
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testGetWorkspaceGroupsListWithWorkspaceAccess()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->get(
            $this->urlFor(GroupsRouteConfig::ROUTE_WORKSPACE_GROUPS_LIST, array('workspace_id' => self::WORKSPACE_ID)),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(1, $result->entries);
    }

    public function testGetWorkspaceGroupsListWithoutWorkspaceAccess()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME4, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->get(
            $this->urlFor(GroupsRouteConfig::ROUTE_WORKSPACE_GROUPS_LIST, array('workspace_id' => self::WORKSPACE_ID)),
            array(),
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testUpdatePortalGroupByPortalAdmin()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $groupNewName = 'Test Group New Name';

        $response = $this->put(
            $this->urlFor(GroupsRouteConfig::ROUTE_GROUP_UPDATE, array('group_id' => self::GROUP_ID)),
            array(
                'name' => $groupNewName
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($result->name, $groupNewName);
        $this->assertNotEmpty($result->id);
    }

    public function testUpdatePortalGroupByOtherUser()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $groupNewName = 'Test Group New Name';

        $response = $this->put(
            $this->urlFor(GroupsRouteConfig::ROUTE_GROUP_UPDATE, array('group_id' => self::GROUP_ID)),
            array(
                'name' => $groupNewName
            ),
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testUpdateWorkspaceGroupByWorkspaceAdmin()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $groupNewName = 'Test Group New Name';

        $response = $this->put(
            $this->urlFor(GroupsRouteConfig::ROUTE_GROUP_UPDATE, array('group_id' => self::GROUP_ID4)),
            array(
                'name' => $groupNewName
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($result->name, $groupNewName);
        $this->assertNotEmpty($result->id);
    }

    public function testUpdateWorkspaceGroupByOtherUser()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $groupNewName = 'Test Group New Name';

        $response = $this->put(
            $this->urlFor(GroupsRouteConfig::ROUTE_GROUP_UPDATE, array('group_id' => self::GROUP_ID4)),
            array(
                'name' => $groupNewName
            ),
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testDeletePortalGroupByPortalAdmin()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->delete(
            $this->urlFor(GroupsRouteConfig::ROUTE_GROUP_DELETE, array('group_id' => self::GROUP_ID3)),
            array(),
            $headers
        );

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testDeletePortalGroupByOtherUser()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->delete(
            $this->urlFor(GroupsRouteConfig::ROUTE_GROUP_DELETE, array('group_id' => self::GROUP_ID3)),
            array(),
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testDeleteWorkspaceGroupByWorkspaceAdmin()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->delete(
            $this->urlFor(GroupsRouteConfig::ROUTE_GROUP_DELETE, array('group_id' => self::GROUP_ID4)),
            array(),
            $headers
        );

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testDeleteWorkspaceGroupByOtherUser()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->delete(
            $this->urlFor(GroupsRouteConfig::ROUTE_GROUP_DELETE, array('group_id' => self::GROUP_ID4)),
            array(),
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testDeleteGroupConflict()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->delete(
            $this->urlFor(GroupsRouteConfig::ROUTE_GROUP_DELETE, array('group_id' => self::GROUP_ID)),
            array(),
            $headers
        );

        $this->assertEquals(409, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testAddPortalGroupMembershipByPortalAdminForPortalUser()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->post(
            $this->urlFor(GroupsRouteConfig::ROUTE_GROUP_MEMBERSHIP_CREATE, array('portal_id' => self::PORTAL_ID)),
            array(
                'user' => array(
                    'id' => self::USER_ID2,
                ),
                'group' => array(
                    'id' => self::GROUP_ID
                )
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(self::USER_ID2, $result->user->id);
        $this->assertEquals(self::GROUP_ID, $result->group->id);
        $this->assertNotEmpty($result->id);
    }

    public function testAddPortalGroupMembershipByPortalAdminForNotPortalUser()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->post(
            $this->urlFor(GroupsRouteConfig::ROUTE_GROUP_MEMBERSHIP_CREATE, array('portal_id' => self::PORTAL_ID)),
            array(
                'user' => array(
                    'id' => self::USER_ID3,
                ),
                'group' => array(
                    'id' => self::GROUP_ID
                )
            ),
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testAddPortalGroupMembershipByOtherUser()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->post(
            $this->urlFor(GroupsRouteConfig::ROUTE_GROUP_MEMBERSHIP_CREATE, array('portal_id' => self::PORTAL_ID)),
            array(
                'user' => array(
                    'id' => self::USER_ID2,
                ),
                'group' => array(
                    'id' => self::GROUP_ID
                )
            ),
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testAddWorkspaceGroupMembershipByWorkspaceAdminForWorkspaceUser()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->post(
            $this->urlFor(GroupsRouteConfig::ROUTE_GROUP_MEMBERSHIP_CREATE, array('portal_id' => self::PORTAL_ID)),
            array(
                'user' => array(
                    'id' => self::USER_ID,
                ),
                'group' => array(
                    'id' => self::GROUP_ID4
                )
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(self::USER_ID, $result->user->id);
        $this->assertEquals(self::GROUP_ID4, $result->group->id);
        $this->assertNotEmpty($result->id);
    }

    public function testAddWorkspaceGroupMembershipByWorkspaceAdminForNotWorkspaceUser()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->post(
            $this->urlFor(GroupsRouteConfig::ROUTE_GROUP_MEMBERSHIP_CREATE, array('portal_id' => self::PORTAL_ID)),
            array(
                'user' => array(
                    'id' => self::USER_ID4,
                ),
                'group' => array(
                    'id' => self::GROUP_ID4
                )
            ),
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testAddWorkspaceGroupMembershipByOtherUser()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->post(
            $this->urlFor(GroupsRouteConfig::ROUTE_GROUP_MEMBERSHIP_CREATE, array('portal_id' => self::PORTAL_ID)),
            array(
                'user' => array(
                    'id' => self::USER_ID,
                ),
                'group' => array(
                    'id' => self::GROUP_ID4
                )
            ),
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }


    public function testRemoveGroupMembership()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->delete(
            $this->urlFor(GroupsRouteConfig::ROUTE_GROUP_MEMBERSHIP_DELETE, array(
                'group_membership_id' => self::GROUP_MEMBERSHIP_ID
            )),
            array(),
            $headers
        );

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testGetUserGroups()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->get(
            $this->urlFor(UsersRouteConfig::ROUTE_USER_GET_GROUPS_LIST, array(
                'user_id' => self::USER_ID,
                'portal_id' => self::PORTAL_ID
            )),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(2, $result->entries);
    }

    public function testGetGroupMembershipsListForUser()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->get(
            $this->urlFor(UsersRouteConfig::ROUTE_USER_GET_GROUP_MEMBERSHIPS_LIST, array(
                'user_id' => self::USER_ID,
                'portal_id' => self::PORTAL_ID
            )),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(2, $result->entries);
    }

    public function testGetGetGroupMembershipsForGroup()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->get(
            $this->urlFor(GroupsRouteConfig::ROUTE_GROUP_GET_GROUP_MEMBERSHIPS_LIST, array(
                'group_id' => self::GROUP_ID,
            )),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(1, $result->entries);
    }
}
