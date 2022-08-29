<?php

namespace iCoordinator;

use iCoordinator\Config\Route\EventsRouteConfig;
use iCoordinator\Config\Route\FilesRouteConfig;
use iCoordinator\Config\Route\FoldersRouteConfig;
use iCoordinator\Doctrine\Proxy\__CG__\iCoordinator\Entity\User;
use iCoordinator\Entity\Acl\AclResource\AclPortalResource;
use iCoordinator\Entity\Acl\AclResource\AclWorkspaceResource;
use iCoordinator\Entity\Acl\AclRole\AclUserRole;
use iCoordinator\Entity\Error;
use iCoordinator\Entity\File;
use iCoordinator\Entity\Portal;
use iCoordinator\Entity\Workspace;
use iCoordinator\Permissions\BitMask;
use iCoordinator\Permissions\PermissionType;
use iCoordinator\PHPUnit\DbUnit\DataSet\ArrayDataSet;
use iCoordinator\PHPUnit\Helper\FileHelper;
use iCoordinator\PHPUnit\TestCase;
use Laminas\Json\Json;


class SelectiveSyncTest extends TestCase
{
    const USER_ID = 1;
    const USER_ID2 = 2;
    const USERNAME = 'test@user.com';
    const USERNAME2 = 'test2@user.com';
    const PASSWORD = 'password';
    const PUBLIC_CLIENT_ID = 'icoordinator_desktop';
    const PUBLIC_CLIENT_ID2 = 'public_test';
    const TEST_FILE_NAME = 'Document1.pdf';
    const PORTAL_ID = 1;
    const LICENSE_ID = 1;

    public function setUp(): void
    {
        parent::setUp();

        FileHelper::initializeFileMocks($this);
    }

