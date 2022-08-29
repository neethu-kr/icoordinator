<?php

namespace iCoordinator;

use iCoordinator\Config\Route\FilesRouteConfig;
use iCoordinator\Config\Route\FoldersRouteConfig;
use iCoordinator\Config\Route\PermissionsRouteConfig;
use iCoordinator\Config\Route\PortalsRouteConfig;
use iCoordinator\Config\Route\WorkspacesRouteConfig;
use iCoordinator\Entity\Acl\AclResource\AclPortalResource;
use iCoordinator\Entity\Acl\AclResource\AclWorkspaceResource;
use iCoordinator\Entity\Acl\AclRole\AclGroupRole;
use iCoordinator\Entity\Acl\AclRole\AclUserRole;
use iCoordinator\Entity\Group;
use iCoordinator\Entity\Portal;
use iCoordinator\Entity\User;
use iCoordinator\Entity\Workspace;
use iCoordinator\Permissions\BitMask;
use iCoordinator\Permissions\PermissionType;
use iCoordinator\PHPUnit\DbUnit\DataSet\ArrayDataSet;
use iCoordinator\PHPUnit\Helper\FileHelper;
use iCoordinator\PHPUnit\TestCase;
use Laminas\Json\Json;


class PermissionsTest extends TestCase
{
    const USER_ID = 1;
    const USER_ID2 = 2;
    const USER_ID3 = 3;
    const USER_ID4 = 4;
    const USERNAME = 'test@user.com';
    const USERNAME2 = 'test2@user.com';
    const USERNAME3 = 'test3@user.com';
    const USERNAME4 = 'test4@user.com';
    const PASSWORD = 'password';
    const PUBLIC_CLIENT_ID = 'public_test';
    const PORTAL_ID = 1;
    const GROUP_ID = 1;
    const GROUP_ID2 = 2;
    const PERMISSION_ID = 1;

    public function setUp(): void
    {
        parent::setUp();

        FileHelper::initializeFileMocks($this);
    }

