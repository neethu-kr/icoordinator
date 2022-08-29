<?php

namespace iCoordinator;

use iCoordinator\Config\Route\FilesRouteConfig;
use iCoordinator\Config\Route\FoldersRouteConfig;
use iCoordinator\Config\Route\SmartFoldersRouteConfig;
use iCoordinator\Entity\Acl\AclResource\AclPortalResource;
use iCoordinator\Entity\Acl\AclResource\AclWorkspaceResource;
use iCoordinator\Entity\Acl\AclRole\AclGroupRole;
use iCoordinator\Entity\Acl\AclRole\AclUserRole;
use iCoordinator\Entity\File;
use iCoordinator\Entity\Group;
use iCoordinator\Entity\Portal;
use iCoordinator\Entity\User;
use iCoordinator\Entity\Workspace;
use iCoordinator\Permissions\BitMask;
use iCoordinator\Permissions\PermissionType;
use iCoordinator\PHPUnit\DbUnit\DataSet\ArrayDataSet;
use iCoordinator\PHPUnit\Helper\FileHelper;
use iCoordinator\PHPUnit\TestCase;
use iCoordinator\Service\PermissionService;
use iCoordinator\Service\UserService;
use iCoordinator\Service\WorkspaceService;
use Laminas\Json\Json;

class FoldersTest extends TestCase
{
    const USER_ID = 1;
    const USER_ID2 = 2;
    const USERNAME = 'test@user.com';
    const USERNAME2 = 'test2@user.com';
    const PASSWORD = 'password';
    const PUBLIC_CLIENT_ID = 'public_test';
    const DESKTOP_CLIENT_ID = 'icoordinator_desktop';
    const TEST_FILE_NAME = 'Document1.pdf';
    const PORTAL_ID = 1;
    const META_FIELD_ID = 1;
    const GROUP_ID = 1;
    const GROUP_ID2 = 2;
    const WORKSPACE_ID = 1;
    const WORKSPACE_ID2 = 2;

    public function setUp(): void
    {
        parent::setUp();
        FileHelper::initializeFileMocks($this);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        FileHelper::clearTmpStorage($this);
    }

    protected function getDataSet()
    {
        $fileBitMask = new BitMask(File::RESOURCE_ID);
        $workspaceBitMask = new BitMask(Workspace::RESOURCE_ID);
        $portalBitMask = new BitMask(Portal::RESOURCE_ID);
        return new ArrayDataSet(array(
            'oauth_clients' => array(
                array(
                    'client_id' => self::PUBLIC_CLIENT_ID
                ),
                array(
                    'client_id' => self::DESKTOP_CLIENT_ID
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
                    'id' => self::WORKSPACE_ID,
                    'name' => 'Workspace 1',
                    'portal_id' => self::PORTAL_ID,
                    'desktop_sync' => 1
                ),
                array(
                    'id' => self::WORKSPACE_ID2,
                    'name' => 'Workspace 2',
                    'portal_id' => self::PORTAL_ID,
                    'desktop_sync' => 1
                ),
                array(
                    'id' => 3,
                    'name' => 'Workspace 30',
                    'portal_id' => self::PORTAL_ID,
                    'desktop_sync' => 0
                ),
                array(
                    'id' => 4,
                    'name' => 'Workspace 4',
                    'portal_id' => self::PORTAL_ID,
                    'desktop_sync' => 1
                )
            ),
            'groups' => array(
                array(
                    'id' => self::GROUP_ID,
                    'name' => 'Group 1',
                    'portal_id' => self::PORTAL_ID,
                    'scope_type' => 'workspace',
                    'scope_id' => self::WORKSPACE_ID
                ),
                array(
                    'id' => self::GROUP_ID2,
                    'name' => 'Group 2',
                    'portal_id' => self::PORTAL_ID,
                    'scope_type' => 'workspace',
                    'scope_id' => self::WORKSPACE_ID
                )
            ),
            'group_memberships' => array(
                array(
                    'user_id' => self::USER_ID2,
                    'group_id' => self::GROUP_ID
                ),
                array(
                    'user_id' => self::USER_ID2,
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
                    'entity_type' => AclGroupRole::ACL_ROLE_ENTITY_TYPE,
                    'entity_id' => self::GROUP_ID
                ),
                array(
                    'id' => 4,
                    'entity_type' => AclGroupRole::ACL_ROLE_ENTITY_TYPE,
                    'entity_id' => self::GROUP_ID2
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
                    'entity_type' => AclWorkspaceResource::ACL_RESOURCE_ENTITY_TYPE,
                    'entity_id' => 1
                ),
                array(
                    'id' => 3,
                    'entity_type' => AclWorkspaceResource::ACL_RESOURCE_ENTITY_TYPE,
                    'entity_id' => 2
                ),
                array(
                    'id' => 4,
                    'entity_type' => AclWorkspaceResource::ACL_RESOURCE_ENTITY_TYPE,
                    'entity_id' => 3
                ),
                array(
                    'id' => 5,
                    'entity_type' => AclWorkspaceResource::ACL_RESOURCE_ENTITY_TYPE,
                    'entity_id' => 4
                )

            ),
            'acl_permissions' => array(
                array(
                    'acl_role_id' => self::USER_ID,
                    'acl_resource_id' => 1,
                    'bit_mask' => $portalBitMask->getBitMask(PermissionType::PORTAL_ADMIN),
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'acl_role_id' => self::USER_ID2,
                    'acl_resource_id' => 1,
                    'bit_mask' => $portalBitMask->getBitMask(PermissionType::PORTAL_ACCESS),
                    'portal_id' => self::PORTAL_ID
                ),
                //workspace permissions
                array(
                    'acl_role_id' => self::USER_ID,
                    'acl_resource_id' => 2,
                    'bit_mask' => $workspaceBitMask->getBitMask(PermissionType::WORKSPACE_ADMIN),
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'acl_role_id' => self::USER_ID2,
                    'acl_resource_id' => 2,
                    'bit_mask' => $workspaceBitMask->getBitMask(PermissionType::WORKSPACE_ACCESS),
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'acl_role_id' => self::USER_ID,
                    'acl_resource_id' => 3,
                    'bit_mask' => $workspaceBitMask->getBitMask(PermissionType::WORKSPACE_ADMIN),
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'acl_role_id' => self::USER_ID2,
                    'acl_resource_id' => 4,
                    'bit_mask' => $workspaceBitMask->getBitMask(PermissionType::WORKSPACE_ADMIN),
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'acl_role_id' => self::USER_ID2,
                    'acl_resource_id' => 5,
                    'bit_mask' => 0,
                    'portal_id' => self::PORTAL_ID
                )
            ),
            'files' => array(

            ),
            'meta_fields' => array(
                array(
                    'id' => self::META_FIELD_ID,
                    'name' => 'Test metafield',
                    'type' => 'list',
                    'options' => "option1\noption2\noption3",
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'id' => 2,
                    'name' => 'Test metafield 2',
                    'type' => 'number',
                    'portal_id' => self::PORTAL_ID
                )
            ),
            'selective_sync' => array()
        ));
    }