    public function testGetNonExistingSelectiveSyncForFile()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID);

        $response = $this->get(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_SELECTIVE_SYNC_GET, array('file_id' => $file->getId())),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNull($result);

    }

    public function testGetNonExistingSelectiveSyncForFolder()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID);

        $response = $this->get(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_SELECTIVE_SYNC_GET, array('folder_id' => $folder->getId())),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNull($result);

    }

    public function testSetAndGetSelectiveSync()
    {
        //create file and set selective sync
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID);
        $user = $this->getEntityManager()->find(User::getEntityName(), self::USER_ID);

        $response = $this->put(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_SELECTIVE_SYNC_SET, array('file_id' => $file->getId())),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($result->id);

        //get selective sync

        $response = $this->get(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_SELECTIVE_SYNC_GET, array('file_id' => $file->getId())),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($result->file);

        //get selective sync by other user

        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->get(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_SELECTIVE_SYNC_GET, array('file_id' => $file->getId())),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNull($result);
    }

    public function testSetAndDeleteSelectiveSync()
    {
        //create file and set selective sync
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID);
        $user = $this->getEntityManager()->find(User::getEntityName(), self::USER_ID);

        $response = $this->put(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_SELECTIVE_SYNC_SET, array('file_id' => $file->getId())),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($result->id);

        //unset selective sync

        $response = $this->delete(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_SELECTIVE_SYNC_DELETE, array('file_id' => $file->getId())),
            array(),
            $headers
        );

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());

    }
    public function testSetAndDeleteSelectiveSyncForUser()
    {
        //create file and set selective sync
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID);
        $user = $this->getEntityManager()->find(User::getEntityName(), self::USER_ID);

        $response = $this->put(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_SELECTIVE_SYNC_SET, array('file_id' => $file->getId())),
            array('user_id' => self::USER_ID2),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($result->id);

        //unset selective sync

        $response = $this->delete(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_SELECTIVE_SYNC_DELETE, array('file_id' => $file->getId())),
            array('user_id' => self::USER_ID2),
            $headers
        );

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());

    }
    public function testFolderSetSelectiveSyncAndCreateUploadDeleteFile()
    {
        //create file and set email options
        $workspaceId = 1;
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID);

        $user = $this->getEntityManager()->find(User::getEntityName(), self::USER_ID);

        $permissionService = $this->getContainer()->get('PermissionService');
        $permissionService->addPermission(
            $folder,
            $user,
            [PermissionType::FILE_READ],
            self::USER_ID,
            $folder->getWorkspace()->getPortal()
        );

        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, $folder);


        //mock environment
        $tmpName = tempnam(sys_get_temp_dir(), 'upload_');
        $settings = $this->getContainer()->get('settings');
        copy($settings['testsPath'] . '/data/files/' . self::TEST_FILE_NAME, $tmpName);
        $_FILES = array(
            'file' => array(
                'name' => self::TEST_FILE_NAME,
                'tmp_name' => $tmpName,
                'error' => UPLOAD_ERR_OK
            )
        );
        //end mock environment


        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_UPDATE_CONTENT, array('file_id' => $file->getId())),
            array(
                'content_modified_at' => '2015-09-07T14:06:51+02:00'
            ),
            $headers
        );
        $response = $this->delete(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_DELETE, array('file_id' => $file->getId())),
            array(),
            $headers
        );
        $response = $this->put(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_SELECTIVE_SYNC_SET, array('folder_id' => $folder->getId())),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($result->id);

        $response = $this->get(
            $this->urlFor(EventsRouteConfig::ROUTE_EVENTS_GET) . '?limit=1',
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $response = $this->get(
            $this->urlFor(EventsRouteConfig::ROUTE_EVENTS_GET) . '?cursor_position='.($result->next_cursor_position-6),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(1, $result->entries);
        $this->assertFalse($result->has_more);
    }

    public function testFolderSetSelectiveSyncAndGetContent()
    {
        $workspaceId = 1;
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID);
        $subfolder = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID, $folder);

        $response = $this->put(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_SELECTIVE_SYNC_SET, array('folder_id' => $subfolder->getId())),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($result->id);

        $user = $this->getEntityManager()->find(User::getEntityName(), self::USER_ID);

        $permissionService = $this->getContainer()->get('PermissionService');
        $permissionService->addPermission(
            $folder,
            $user,
            [PermissionType::FILE_READ],
            self::USER_ID,
            $folder->getWorkspace()->getPortal()
        );
        $permissionService->addPermission(
            $subfolder,
            $user,
            [PermissionType::FILE_READ],
            self::USER_ID,
            $subfolder->getWorkspace()->getPortal()
        );
        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, $folder);


        $response = $this->get(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_CHILDREN_GET, array(
                'folder_id' => $folder->getId()
            )),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(1, $result->entries);
        $this->assertFalse($result->has_more);
    }

    public function testRootFolderSetSelectiveSyncAndGetContent()
    {
        $workspaceId = 1;
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder1 = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID);
        $folder2 = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID);
        $file = FileHelper::createFile($this->getContainer(), $workspaceId, self::USER_ID);

        $response = $this->put(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_SELECTIVE_SYNC_SET, array('folder_id' => $folder1->getId())),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($result->id);


        $response = $this->get(
            $this->urlFor(FoldersRouteConfig::ROUTE_ROOT_FOLDER_CHILDREN_GET, array('workspace_id' => $workspaceId)),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(2, $result->entries);
        $this->assertFalse($result->has_more);
    }

    public function testSetSelectiveSyncAndGetRootDirListing()
    {
        $workspaceId = 1;
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID2);

        $folder1 = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID);

        $response = $this->put(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_SELECTIVE_SYNC_SET, array('folder_id' => $folder1->getId())),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($result->id);


        $response = $this->get(
            $this->urlFor(FoldersRouteConfig::ROUTE_ROOT_FOLDER_CHILDREN_GET, array('workspace_id' => $workspaceId)),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(1, $result->entries);
        $this->assertNotNull($result->entries[0]->selective_sync);
        $this->assertFalse($result->has_more);
    }

    public function testSetInheritedSelectiveSyncAndGetDirListing()
    {
        $workspaceId = 1;
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID2);

        $folder = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID);
        $folder1 = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID, $folder);
        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, $folder);

        $response = $this->put(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_SELECTIVE_SYNC_SET, array('folder_id' => $folder->getId())),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($result->id);


        $response = $this->get(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_CHILDREN_GET, array(
                'folder_id' => $folder->getId()
            )),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(2, $result->entries);
        $this->assertNotNull($result->entries[0]->selective_sync);
        $this->assertNotNull($result->entries[1]->selective_sync);
        $this->assertFalse($result->has_more);
    }

    public function testFileUploadIntoParentFolder()
    {
        $workspaceId = 1;
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID);

        $response = $this->put(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_SELECTIVE_SYNC_SET, array('folder_id' => $folder->getId())),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($result->id);

        $user = $this->getEntityManager()->find(User::getEntityName(), self::USER_ID);

        $permissionService = $this->getContainer()->get('PermissionService');
        $permissionService->addPermission(
            $folder,
            $user,
            [PermissionType::FILE_READ],
            self::USER_ID,
            $folder->getWorkspace()->getPortal()
        );

        //mock environment
        $tmpName = tempnam(sys_get_temp_dir(), 'upload_');
        $settings = $this->getContainer()->get('settings');
        copy($settings['testsPath'] . '/data/files/' . self::TEST_FILE_NAME, $tmpName);
        $_FILES = array(
            'file' => array(
                'name' => self::TEST_FILE_NAME,
                'tmp_name' => $tmpName,
                'error' => UPLOAD_ERR_OK
            )
        );
        //end mock environment

        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_ADD, array('workspace_id' => $workspaceId)),
            array(
                'name' => self::TEST_FILE_NAME,
                'parent_id' => $folder->getId()
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals(Error::ITEM_SYNC_DISABLED, $result->type);
    }

    public function testFileTrash()
    {
        $workspaceId = 1;
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID);

        $user = $this->getEntityManager()->find(User::getEntityName(), self::USER_ID);

        $permissionService = $this->getContainer()->get('PermissionService');
        $permissionService->addPermission(
            $folder,
            $user,
            [PermissionType::FILE_READ],
            self::USER_ID,
            $folder->getWorkspace()->getPortal()
        );

        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, $folder);

        $response = $this->put(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_SELECTIVE_SYNC_SET, array('folder_id' => $folder->getId())),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($result->id);

        $response = $this->delete(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_DELETE, array('file_id' => $file->getId())),
            array(),
            $headers
        );

        $file = $this->getFileService()->getFile($file->getId());
        $result = Json::decode($response->getBody());
        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals(Error::ITEM_SYNC_DISABLED, $result->type);
        $this->assertFalse($file->getIsTrashed());
        $this->assertFalse($file->getIsDeleted());
    }

    public function testFileCopy()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder1 = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID, null);
        $folder2 = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID, null);

        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, $folder1);

        $response = $this->put(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_SELECTIVE_SYNC_SET, array('folder_id' => $folder2->getId())),
            array(),
            $headers
        );

        $newName = $file->getName() . ' (copy)';
        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_COPY, array('file_id' => $file->getId())),
            array(
                'parent' => array('id' => $folder2->getId()),
                'name' => $newName
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals(Error::ITEM_SYNC_DISABLED, $result->type);
    }

    public function testAddSubFolderByWorkspaceAdmin()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $workspaceId = 1;
        $folder = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID);

        $response = $this->put(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_SELECTIVE_SYNC_SET, array('folder_id' => $folder->getId())),
            array(),
            $headers
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

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals(Error::ITEM_SYNC_DISABLED, $result->type);
    }

    public function testUpdateFolderName()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $workspaceId = 1;
        $folder = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID);

        $response = $this->put(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_SELECTIVE_SYNC_SET, array('folder_id' => $folder->getId())),
            array(),
            $headers
        );

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

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals(Error::ITEM_SYNC_DISABLED, $result->type);
    }

    public function testTrashFolder()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $workspaceId = 1;
        $folder = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID);

        $response = $this->put(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_SELECTIVE_SYNC_SET, array('folder_id' => $folder->getId())),
            array(),
            $headers
        );

        $response = $this->delete(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_DELETE, array(
                'folder_id' => $folder->getId()
            )),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals(Error::ITEM_SYNC_DISABLED, $result->type);
        $this->assertFalse($folder->getIsTrashed());
        $this->assertFalse($folder->getIsDeleted());
    }

    public function testFolderCopy()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder1 = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID, null);
        $folder2 = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID, null);
        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, $folder1);

        $response = $this->put(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_SELECTIVE_SYNC_SET, array('folder_id' => $folder2->getId())),
            array(),
            $headers
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

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals(Error::ITEM_SYNC_DISABLED, $result->type);
    }

    /**
     * @return FileService
     */
    private function getFileService()
    {
        return $this->getContainer()->get('FileService');
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
                    'client_id' => self::PUBLIC_CLIENT_ID2
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
                )
            ),
            'acl_permissions' => array(

                array(
                    'acl_resource_id' => 1,
                    'acl_role_id' => 1,
                    'bit_mask' => $portalBitMask->getBitMask(PermissionType::PORTAL_ADMIN),
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'acl_resource_id' => 1,
                    'acl_role_id' => 2,
                    'bit_mask' => $portalBitMask->getBitMask(PermissionType::PORTAL_ACCESS),
                    'portal_id' => self::PORTAL_ID
                ),
                //workspace permissions
                array(
                    'acl_role_id' => 1,
                    'acl_resource_id' => 2,
                    'bit_mask' => $workspaceBitMask->getBitMask(PermissionType::WORKSPACE_ADMIN),
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'acl_role_id' => 2,
                    'acl_resource_id' => 2,
                    'bit_mask' => $workspaceBitMask->getBitMask(PermissionType::WORKSPACE_ADMIN),
                    'portal_id' => self::PORTAL_ID
                )
            ),
            'files' => array(),
            'selective_sync' => array(),
            'shared_links' => array(),
            'locks' => array(),
            'event_notifications' => array(),
            'licenses' => [
                [
                    'id' => self::LICENSE_ID,
                    'users_limit' => 3,
                    'workspaces_limit' => 0,
                    'storage_limit' => 5,
                    'file_size_limit' => 3
                ]
            ],
            'subscriptions' => [
                [
                    'id' => 1,
                    'portal_id' => self::PORTAL_ID,
                    'license_id' => self::LICENSE_ID,
                    'users_allocation' => 5
                ]
            ],
            'subscription_chargify_mappers' => []
        ));
    }
}