    public function testPermissionGet()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        //getting with access
        $response = $this->get(
            $this->urlFor(PermissionsRouteConfig::ROUTE_PERMISSION_GET, array('permission_id' => 3)),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(AclUserRole::ACL_ROLE_ENTITY_TYPE, $result->grant_to->entity_type);
        $this->assertEquals(3, $result->id);

        //getting without access

        $headers = $this->getAuthorizationHeaders(self::USERNAME4, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->get(
            $this->urlFor(PermissionsRouteConfig::ROUTE_PERMISSION_GET, array('permission_id' => 3)),
            array(),
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());

        //getting non-existing

        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);
        $response = $this->get(
            $this->urlFor(PermissionsRouteConfig::ROUTE_PERMISSION_GET, array('permission_id' => 101)),
            array(),
            $headers
        );

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testWorkspacePermissionAdd()
    {
        //create by portal admin
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->post(
            $this->urlFor(WorkspacesRouteConfig::ROUTE_WORKSPACE_PERMISSION_ADD, array('workspace_id' => 4)),
            array(
                'grant_to' => array(
                    'entity_type' => AclUserRole::ACL_ROLE_ENTITY_TYPE,
                    'entity_id' => self::USER_ID2
                ),
                'actions' => array(
                    PermissionType::WORKSPACE_ACCESS,
                    PermissionType::WORKSPACE_GRANT_ACCESS
                )
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertNotEmpty($result->id);

        //fetching newly created permission

        $response = $this->get(
            $this->urlFor(PermissionsRouteConfig::ROUTE_PERMISSION_GET, array('permission_id' => $result->id)),
            array(),
            $headers
        );
        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($result->id);
    }


    public function testWorkspacePermissionAddForPortalGroup()
    {
        //create by portal admin
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->post(
            $this->urlFor(WorkspacesRouteConfig::ROUTE_WORKSPACE_PERMISSION_ADD, array('workspace_id' => 4)),
            array(
                'grant_to' => array(
                    'entity_type' => AclGroupRole::ACL_ROLE_ENTITY_TYPE,
                    'entity_id' => self::GROUP_ID
                ),
                'actions' => array(
                    PermissionType::WORKSPACE_ACCESS,
                    PermissionType::WORKSPACE_GRANT_ACCESS
                )
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertNotEmpty($result->id);

        //fetching newly created permission

        $response = $this->get(
            $this->urlFor(PermissionsRouteConfig::ROUTE_PERMISSION_GET, array('permission_id' => $result->id)),
            array(),
            $headers
        );
        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($result->id);
    }


    public function testWorkspacePermissionAddForWorkspaceGroup()
    {
        //create by portal admin
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->post(
            $this->urlFor(WorkspacesRouteConfig::ROUTE_WORKSPACE_PERMISSION_ADD, array('workspace_id' => 4)),
            array(
                'grant_to' => array(
                    'entity_type' => AclGroupRole::ACL_ROLE_ENTITY_TYPE,
                    'entity_id' => self::GROUP_ID2
                ),
                'actions' => array(
                    PermissionType::WORKSPACE_ACCESS,
                    PermissionType::WORKSPACE_GRANT_ACCESS
                )
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertNotEmpty($result->id);

        //fetching newly created permission

        $response = $this->get(
            $this->urlFor(PermissionsRouteConfig::ROUTE_PERMISSION_GET, array('permission_id' => $result->id)),
            array(),
            $headers
        );
        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($result->id);
    }


    public function testWorkspacePermissionAddForOtherWorkspaceGroup()
    {
        //create by portal admin
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->post(
            $this->urlFor(WorkspacesRouteConfig::ROUTE_WORKSPACE_PERMISSION_ADD, array('workspace_id' => 1)),
            array(
                'grant_to' => array(
                    'entity_type' => AclGroupRole::ACL_ROLE_ENTITY_TYPE,
                    'entity_id' => self::GROUP_ID2
                ),
                'actions' => array(
                    PermissionType::WORKSPACE_ACCESS,
                    PermissionType::WORKSPACE_GRANT_ACCESS
                )
            ),
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }


    public function testGrandWorkspaceAdminPermission()
    {
        //create by portal admin
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->post(
            $this->urlFor(WorkspacesRouteConfig::ROUTE_WORKSPACE_PERMISSION_ADD, array('workspace_id' => 4)),
            array(
                'grant_to' => array(
                    'entity_type' => AclUserRole::ACL_ROLE_ENTITY_TYPE,
                    'entity_id' => self::USER_ID2
                ),
                'actions' => array(
                    PermissionType::WORKSPACE_ADMIN
                )
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertNotEmpty($result->id);
    }


    public function testFilePermissionAdd()
    {
        $workspaceId = 1;
        $file = FileHelper::createFile($this->getContainer(), $workspaceId, self::USER_ID);

        //create by portal admin
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_PERMISSION_ADD, array('file_id' => $file->getId())),
            array(
                'grant_to' => array(
                    'entity_type' => AclUserRole::ACL_ROLE_ENTITY_TYPE,
                    'entity_id' => self::USER_ID2
                ),
                'actions' => array(
                    PermissionType::FILE_READ
                )
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertNotEmpty($result->id);
    }

    public function testFolderPermissionAdd()
    {
        $workspaceId = 1;
        $folder = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID);

        //create by portal admin
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->post(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_PERMISSION_ADD, array('folder_id' => $folder->getId())),
            array(
                'grant_to' => array(
                    'entity_type' => AclUserRole::ACL_ROLE_ENTITY_TYPE,
                    'entity_id' => self::USER_ID2
                ),
                'actions' => array(
                    PermissionType::FILE_READ,
                    PermissionType::FILE_EDIT
                )
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertNotEmpty($result->id);
    }

    public function testPortalPermissionAdd()
    {
        //create by portal admin
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->post(
            $this->urlFor(PortalsRouteConfig::ROUTE_PORTAL_PERMISSION_ADD, array('portal_id' => self::PORTAL_ID)),
            array(
                'grant_to' => array(
                    'entity_type' => AclUserRole::ACL_ROLE_ENTITY_TYPE,
                    'entity_id' => self::USER_ID3
                ),
                'actions' => array(
                    PermissionType::PORTAL_ADMIN
                )
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertNotEmpty($result->id);
    }

    public function testPermissionUpdate()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->put(
            $this->urlFor(PermissionsRouteConfig::ROUTE_PERMISSION_UPDATE, array('permission_id' => self::PERMISSION_ID)),
            array(
                'actions' => array(
                    PermissionType::PORTAL_ACCESS
                )
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains(PermissionType::PORTAL_ACCESS, $result->actions);
    }

    public function testPermissionUpdateToNone()
    {
        $workspaceId = 1;
        $file = FileHelper::createFile($this->getContainer(), $workspaceId, self::USER_ID);

        //create by portal admin
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_PERMISSION_ADD, array('file_id' => $file->getId())),
            array(
                'grant_to' => array(
                    'entity_type' => AclUserRole::ACL_ROLE_ENTITY_TYPE,
                    'entity_id' => self::USER_ID2
                ),
                'actions' => array(
                    PermissionType::FILE_READ
                )
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertNotEmpty($result->id);

        $response = $this->put(
            $this->urlFor(PermissionsRouteConfig::ROUTE_PERMISSION_UPDATE, array('permission_id' => $result->id)),
            array(
                'actions' => array(
                    PermissionType::FILE_NONE
                )
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains(PermissionType::FILE_NONE, $result->actions);
    }

    public function testPermissionUpdateGroupToNone()
    {
        $workspaceId = 1;

        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        //create folder
        $parentFolder = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID);
        $user = $this->getEntityManager()->find(User::getEntityName(), self::USER_ID2);
        $group = $this->getEntityManager()->find(Group::getEntityName(), self::GROUP_ID);

        //share folder with USER_ID2
        $permissionsService = $this->getContainer()->get('PermissionService');
        $permissionsService->addPermission(
            $parentFolder,
            $user,
            ['edit'],
            self::USER_ID,
            $parentFolder->getWorkspace()->getPortal()
        );

        //create another folder which should only return the lowest level permission for the folder
        $folder = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID, $parentFolder);
        $permissionsService->addPermission(
            $folder,
            $user,
            ['read'],
            self::USER_ID,
            $folder->getWorkspace()->getPortal()
        );
        $permission = $permissionsService->addPermission(
            $folder,
            $group,
            ['read'],
            self::USER_ID,
            $folder->getWorkspace()->getPortal()
        );

        $response = $this->put(
            $this->urlFor(PermissionsRouteConfig::ROUTE_PERMISSION_UPDATE, array('permission_id' => $permission->getId() )),
            array(
                'actions' => array(
                    PermissionType::FILE_NONE
                )
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        // 200 if set to none, 204 if deleted
        $this->assertEquals(200, $response->getStatusCode());

    }

    public function testAddExistingGroupPermission()
    {
        $workspaceId = 1;

        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        //create folder
        $parentFolder = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID);
        $user = $this->getEntityManager()->find(User::getEntityName(), self::USER_ID);
        $group = $this->getEntityManager()->find(Group::getEntityName(), self::GROUP_ID2);

        //share folder with USER_ID2
        $permissionsService = $this->getContainer()->get('PermissionService');
        $permissionsService->addPermission(
            $parentFolder,
            $user,
            ['edit'],
            self::USER_ID,
            $parentFolder->getWorkspace()->getPortal()
        );

        //create another folder which should only return the lowest level permission for the folder
        $folder = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID, $parentFolder);
        $permissionsService->addPermission(
            $folder,
            $user,
            ['edit'],
            self::USER_ID,
            $folder->getWorkspace()->getPortal()
        );
        $permission = $permissionsService->addPermission(
            $folder,
            $group,
            ['edit'],
            self::USER_ID,
            $folder->getWorkspace()->getPortal()
        );
        $permissionsService->clearCache();
        $permission = $permissionsService->addPermission(
            $folder,
            $group,
            ['none'],
            self::USER_ID,
            $folder->getWorkspace()->getPortal()
        );

    }

    public function testPortalPermissionsGetList()
    {
        //create by portal admin
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->get(
            $this->urlFor(PortalsRouteConfig::ROUTE_PORTAL_PERMISSIONS_LIST, array('portal_id' => self::PORTAL_ID)),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(3, $result);
    }

    public function testWorkspacePermissionsGetList()
    {
        $workspaceId = 1;

        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->get(
            $this->urlFor(WorkspacesRouteConfig::ROUTE_WORKSPACE_PERMISSIONS_LIST, array('workspace_id' => $workspaceId)),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(4, $result);
    }

    public function testFilePermissionsGetList()
    {
        $workspaceId = 1;

        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        //create folder
        $parentFolder = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID);
        $user = $this->getEntityManager()->find(User::getEntityName(), self::USER_ID2);

        //share folder with USER_ID2
        $permissionsService = $this->getContainer()->get('PermissionService');
        $permissionsService->addPermission(
            $parentFolder,
            $user,
            ['edit'],
            self::USER_ID,
            $parentFolder->getWorkspace()->getPortal()
        );

        //create another folder which should only return the lowest level permission for the folder
        $folder = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID, $parentFolder);
        $permissionsService->addPermission(
            $folder,
            $user,
            ['read'],
            self::USER_ID,
            $folder->getWorkspace()->getPortal()
        );
        $response = $this->get(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_PERMISSIONS_LIST, array('folder_id' => $folder->getId())),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(1, $result);
    }

    public function testFileGetPermissionsListHighestPermission()
    {
        $workspaceId = 1;

        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        //create folder
        $parentFolder = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID);
        $user = $this->getEntityManager()->find(User::getEntityName(), self::USER_ID2);
        $group = $this->getEntityManager()->find(Group::getEntityName(), self::GROUP_ID);

        //share folder with USER_ID2
        $permissionsService = $this->getContainer()->get('PermissionService');
        $permissionsService->addPermission(
            $parentFolder,
            $user,
            ['edit'],
            self::USER_ID,
            $parentFolder->getWorkspace()->getPortal()
        );

        //create another folder which should only return the lowest level permission for the folder
        $folder = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID, $parentFolder);
        $permissionsService->addPermission(
            $folder,
            $user,
            ['read'],
            self::USER_ID,
            $folder->getWorkspace()->getPortal()
        );
        $permissionsService->addPermission(
            $folder,
            $group,
            ['none'],
            self::USER_ID,
            $folder->getWorkspace()->getPortal()
        );
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->get(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_PERMISSIONS_LIST, array('folder_id' => $folder->getId())),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(2, $result);
    }

    public function testFolderPermissionsGetList()
    {
        $workspaceId = 1;

        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        //create folder
        $folder = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID);
        $user = $this->getEntityManager()->find(User::getEntityName(), self::USER_ID2);

        //share folder with USER_ID2
        $permissionsService = $this->getContainer()->get('PermissionService');
        $permissionsService->addPermission(
            $folder,
            $user,
            ['edit'],
            self::USER_ID,
            $folder->getWorkspace()->getPortal()
        );

        $response = $this->get(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_PERMISSIONS_LIST, array('folder_id' => $folder->getId())),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(1, $result);
    }

    public function testFolderGetEmptyPermissionsList()
    {
        $workspaceId = 1;

        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        //create folder
        $folder = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID);

        $response = $this->get(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_PERMISSIONS_LIST, array('folder_id' => $folder->getId())),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(0, $result);
    }

    public function testNonExistingFolderPermissionsList()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->get(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_PERMISSIONS_LIST, array('folder_id' => 10500)),
            array(),
            $headers
        );

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testHasWorkspaceAccessUsingGroupPermissions()
    {
        $workspace = 2;

        $headers = $this->getAuthorizationHeaders(self::USERNAME4, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->get(
            $this->urlFor(WorkspacesRouteConfig::ROUTE_WORKSPACE_GET, array('workspace_id' => $workspace)),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($result);

        $workspace = 3;

        $response = $this->get(
            $this->urlFor(WorkspacesRouteConfig::ROUTE_WORKSPACE_GET, array('workspace_id' => $workspace)),
            array(),
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testDeleteWorkspaceAccessPermissionByPortalAdmin()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->delete(
            $this->urlFor(PermissionsRouteConfig::ROUTE_PERMISSION_DELETE, array('permission_id' => 3)),
            array(),
            $headers
        );

        $this->assertEquals(204, $response->getStatusCode());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        FileHelper::clearTmpStorage($this);
    }

    protected function getDataSet()
    {
        $workspaceBitmask = new BitMask(Workspace::RESOURCE_ID);
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
                    'password' => password_hash(self::PASSWORD, PASSWORD_DEFAULT),
                    'email_confirmed' => 1
                ),
                array(
                    'id' => self::USER_ID2,
                    'email' => self::USERNAME2,
                    'password' => password_hash(self::PASSWORD, PASSWORD_DEFAULT),
                    'email_confirmed' => 1
                ),
                array(
                    'id' => self::USER_ID3,
                    'email' => self::USERNAME3,
                    'password' => password_hash(self::PASSWORD, PASSWORD_DEFAULT),
                    'email_confirmed' => 1
                ),
                array(
                    'id' => self::USER_ID4,
                    'email' => self::USERNAME4,
                    'password' => password_hash(self::PASSWORD, PASSWORD_DEFAULT),
                    'email_confirmed' => 1
                )
            ),
            'portals' => array(
                array(
                    'id' => self::PORTAL_ID,
                    'name' => 'Test portal',
                    'owned_by' => self::USER_ID
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
                ),
                array(
                    'id' => 4,
                    'name' => 'Workspace 4',
                    'portal_id' => self::PORTAL_ID
                )
            ),
            'groups' => array(
                array(
                    'id' => self::GROUP_ID,
                    'name' => 'Group 1',
                    'portal_id' => self::PORTAL_ID,
                    'scope_type' => 'portal',
                    'scope_id' => self::PORTAL_ID
                ),
                array(
                    'id' => self::GROUP_ID2,
                    'name' => 'Group 1',
                    'portal_id' => self::PORTAL_ID,
                    'scope_type' => 'workspace',
                    'scope_id' => 4
                )
            ),
            'group_memberships' => array(
                array(
                    'user_id' => self::USER_ID2,
                    'group_id' => self::GROUP_ID
                ),
                array(
                    'user_id' => self::USER_ID4,
                    'group_id' => self::GROUP_ID
                )
            ),
            'acl_resources' => array(
                array(
                    'id' => 1,
                    'entity_id' => self::PORTAL_ID,
                    'entity_type' => AclPortalResource::ACL_RESOURCE_ENTITY_TYPE
                ),
                array(
                    'id' => 2,
                    'entity_id' => 1,
                    'entity_type' => AclWorkspaceResource::ACL_RESOURCE_ENTITY_TYPE
                ),
                array(
                    'id' => 3,
                    'entity_id' => 2,
                    'entity_type' => AclWorkspaceResource::ACL_RESOURCE_ENTITY_TYPE
                )
            ),
            'acl_roles' => array(
                array(
                    'id' => 1,
                    'entity_id' => self::USER_ID,
                    'entity_type' => AclUserRole::ACL_ROLE_ENTITY_TYPE
                ),
                array(
                    'id' => 2,
                    'entity_id' => self::USER_ID2,
                    'entity_type' => AclUserRole::ACL_ROLE_ENTITY_TYPE
                ),
                array(
                    'id' => 3,
                    'entity_id' => self::USER_ID3,
                    'entity_type' => AclUserRole::ACL_ROLE_ENTITY_TYPE
                ),
                array(
                    'id' => 4,
                    'entity_id' => self::USER_ID4,
                    'entity_type' => AclUserRole::ACL_ROLE_ENTITY_TYPE
                ),
                array(
                    'id' => 5,
                    'entity_id' => self::GROUP_ID,
                    'entity_type' => AclGroupRole::ACL_ROLE_ENTITY_TYPE
                )
            ),
            'acl_permissions' => array(
                //portal permissions
                array(
                    'id' => self::PERMISSION_ID,
                    'acl_role_id' => 1,
                    'acl_resource_id' => 1,
                    'bit_mask' => PermissionType::getPermissionTypeBitMask(
                        Portal::RESOURCE_ID,
                        PermissionType::PORTAL_ADMIN
                    ),
                    'is_deleted' => 0,
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'id' => 5,
                    'acl_role_id' => 2,
                    'acl_resource_id' => 1,
                    'bit_mask' => PermissionType::getPermissionTypeBitMask(
                        Portal::RESOURCE_ID,
                        PermissionType::PORTAL_ACCESS
                    ),
                    'is_deleted' => 0,
                    'portal_id' => self::PORTAL_ID
                ),
                //workspace permissions
                array(
                    'id' => 8,
                    'acl_role_id' => 1,
                    'acl_resource_id' => 2,
                    'bit_mask' => PermissionType::getPermissionTypeBitMask(
                        Workspace::RESOURCE_ID,
                        PermissionType::WORKSPACE_ACCESS
                    ),
                    'is_deleted' => 0,
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'id' => 2,
                    'acl_role_id' => 2,
                    'acl_resource_id' => 2,
                    'bit_mask' => $workspaceBitmask->getBitMask([
                        PermissionType::WORKSPACE_ACCESS,
                        PermissionType::WORKSPACE_GRANT_ACCESS
                    ]),
                    'is_deleted' => 0,
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'id' => 3,
                    'acl_role_id' => 3,
                    'acl_resource_id' => 2,
                    'bit_mask' => PermissionType::getPermissionTypeBitMask(
                        Workspace::RESOURCE_ID,
                        PermissionType::WORKSPACE_ACCESS
                    ),
                    'is_deleted' => 0,
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'id' => 9,
                    'acl_role_id' => 3,
                    'acl_resource_id' => 2,
                    'is_deleted' => 1,
                    'bit_mask' => PermissionType::getPermissionTypeBitMask(
                        Workspace::RESOURCE_ID,
                        PermissionType::WORKSPACE_ACCESS
                    ),
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'id' => 4,
                    'acl_role_id' => 4,
                    'acl_resource_id' => 2,
                    'bit_mask' => PermissionType::getPermissionTypeBitMask(
                        Workspace::RESOURCE_ID,
                        PermissionType::WORKSPACE_ACCESS
                    ),
                    'is_deleted' => 0,
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'id' => 6,
                    'acl_role_id' => 4,
                    'acl_resource_id' => 1,
                    'bit_mask' => PermissionType::getPermissionTypeBitMask(
                        Portal::RESOURCE_ID,
                        PermissionType::PORTAL_ACCESS
                    ),
                    'is_deleted' => 0,
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'id' => 7,
                    'acl_role_id' => 5,
                    'acl_resource_id' => 3,
                    'bit_mask' => PermissionType::getPermissionTypeBitMask(
                        Workspace::RESOURCE_ID,
                        PermissionType::WORKSPACE_ACCESS
                    ),
                    'is_deleted' => 0,
                    'portal_id' => self::PORTAL_ID
                )
            ),
            'files' => array()
        ));
    }
}