    public function testAddFolderByWorkspaceAdmin()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $testName = 'Test folder';

        $response = $this->post(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_ADD, array('workspace_id' => 3)),
            array(
                'name' => $testName,
                'parent' => array(
                    'id' => 0
                )
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals($result->name, $testName);
        $this->assertNotEmpty($result->id);
    }

    public function testAddExistingFolderByWorkspaceAdmin()
    {
        $workspaceId = 3;
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);
        $folder = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID);
        $testName = 'Anbud_beställning';

        $response = $this->post(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_ADD, array('workspace_id' => $workspaceId)),
            array(
                'name' => $testName,
                'parent' => array(
                    'id' => $folder->getId()
                )
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals($result->name, $testName);
        $this->assertNotEmpty($result->id);

        $response = $this->post(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_ADD, array('workspace_id' => $workspaceId)),
            array(
                'name' => $testName,
                'parent' => array(
                    'id' => $folder->getId()
                )
            ),
            $headers
        );
        $this->assertEquals(409, $response->getStatusCode());
    }

    public function testAddFolderByWorkspaceAdminWithDefaultSyncOff()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::DESKTOP_CLIENT_ID);

        $testName = 'Test folder';

        $response = $this->post(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_ADD, array('workspace_id' => 3)),
            array(
                'name' => $testName,
                'parent' => array(
                    'id' => 0
                )
            ),
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testAddFolderByNotWorkspaceAdmin()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $testName = 'Test folder';

        $response = $this->post(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_ADD, array('workspace_id' => 1)),
            array(
                'name' => $testName,
                'parent' => array(
                    'id' => 0
                )
            ),
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testAddSubFolderByWorkspaceAdmin()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $workspaceId = 1;
        $folder = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID);

        $testName = 'Test folder';
        $response = $this->post(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_ADD, array('workspace_id' => $workspaceId)),
            array(
                'name' => $testName,
                'parent' => $folder->jsonSerialize()
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals($result->name, $testName);
        $this->assertNotEmpty($result->id);
        $this->assertEquals($result->parent->id, $folder->getId());
    }

    public function testAddSubFolderByUserWithEditRights()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $workspaceId = 1;
        $workspace  = $this->getWorkspaceService()->getWorkspace($workspaceId);
        $folder     = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID);

        $createdBy  = $this->getUserService()->getUser(self::USER_ID);
        $grantTo    = $this->getUserService()->getUser(self::USER_ID2);

        $this->getPermissionService()->addPermission($folder, $grantTo, [PermissionType::FILE_EDIT], $createdBy, $workspace->getPortal());

        $testName = 'Test folder';
        $response = $this->post(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_ADD, array('workspace_id' => $workspaceId)),
            array(
                'name' => $testName,
                'parent' => $folder->jsonSerialize()
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals($result->name, $testName);
        $this->assertNotEmpty($result->id);
        $this->assertEquals($result->parent->id, $folder->getId());
    }

    public function testAddSubFolderByUserWithReadRights()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $workspaceId = 1;
        $workspace  = $this->getWorkspaceService()->getWorkspace($workspaceId);
        $folder     = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID);

        $createdBy  = $this->getUserService()->getUser(self::USER_ID);
        $grantTo    = $this->getUserService()->getUser(self::USER_ID2);

        $this->getPermissionService()->addPermission($folder, $grantTo, [PermissionType::FILE_READ], $createdBy, $workspace->getPortal());

        $testName = 'Test folder';
        $response = $this->post(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_ADD, array('workspace_id' => $workspaceId)),
            array(
                'name' => $testName,
                'parent' => $folder->jsonSerialize()
            ),
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testAddSubFolderInherentingMetaFields()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $workspaceId = 1;
        $folder     = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID);

        $metaFieldService = $this->getContainer()->get('MetaFieldService');
        $metaField = $metaFieldService->getMetaField(self::META_FIELD_ID);

        $value = $metaField->getOptions()->current();
        $metaFieldValue = $metaFieldService->addMetaFieldValue(
            $folder,
            array(
                'meta_field' => array('id' => $metaField->getId()),
                'value' => $value
            ),
            self::USER_ID
        );

        $testName = 'Test folder';
        $response = $this->post(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_ADD, array('workspace_id' => $workspaceId)),
            array(
                'name' => $testName,
                'parent' => $folder->jsonSerialize()
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals($result->name, $testName);
        $this->assertNotEmpty($result->id);
        $this->assertEquals($result->parent->id, $folder->getId());

        $newFolder = $this->getFolderService()->getFolder($result->id);
        $this->assertNotEmpty($folder->getMetaFieldsValues());
        $this->assertNotEmpty($newFolder->getMetaFieldsValues());

        $metaFieldService->deleteMetaFieldValue($metaFieldValue,self::USER_ID);

    }
    public function testAddSubFolderInvalidCharacters()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $workspaceId = 1;
        $folder = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID);

        $testName = '|Test Folder';
        $response = $this->post(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_ADD, array('workspace_id' => $workspaceId)),
            array(
                'name' => $testName,
                'parent' => $folder->jsonSerialize()
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testGetFolder()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $workspaceId = 1;
        $this->createFolderTree($workspaceId, self::USER_ID, $headers);

        $response = $this->get(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_GET, array(
                'folder_id' => 1
            )),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(1, $result->id);
        $this->assertNotEmpty($result->etag);
        $this->assertNotEmpty($result->created_at);
        $this->assertNotEmpty($result->modified_at);
    }

    public function testUpdateFolderName()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $workspaceId = 1;
        $folder = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID);

        $newName = 'New folder name';

        $response = $this->put(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_UPDATE, array(
                'folder_id' => $folder->getId()
            )),
            array(
                'name' => $newName
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($newName, $result->name);
    }

    public function testUpdateFolderWithIfMatchHeader()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);
        $headers['If-Match'] = 2;

        $workspaceId = 1;
        $folder = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID, null, null, ['etag' => 2]);

        $newName = 'New folder name';

        $response = $this->put(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_UPDATE, array(
                'folder_id' => $folder->getId()
            )),
            array(
                'name' => $newName
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($newName, $result->name);
    }

    public function testUpdateFoldersParentName()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $workspaceId = 1;
        list($folder1, $folder2) = $this->createFolderTree($workspaceId, self::USER_ID, $headers);

        $response = $this->put(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_UPDATE, array(
                'folder_id' => $folder1->getId()
            )),
            array(
                'parent' => $folder2->jsonSerialize()
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($folder2->getId(), $result->parent->id);
    }

    public function testUpdateDeletedFolder() {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID);

        $folderService = $this->getContainer()->get('FolderService');
        $folderService->deleteFolder($folder, self::USER_ID);

        $newName = 'Updated name';

        //update deleted folder
        $response = $this->put(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_UPDATE, array('folder_id' => $folder->getId())),
            array(
                'name' => $newName
            ),
            $headers
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testTrashFolder()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $workspaceId = 1;
        list($folder1, $folder2) = $this->createFolderTree($workspaceId, self::USER_ID, $headers);

        $response = $this->delete(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_DELETE, array(
                'folder_id' => $folder1->getId()
            )),
            array(),
            $headers
        );

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertTrue($folder1->getIsTrashed());
        $this->assertFalse($folder1->getIsDeleted());
    }


    public function testDeleteFolder()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $workspaceId = 1;
        list($folder1, $folder2) = $this->createFolderTree($workspaceId, self::USER_ID, $headers);

        $response = $this->delete(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_DELETE_PERMANENTLY, array(
                'folder_id' => $folder1->getId()
            )),
            array(),
            $headers
        );

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertTrue($folder1->getIsDeleted());
    }

    public function testDeleteWorkspaceRootFolderByUserWithWorkspaceAccess()
    {
        $workspaceId = 1;
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);
        $user2 = $this->getEntityManager()->find(User::getEntityName(), self::USER_ID2);

        list($folder1, $folder2) = $this->createFolderTree($workspaceId, self::USER_ID, $headers);

        //share folder with user2
        $permissionService = $this->getContainer()->get('PermissionService');
        $permissionService->addPermission(
            $folder1,
            $user2,
            [PermissionType::FILE_READ],
            self::USER_ID,
            $folder1->getWorkspace()->getPortal()
        );
        $permissionService->clearCache();
        //Test case purpose - see that user with only access is prevented from deleting folder
        $response = $this->delete(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_DELETE_PERMANENTLY, array(
                'folder_id' => $folder1->getId()
            )),
            array(),
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testRestoreFolderByPortalAdmin()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder1 = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID);
        $folder2 = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID, $folder1);

        $folderService = $this->getContainer()->get('FolderService');
        $folderService->deleteFolder($folder2, self::USER_ID);

        $response = $this->post(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_RESTORE, array('folder_id' => $folder2->getId())),
            array(),
            $headers
        );

        $this->assertEquals(201, $response->getStatusCode());
    }

    public function testRestoreFolderWithContentByPortalAdmin()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder1 = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID);
        $folder2 = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID, $folder1);
        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, $folder2);

        $folderService = $this->getContainer()->get('FolderService');
        $folderService->deleteFolder($folder2, self::USER_ID);

        $response = $this->post(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_RESTORE, array('folder_id' => $folder2->getId())),
            array(),
            $headers
        );

        $this->assertEquals(201, $response->getStatusCode());
    }

    public function testFolderCopy()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder1 = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID, null);
        $folder2 = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID, null);
        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, $folder1);

        $newName = $folder1->getName() . ' (copy)';
        $response = $this->post(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_COPY, array('folder_id' => $folder1->getId())),
            array(
                'parent' => array('id' => $folder2->getId()),
                'name' => $newName
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($folder2->getId(), $result->parent->id);
        $this->assertEquals($newName, $result->name);
    }

    public function testFolderCopyWithLabels()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder1 = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID, null);
        $folder2 = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID, null);
        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, $folder1);

        $metaFieldService = $this->getContainer()->get('MetaFieldService');
        $metaField = $metaFieldService->getMetaField(self::META_FIELD_ID);

        $value1 = $metaField->getOptions()->current();
        $value2 = $metaField->getOptions()->next();
        $metaFieldValue = $metaFieldService->addMetaFieldValue(
            $folder2,
            array(
                'meta_field' => array('id' => $metaField->getId()),
                'value' => $value1
            ),
            self::USER_ID
        );
        $metaFieldValue = $metaFieldService->addMetaFieldValue(
            $file,
            array(
                'meta_field' => array('id' => $metaField->getId()),
                'value' => $value2
            ),
            self::USER_ID
        );
        $newName = $folder1->getName() . ' (copy)';
        $response = $this->post(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_COPY, array('folder_id' => $folder1->getId())),
            array(
                'parent' => array('id' => $folder2->getId()),
                'name' => $newName
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($folder2->getId(), $result->parent->id);
        $this->assertEquals($newName, $result->name);
        $folder2 = $this->getFolderService()->getFolder($folder2->getId());
        $newfolder = $this->getFolderService()->getFolder($result->id);
        $file = $this->getFileService()->getFile($file->getId());
        $this->assertEquals(1,count($file->getMetaFieldsValues()));
        $this->assertEquals(1,count($folder2->getMetaFieldsValues()));
        $this->assertEquals(1,count($newfolder->getMetaFieldsValues()));
    }

    public function testFolderCopyToRoot()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder1 = FileHelper::createFolder($this->getContainer(), 3, self::USER_ID, null);
        $file = FileHelper::createFile($this->getContainer(), 3, self::USER_ID, $folder1);

        $newName = $folder1->getName() . ' (copy)';
        $response = $this->post(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_COPY, array('folder_id' => $folder1->getId())),
            array(
                'parent' => array('id' => 0),
                'name' => $newName
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNull($result->parent);
        $this->assertEquals($newName, $result->name);
    }

    public function testFolderCopyToRootWithOnlyReadAccess()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder1 = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID, null);
        $user2 = $this->getEntityManager()->find(User::getEntityName(), self::USER_ID2);

        //share folder with user2
        $permissionService = $this->getContainer()->get('PermissionService');
        $permissionService->addPermission(
            $folder1,
            $user2,
            [PermissionType::FILE_EDIT],
            self::USER_ID,
            $folder1->getWorkspace()->getPortal()
        );
        $permissionService->clearCache();
        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, $folder1);

        $newName = $folder1->getName() . ' (copy)';
        $response = $this->post(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_COPY, array('folder_id' => $folder1->getId())),
            array(
                'parent' => array('id' => 0),
                'name' => $newName
            ),
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());

    }

    public function testFolderRename()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder1 = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID, null);

        $newName = strtoupper($folder1->getName());
        $response = $this->put(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_UPDATE, array('folder_id' => $folder1->getId())),
            array(
                'name' => $newName
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($newName, $result->name);
    }

    public function testFolderRenameByUserWithReadAccess()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);
        $user2 = $this->getEntityManager()->find(User::getEntityName(), self::USER_ID2);

        $folder1 = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID, null);

        //share folder with user2
        $permissionService = $this->getContainer()->get('PermissionService');
        $permissionService->addPermission(
            $folder1,
            $user2,
            [PermissionType::FILE_READ],
            self::USER_ID,
            $folder1->getWorkspace()->getPortal()
        );
        $permissionService->clearCache();

        $newName = strtoupper($folder1->getName());
        $response = $this->put(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_UPDATE, array('folder_id' => $folder1->getId())),
            array(
                'name' => $newName
            ),
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testFolderMoveFolderExists()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder1 = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID, null);
        $folder2 = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID, null);

        $folder3 = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID, $folder1);

        // Create a copy with same name at move destination folder
        $response = $this->post(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_COPY, array('folder_id' => $folder3->getId())),
            array(
                'parent' => array('id' => $folder2->getId()),
                'name' => $folder3->getName()
            ),
            $headers
        );

        $response = $this->put(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_UPDATE, array('folder_id' => $folder3->getId())),
            array('parent' => array('id' => $folder2->getId()), 'name' => $folder3->getName()),
            $headers
        );

        $this->assertEquals(409, $response->getStatusCode());

        //fetching updated file

        $response = $this->get(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_GET, array('folder_id' => $folder3->getId())),
            array(),
            $headers
        );
        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($folder1->getId(), $result->parent->id);

        //fetching children of the folder where file move attempt was made

        $response = $this->get(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_CHILDREN_GET, array('folder_id' => $folder2->getId())),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertCount(1, $result->entries);
    }

    public function testFolderMoveToDifferentWorkspace()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder1 = FileHelper::createFolder($this->getContainer(), self::WORKSPACE_ID, self::USER_ID, null);
        $folder2 = FileHelper::createFolder($this->getContainer(), self::WORKSPACE_ID2, self::USER_ID, null);

        $folder3 = FileHelper::createFolder($this->getContainer(), self::WORKSPACE_ID, self::USER_ID, $folder1);

        // Move folder to destination folder in different workspace

        $response = $this->put(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_UPDATE, array('folder_id' => $folder3->getId())),
            array('parent' => array('id' => $folder2->getId()), 'name' => $folder3->getName()),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($folder2->getWorkspace()->getId(), $result->workspace->id);

    }

    public function testFolderMoveToRootWithOnlyAccessRights()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder1 = FileHelper::createFolder($this->getContainer(), self::WORKSPACE_ID, self::USER_ID, null);
        $folder2 = FileHelper::createFolder($this->getContainer(), self::WORKSPACE_ID, self::USER_ID2, $folder1);
        $user2 = $this->getEntityManager()->find(User::getEntityName(), self::USER_ID2);

        //share folder2 with user2
        $permissionService = $this->getContainer()->get('PermissionService');
        $permissionService->addPermission(
            $folder2,
            $user2,
            [PermissionType::FILE_EDIT],
            self::USER_ID,
            $folder2->getWorkspace()->getPortal()
        );
        $permissionService->clearCache();


        // Move folder to root folder

        $response = $this->put(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_UPDATE, array('folder_id' => $folder2->getId())),
            array('parent' => array('id' => 0), 'name' => $folder2->getName()),
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testFolderMoveToRoot()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder1 = FileHelper::createFolder($this->getContainer(), self::WORKSPACE_ID, self::USER_ID, null);
        $folder2 = FileHelper::createFolder($this->getContainer(), self::WORKSPACE_ID, self::USER_ID2, $folder1);


        // Move folder to root folder

        $response = $this->put(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_UPDATE, array('folder_id' => $folder2->getId())),
            array('parent' => array('id' => 0), 'name' => $folder2->getName()),
            $headers
        );

        $this->assertEquals(200, $response->getStatusCode());
        $result = Json::decode($response->getBody());
        $this->assertEquals(null, $result->parent);
    }

    public function testFolderMoveReadOnlyRootFolderToRootFolderWithEditRights()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder1 = FileHelper::createFolder($this->getContainer(), self::WORKSPACE_ID, self::USER_ID, null);
        $folder2 = FileHelper::createFolder($this->getContainer(), self::WORKSPACE_ID, self::USER_ID, null);
        $user2 = $this->getEntityManager()->find(User::getEntityName(), self::USER_ID2);

        //share folder1 with user2
        $permissionService = $this->getContainer()->get('PermissionService');
        $permissionService->addPermission(
            $folder1,
            $user2,
            [PermissionType::FILE_EDIT],
            self::USER_ID,
            $folder1->getWorkspace()->getPortal()
        );
        //share folder2 with user2
        $permissionService->addPermission(
            $folder2,
            $user2,
            [PermissionType::FILE_READ],
            self::USER_ID,
            $folder2->getWorkspace()->getPortal()
        );
        $permissionService->clearCache();


        // Move read access folder to folder with edit rights

        $response = $this->put(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_UPDATE, array('folder_id' => $folder2->getId())),
            array('parent' => array('id' => $folder1->getId())),
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testAccessToChildrenInOwnedSharedFolder()
    {
        $workspaceId = 1;
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        //create initial structure by user1
        list($folder1, $folder2) = $this->createFolderTree($workspaceId, self::USER_ID, $headers);

        $user = $this->getEntityManager()->find(User::getEntityName(), self::USER_ID);

        //share folder with user2
        $permissionService = $this->getContainer()->get('PermissionService');
        $permissionService->addPermission(
            $folder1,
            $user,
            [PermissionType::FILE_EDIT, PermissionType::FILE_READ],
            self::USER_ID,
            $folder1->getWorkspace()->getPortal()
        );

        //create file be user2 inside shared folder of user1
        FileHelper::createFile($this->getContainer(), $workspaceId, self::USER_ID2, $folder1);

        //Test case purpose - see, how many files user1 sees inside folder1

        $response = $this->get(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_CHILDREN_GET, array(
                'folder_id' => $folder1->getId()
            )),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertCount(5, $result->entries);
    }

    public function testAccessToChildrenInFolderWithMultiplePermissions()
    {
        $workspaceId = 1;
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        //create initial structure by user1
        list($folder1, $folder2) = $this->createFolderTree($workspaceId, self::USER_ID2, $headers);

        $user = $this->getEntityManager()->find(User::getEntityName(), self::USER_ID);
        $user2 = $this->getEntityManager()->find(User::getEntityName(), self::USER_ID2);
        $group = $this->getEntityManager()->find(Group::getEntityName(), self::GROUP_ID);

        //share folder with user2
        $permissionService = $this->getContainer()->get('PermissionService');
        $permissionService->addPermission(
            $folder1,
            $user2,
            [PermissionType::FILE_EDIT, PermissionType::FILE_READ],
            self::USER_ID,
            $folder1->getWorkspace()->getPortal()
        );

        //create file be user2 inside shared folder of user1
        $file = FileHelper::createFile($this->getContainer(), $workspaceId, self::USER_ID2, $folder1);
        $permissionService->addPermission(
            $file,
            $user2,
            [PermissionType::FILE_EDIT],
            self::USER_ID,
            $folder1->getWorkspace()->getPortal()
        );
        $permissionService->addPermission(
            $file,
            $group,
            [PermissionType::FILE_NONE],
            self::USER_ID,
            $folder1->getWorkspace()->getPortal()
        );
        //Test case purpose - see, how many files user2 sees inside folder1

        $response = $this->get(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_CHILDREN_GET, array(
                'folder_id' => $folder1->getId()
            )),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertCount(4, $result->entries);
    }

    public function testAccessWithMultipleGroupPermissions()
    {
        $workspaceId = 1;
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        //create initial structure by user1
        $folder1 = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID);

        //add 3 children to folder1
        $folder2 = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID, $folder1);
        FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID, $folder1);
        FileHelper::createFile($this->getContainer(), $workspaceId, self::USER_ID, $folder1);
        $file2 = FileHelper::createFile($this->getContainer(), $workspaceId, self::USER_ID, $folder2);

        $group1 = $this->getEntityManager()->find(Group::getEntityName(), self::GROUP_ID);
        $group2 = $this->getEntityManager()->find(Group::getEntityName(), self::GROUP_ID2);
        $user2 = $this->getEntityManager()->find(User::getEntityName(), self::USER_ID2);

        //share folder with group1
        $permissionService = $this->getContainer()->get('PermissionService');
        $permissionService->addPermission(
            $folder1,
            $group1,
            [PermissionType::FILE_EDIT],
            self::USER_ID,
            $folder1->getWorkspace()->getPortal()
        );
        //share folder with group2
        $permissionService->addPermission(
            $folder1,
            $group2,
            [PermissionType::FILE_READ],
            self::USER_ID,
            $folder1->getWorkspace()->getPortal()
        );
        //set no permission for group2 on subfolder
        $permission = $permissionService->addPermission(
            $folder2,
            $group2,
            [PermissionType::FILE_NONE],
            self::USER_ID,
            $folder2->getWorkspace()->getPortal()
        );
        /*$response = $this->put(
            $this->urlFor(PermissionsRouteConfig::ROUTE_PERMISSION_UPDATE, array('permission_id' => $permission->getId())),
            array(
                'actions' => array(
                    'none'
                )
            ),
            $headers
        );*/
        /*$permissionService->updatePermission(
            $permission,
            [
                PermissionType::FILE_NONE
            ],
            self::USER_ID
        );*/
        $permissionService->clearCache();
        //Test case purpose - see, how many files user2 sees inside folder1
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);
        $response = $this->get(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_CHILDREN_GET, array(
                'folder_id' => $folder1->getId()
            )),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertCount(3, $result->entries);

        $response = $this->get(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_CHILDREN_GET, array(
                'folder_id' => $folder2->getId()
            )),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertCount(1, $result->entries);

        $response = $this->get(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_GET_PERMISSION, array(
                'folder_id' => $folder2->getId()
            )),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals("edit", $result->actions);

        $response = $this->get(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_GET, array(
                'file_id' => $file2->getId()
            )),
            array(),
            $headers
        );

        $this->assertEquals(200, $response->getStatusCode());

        $testName = 'Test folder';
        $response = $this->post(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_ADD, array('workspace_id' => $workspaceId)),
            array(
                'name' => $testName,
                'parent' => $folder2->jsonSerialize()
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals($result->name, $testName);
        $this->assertNotEmpty($result->id);
        $this->assertEquals($result->parent->id, $folder2->getId());



    }

    public function testGetFolderPermission()
    {
        $workspaceId = 1;
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        //create initial structure by user1
        list($folder1, $folder2) = $this->createFolderTree($workspaceId, self::USER_ID, $headers);

        $user2 = $this->getEntityManager()->find(User::getEntityName(), self::USER_ID2);

        //share folder with user2
        $permissionService = $this->getContainer()->get('PermissionService');
        $permissionService->addPermission(
            $folder1,
            $user2,
            [PermissionType::FILE_READ],
            self::USER_ID,
            $folder1->getWorkspace()->getPortal()
        );
        $permissionService->clearCache();
        //Test case purpose - return folder permission
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->get(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_GET_PERMISSION, array(
                'folder_id' => $folder1->getId()
            )),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals('read', $result->actions);
    }

    public function testGetWorkspaceRootFoldersChildrenByUserWithWorkspaceAccess()
    {
        $workspaceId = 1;
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        //create initial structure by user1
        $this->createFolderTree($workspaceId, self::USER_ID, $headers);


        //Test case purpose - see, how many files user1 sees inside root folder
        $response = $this->get(
            $this->urlFor(FoldersRouteConfig::ROUTE_ROOT_FOLDER_CHILDREN_GET, array(
                'workspace_id' => $workspaceId
            )) . '?limit=5',
            array(),
            $headers
        );
        $result = Json::decode($response->getBody());

        $this->assertCount(0, $result->entries);
        $this->assertNull($result->next_offset);
        $this->assertFalse($result->has_more);
    }


    public function testGetWorkspaceRootFoldersChildrenByWorkspaceAdmin()
    {
        $workspaceId = 1;
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        //create initial structure by user1
        $this->createFolderTree($workspaceId, self::USER_ID, $headers);

        //create some files by user2
        for ($i = 0; $i < 5; $i++) {
            FileHelper::createFile($this->getContainer(), $workspaceId, self::USER_ID2);
        }

        //create some files by user1
        for ($i = 0; $i < 5; $i++) {
            FileHelper::createFile($this->getContainer(), $workspaceId, self::USER_ID);
        }

        //Test case purpose - see, how many files user1 sees inside root folder
        $response = $this->get(
            $this->urlFor(FoldersRouteConfig::ROUTE_ROOT_FOLDER_CHILDREN_GET, array(
                'workspace_id' => $workspaceId
            )) . '?limit=5',
            array(),
            $headers
        );
        $result = Json::decode($response->getBody());

        $this->assertCount(5, $result->entries);
        $this->assertEquals(5, $result->next_offset);
        $this->assertTrue($result->has_more);

        $response = $this->get(
            $this->urlFor(FoldersRouteConfig::ROUTE_ROOT_FOLDER_CHILDREN_GET, array(
                'workspace_id' => $workspaceId
            )) . '?limit=5&offset=' . $result->next_offset,
            array(),
            $headers
        );
        $result = Json::decode($response->getBody());

        $this->assertCount(5, $result->entries);
        $this->assertEquals(10, $result->next_offset);
        $this->assertTrue($result->has_more);
    }


    public function testGetTrashFoldersChildren()
    {
        $workspaceId = 1;
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        //create initial structure by user1
        list($folder1, $folder2) = $this->createFolderTree($workspaceId, self::USER_ID, $headers);

        $folderService = $this->getContainer()->get('FolderService');
        $folderService->deleteFolder($folder1, self::USER_ID);
        $folderService->deleteFolder($folder2, self::USER_ID);


        //Test case purpose - see, how many files user1 sees inside folder1
        $response = $this->get(
            $this->urlFor(FoldersRouteConfig::ROUTE_TRASH_FOLDER_CHILDREN_GET, array(
                'workspace_id' => $workspaceId
            )),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertCount(2, $result->entries);
    }

    public function testGetTrashFoldersChildrenNotWSAdmin()
    {
        $workspaceId = 1;
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        //create initial structure by user2
        $folder1 = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID2);
        $folder2 = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID2);
        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_PERMISSION_ADD, array('file_id' => $folder1->getId())),
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

        $folderService = $this->getContainer()->get('FolderService');
        $folderService->deleteFolder($folder1, self::USER_ID2);
        $folderService->deleteFolder($folder2, self::USER_ID2);


        //Test case purpose - see, how many files user1 sees inside folder1
        $response = $this->get(
            $this->urlFor(FoldersRouteConfig::ROUTE_TRASH_FOLDER_CHILDREN_GET, array(
                'workspace_id' => $workspaceId
            )),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertCount(1, $result->entries);
    }

    public function testGetFoldersChildrenOfTypeFolder()
    {
        $workspaceId = 1;
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        //create initial structure by user1
        list($folder1, $folder2) = $this->createFolderTree($workspaceId, self::USER_ID, $headers);

        //Test case purpose - see, how many files user1 sees inside folder1
        $response = $this->get(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_CHILDREN_GET, array(
                'folder_id' => $folder1->getId()
            )) . '?types=folder,smart_folder',
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertCount(2, $result->entries);
    }


    public function testGetFolderChildrenOfTypeFileAndFolder()
    {
        $workspaceId = 1;
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        //create initial structure by user1
        list($folder1, $folder2) = $this->createFolderTree($workspaceId, self::USER_ID, $headers);


        //Test case purpose - see, how many files user1 sees inside folder1
        $response = $this->get(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_CHILDREN_GET, array(
                'folder_id' => $folder1->getId()
            )) . '?types=folder,file',
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertCount(4, $result->entries);
    }

    public function testGetRootFolderChildrenOfTypeFileAndFolder()
    {
        $workspaceId = 1;
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);
        //create initial structure by user1
        $this->createFolderTree($workspaceId, self::USER_ID, $headers);
        FileHelper::createFile($this->getContainer(), $workspaceId, self::USER_ID);


        //Test case purpose - see, how many files user1 sees inside folder1
        $response = $this->get(
            $this->urlFor(FoldersRouteConfig::ROUTE_ROOT_FOLDER_CHILDREN_GET, array(
                'workspace_id' => $workspaceId
            )) . '?types=folder,file',
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertCount(3, $result->entries);
    }

    public function testGetRootFolderChildrenOfTypeSmartFolder()
    {
        $workspaceId = 1;
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);
        //create initial structure by user1
        $this->createFolderTree($workspaceId, self::USER_ID, $headers);
        FileHelper::createFile($this->getContainer(), $workspaceId, self::USER_ID);
        $testName = 'Test folder';

        $response = $this->post(
            $this->urlFor(SmartFoldersRouteConfig::ROUTE_SMART_FOLDER_ADD, array('workspace_id' => $workspaceId)),
            array(
                'name' => $testName
            ),
            $headers
        );
        $headers2 = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        //Test case purpose - see, how many files user1 sees inside folder1
        $response = $this->get(
            $this->urlFor(FoldersRouteConfig::ROUTE_ROOT_FOLDER_CHILDREN_GET, array(
                'workspace_id' => $workspaceId
            )) . '?types=smart_folder',
            array(),
            $headers2
        );

        $result = Json::decode($response->getBody());

        $this->assertCount(1, $result->entries);
    }

    public function testGetFolderPath()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder1 = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID, null);
        $folder2 = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID, $folder1);

        $response = $this->get(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_GET_PATH, array('folder_id' => $folder2->getId())),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $path = $folder2->getWorkspace()->getName()."/".$folder1->getName()."/".$folder2->getName();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($path, $result->path);
    }

    private function createFolderTree($workspaceId, $userId, $headers)
    {
        $folder1 = FileHelper::createFolder($this->getContainer(), $workspaceId, $userId);
        $folder2 = FileHelper::createFolder($this->getContainer(), $workspaceId, $userId);

        //add 3 children to folder1
        FileHelper::createFolder($this->getContainer(), $workspaceId, $userId, $folder1);
        FileHelper::createFolder($this->getContainer(), $workspaceId, $userId, $folder1);
        FileHelper::createFile($this->getContainer(), $workspaceId, $userId, $folder1);
        $noPermissionsFile = FileHelper::createFile($this->getContainer(), $workspaceId, $userId, $folder1);
        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_PERMISSION_ADD, array('file_id' => $noPermissionsFile->getId())),
            array(
                'grant_to' => array(
                    'entity_type' => AclUserRole::ACL_ROLE_ENTITY_TYPE,
                    'entity_id' => $userId
                ),
                'actions' => array(
                    PermissionType::FILE_NONE
                )
            ),
            $headers
        );

        //add 1 child to folder2
        FileHelper::createFolder($this->getContainer(), $workspaceId, $userId, $folder2);

        return array($folder1, $folder2);
    }

    /**
     * @return PermissionService
     */
    private function getPermissionService()
    {
        return $this->getContainer()->get('PermissionService');
    }

    /**
     * @return UserService
     */
    private function getUserService()
    {
        return $this->getContainer()->get('UserService');
    }

    /**
     * @return WorkspaceService
     */
    private function getWorkspaceService()
    {
        return $this->getContainer()->get('WorkspaceService');
    }

    /**
     * @return FolderService
     */
    private function getFolderService()
    {
        return $this->getContainer()->get('FolderService');
    }

    /**
     * @return FileService
     */
    private function getFileService()
    {
        return $this->getContainer()->get('FileService');
    }
}
