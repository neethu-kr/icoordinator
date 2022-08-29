<?php

namespace iCoordinator;

use Carbon\Carbon;
use Datetime;
use iCoordinator\Config\Route\FilesRouteConfig;
use iCoordinator\Config\Route\FoldersRouteConfig;
use iCoordinator\Entity\Acl\AclResource\AclFileResource;
use iCoordinator\Entity\Acl\AclResource\AclPortalResource;
use iCoordinator\Entity\Acl\AclResource\AclWorkspaceResource;
use iCoordinator\Entity\Acl\AclRole\AclUserRole;
use iCoordinator\Entity\File;
use iCoordinator\Entity\Portal;
use iCoordinator\Entity\User;
use iCoordinator\Entity\Workspace;
use iCoordinator\File\Encryptor;
use iCoordinator\File\Storage\StorageFactory;
use iCoordinator\Permissions\BitMask;
use iCoordinator\Permissions\PermissionType;
use iCoordinator\PHPUnit\DbUnit\DataSet\ArrayDataSet;
use iCoordinator\PHPUnit\Helper\FileHelper;
use iCoordinator\PHPUnit\TestCase;
use Laminas\Json\Json;
use Upload\FileInfo;


class FilesTest extends TestCase
{
    const USER_ID = 1;
    const USER_ID2 = 2;
    const USERNAME = 'test@user.com';
    const USERNAME2 = 'test2@user.com';
    const PASSWORD = 'password';
    const PUBLIC_CLIENT_ID = 'public_test';
    const DESKTOP_CLIENT_ID = 'icoordinator_desktop';
    const TEST_FILE_NAME = 'Document1.pdf';
    const TEST_FILE_NAME2 = 'textfile.txt';
    const TEST_FILE_NAME_BIG = 'bigfile.ifc';
    const PORTAL_ID = 1;
    const META_FIELD_ID = 1;
    const FOLDER_ID = 7;
    const LICENSE_ID = 1;
    const GB =  1073741824;
    const WORKSPACE_ID = 1;
    const WORKSPACE_ID2 = 2;

    public function setUp(): void
    {
        parent::setUp();

        FileHelper::initializeFileMocks($this);
        FileHelper::clearTmpStorage($this);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
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
                    'id' => 1,
                    'name' => 'Workspace 1',
                    'portal_id' => self::PORTAL_ID,
                    'desktop_sync' => 1
                ),
                array(
                    'id' => 2,
                    'name' => 'Workspace 2',
                    'portal_id' => self::PORTAL_ID,
                    'desktop_sync' => 1
                ),
                array(
                    'id' => 3,
                    'name' => 'Workspace 3',
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
                ),
                array(
                    'id' => 6,
                    'entity_type' => AclFileResource::ACL_RESOURCE_ENTITY_TYPE,
                    'entity_id' => 1
                ),
                array(
                    'id' => 7,
                    'entity_type' => AclFileResource::ACL_RESOURCE_ENTITY_TYPE,
                    'entity_id' => 2
                ),
                array(
                    'id' => 8,
                    'entity_type' => AclFileResource::ACL_RESOURCE_ENTITY_TYPE,
                    'entity_id' => 3
                ),
                array(
                    'id' => 9,
                    'entity_type' => AclFileResource::ACL_RESOURCE_ENTITY_TYPE,
                    'entity_id' => 4
                ),
                array(
                    'id' => 10,
                    'entity_type' => AclFileResource::ACL_RESOURCE_ENTITY_TYPE,
                    'entity_id' => 5
                ),
                array(
                    'id' => 11,
                    'entity_type' => AclFileResource::ACL_RESOURCE_ENTITY_TYPE,
                    'entity_id' => 6
                ),
                array(
                    'id' => 12,
                    'entity_type' => AclFileResource::ACL_RESOURCE_ENTITY_TYPE,
                    'entity_id' => 7
                ),
                array(
                    'id' => 13,
                    'entity_type' => AclFileResource::ACL_RESOURCE_ENTITY_TYPE,
                    'entity_id' => 8
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
                ),
                //file permissions
                array(
                    'acl_role_id' => self::USER_ID2,
                    'acl_resource_id' => 8,
                    'bit_mask' => $fileBitMask->getBitMask(PermissionType::FILE_READ),
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'acl_role_id' => self::USER_ID2,
                    'acl_resource_id' => 6,
                    'bit_mask' => $fileBitMask->getBitMask(array(
                        PermissionType::FILE_READ,
                        PermissionType::FILE_EDIT,
                        PermissionType::FILE_GRANT_READ,
                        PermissionType::FILE_GRANT_EDIT
                    )),
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'acl_role_id' => self::USER_ID2,
                    'acl_resource_id' => 9,
                    'bit_mask' => $fileBitMask->getBitMask(array(
                        PermissionType::FILE_READ,
                        PermissionType::FILE_EDIT,
                        PermissionType::FILE_GRANT_READ,
                        PermissionType::FILE_GRANT_EDIT
                    )),
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'acl_role_id' => self::USER_ID,
                    'acl_resource_id' => 6,
                    'bit_mask' => $fileBitMask->getBitMask(array(
                        PermissionType::FILE_READ,
                        PermissionType::FILE_EDIT,
                        PermissionType::FILE_GRANT_READ,
                        PermissionType::FILE_GRANT_EDIT
                    )),
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'acl_role_id' => self::USER_ID2,
                    'acl_resource_id' => 11,
                    'bit_mask' => $fileBitMask->getBitMask(PermissionType::FILE_EDIT),
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'acl_role_id' => self::USER_ID,
                    'acl_resource_id' => 7,
                    'bit_mask' => $fileBitMask->getBitMask(PermissionType::FILE_EDIT),
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'acl_role_id' => self::USER_ID,
                    'acl_resource_id' => 12,
                    'bit_mask' => $fileBitMask->getBitMask(array(
                        PermissionType::FILE_READ,
                        PermissionType::FILE_EDIT,
                        PermissionType::FILE_GRANT_READ,
                        PermissionType::FILE_GRANT_EDIT
                    )),
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'acl_role_id' => self::USER_ID2,
                    'acl_resource_id' => 13,
                    'bit_mask' => $fileBitMask->getBitMask(PermissionType::FILE_EDIT),
                    'portal_id' => self::PORTAL_ID
                )
            ),
            'files' => array(
                array(
                    'id' => 1,
                    'type' => 'file',
                    'name' => 'Document1.pdf',
                    'workspace_id' => 1,
                    'size' => 999,
                    'etag' => 1,
                    'created_by' => self::USER_ID2,
                    'owned_by' => self::USER_ID2,
                    'content_created_at' => Carbon::now()->toDateTimeString(),
                    'content_modified_at' => Carbon::now()->toDateTimeString()
                ),
                array(
                    'id' => 2,
                    'type' => 'file',
                    'name' => 'Document2.pdf',
                    'workspace_id' => 1,
                    'size' => 999,
                    'etag' => 1,
                    'created_by' => self::USER_ID,
                    'owned_by' => self::USER_ID,
                    'content_created_at' => Carbon::now()->toDateTimeString(),
                    'content_modified_at' => Carbon::now()->toDateTimeString()
                ),
                array(
                    'id' => 3,
                    'type' => 'file',
                    'name' => 'Document3.pdf',
                    'workspace_id' => 1,
                    'size' => 999,
                    'etag' => 1,
                    'created_by' => self::USER_ID,
                    'owned_by' => self::USER_ID,
                    'content_created_at' => Carbon::now()->toDateTimeString(),
                    'content_modified_at' => Carbon::now()->toDateTimeString()
                ),
                array(
                    'id' => 4,
                    'type' => 'file',
                    'name' => 'Document4.pdf',
                    'workspace_id' => 1,
                    'size' => 999,
                    'etag' => 1,
                    'created_by' => self::USER_ID2,
                    'owned_by' => self::USER_ID2,
                    'content_created_at' => Carbon::now()->toDateTimeString(),
                    'content_modified_at' => Carbon::now()->toDateTimeString()
                ),
                array(
                    'id' => 5,
                    'type' => 'file',
                    'name' => 'Document5.pdf',
                    'workspace_id' => 3,
                    'size' => 999,
                    'etag' => 1,
                    'created_by' => self::USER_ID,
                    'owned_by' => self::USER_ID,
                    'content_created_at' => Carbon::now()->toDateTimeString(),
                    'content_modified_at' => Carbon::now()->toDateTimeString()
                ),
                array(
                    'id' => 6,
                    'type' => 'file',
                    'name' => 'Document6.pdf',
                    'workspace_id' => 1,
                    'size' => 999,
                    'etag' => 1,
                    'created_by' => self::USER_ID,
                    'owned_by' => self::USER_ID,
                    'content_created_at' => Carbon::now()->toDateTimeString(),
                    'content_modified_at' => Carbon::now()->toDateTimeString()
                ),
                array(
                    'id' => self::FOLDER_ID,
                    'type' => 'folder',
                    'name' => 'Folder 1',
                    'workspace_id' => 1,
                    'size' => 0,
                    'etag' => 1,
                    'created_by' => self::USER_ID,
                    'owned_by' => self::USER_ID,
                    'content_created_at' => Carbon::now()->toDateTimeString(),
                    'content_modified_at' => Carbon::now()->toDateTimeString()
                ),
                array(
                    'id' => 8,
                    'type' => 'file',
                    'name' => 'Document1.pdf',
                    'workspace_id' => 2,
                    'size' => 999,
                    'etag' => 1,
                    'created_by' => self::USER_ID,
                    'owned_by' => self::USER_ID,
                    'content_created_at' => Carbon::now()->toDateTimeString(),
                    'content_modified_at' => Carbon::now()->toDateTimeString()
                ),
            ),
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
            'subscription_chargify_mappers' => [],
            'file_versions' => [],
            'file_uploads' => [],
            'events' => [],
            'history_events' => [],
            'shared_links' => [],
            'locks' => [],
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
            'meta_fields_values' => array(),
            'selective_sync' => array(),
            'event_notifications' => array(),
            'download_zip_token_files' => array(),
            'download_zip_tokens' => array()
        ));
    }

    /*public function testFileChunkedUploadExceedingSizeLimit()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        // Create file upload
        $response = $this->post(
            '/workspaces/1/files/uploads',
            array(
                'size' => 4*self::GB,
                'parent_id' => 7
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(403, $response->getStatusCode());

    }

    public function testFileChunkedUploadByWorkspaceAdmin()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $settings = $this->getContainer()->get('settings');
        $srcFile = $settings['testsPath'] . '/data/files/' . self::TEST_FILE_NAME_BIG;
        $fileSize = filesize($srcFile);
        // Create file upload
        $response = $this->post(
            '/workspaces/1/files/uploads',
            array(
                'size' => $fileSize,
                'parent_id' => 7,
                'hash' => hash_file('sha256', $srcFile)
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertNotEmpty($result->id);
        $this->assertNotEmpty($result->expires_at);
        $this->assertEquals(0, $result->offset);

        $uploadId = $result->id;

        //Starting to upload chunks
        $tmpName = tempnam(sys_get_temp_dir(), 'upload_');
        $settings = $this->getContainer()->get('settings');
        $srcFile = $settings['testsPath'] . '/data/files/' . self::TEST_FILE_NAME_BIG;
        $fileContent = file_get_contents($srcFile);
        $fileSize = filesize($srcFile);
        $chunkSize = 1048576;
        $offset = 0;
        $srcFp = fopen($srcFile, 'rb');

        do {
            fseek($srcFp, $offset);
            $chunk = fread($srcFp, $chunkSize);
            file_put_contents($tmpName, $chunk);

            $_FILES = array(
                'file' => array(
                    'name' => self::TEST_FILE_NAME_BIG,
                    'tmp_name' => $tmpName,
                    'error' => UPLOAD_ERR_OK,
                    'size' => strlen($chunk)
                )
            );

            $response = $this->post(
                sprintf('/files/uploads/%s', $uploadId),
                [
                    'offset' => $offset,
                ]
            );

            $result = json_decode($response->getBody());

            $this->assertEquals(200, $response->getStatusCode());
            $this->assertEquals(min($offset + $chunkSize, $fileSize), $result->offset);

            $offset = $result->offset;
        } while ($offset < $fileSize);

        $_FILES = [];

        //Finish upload
        $contentCreatedAt = Carbon::now()->format(DateTime::ISO8601);
        $contentModifiedAt = Carbon::now()->format(DateTime::ISO8601);
        $response = $this->post(
            '/workspaces/1/files/content',
            array(
                'name' => self::TEST_FILE_NAME_BIG,
                'upload_id' => $uploadId,
                'parent_id' => 7,
                'content_created_at' => $contentCreatedAt,
                'content_modified_at' => $contentModifiedAt
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(202, $response->getStatusCode());
        $this->assertNotEmpty($result->id);
        $this->assertEquals(7, $result->parent->id);
        $this->assertEquals(self::TEST_FILE_NAME_BIG, $result->name);
        $this->assertEquals($fileSize, $result->size);
        $this->assertEquals($contentCreatedAt, $result->content_created_at);
        $this->assertEquals($contentModifiedAt, $result->content_modified_at);

        $fileId = $result->id;

        while ($result->is_uploading
            && ($response->getStatusCode() == 200 || $response->getStatusCode() == 202)) {
            sleep(3);
            $this->getEntityManager()->clear();
            $response = $this->get(
                $this->urlFor(FilesRouteConfig::ROUTE_FILE_GET, array('file_id' => $fileId)),
                array(),
                $headers
            );
            $result = Json::decode($response->getBody());
        }
        //Test file download
        $response = $this->get(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_GET_CONTENT, array('file_id' => $fileId)),
            array(),
            $headers
        );
        //Follow redirect
        $response = $this->get(
            $response->getHeaderLine('Location'),
            array(),
            array()
        );

        $bodyContent = $response->getBody()->getContents();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($fileSize, $response->getHeaderLine('Content-Length'));
        $this->assertEquals(strlen($fileContent), $response->getHeaderLine('Content-Length'));
        $this->assertEquals($fileContent, substr($bodyContent, 0, $fileSize));
    }

    public function testFileChunkedUploadToParentFolderByUserWithEditRights()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $workspaceId = 1;
        $folderId = 7;
        $workspace  = $this->getWorkspaceService()->getWorkspace($workspaceId);
        $folder     = $this->getFileService()->getFile($folderId);

        $createdBy  = $this->getUserService()->getUser(self::USER_ID);
        $grantTo    = $this->getUserService()->getUser(self::USER_ID2);

        $this->getPermissionService()->addPermission($folder, $grantTo, [PermissionType::FILE_EDIT], $createdBy, $workspace->getPortal());

        $settings = $this->getContainer()->get('settings');
        $srcFile = $settings['testsPath'] . '/data/files/' . self::TEST_FILE_NAME_BIG;
        $fileSize = filesize($srcFile);
        // Create file upload
        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_CHUNKED_UPLOAD_IN_FOLDER, array('folder_id' => $folderId)),
            array(
                'size' => $fileSize,
                'hash' => hash_file('sha256', $srcFile)
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertNotEmpty($result->id);
        $this->assertNotEmpty($result->expires_at);
        $this->assertEquals(0, $result->offset);

        $uploadId = $result->id;

        //Starting to upload chunks
        $tmpName = tempnam(sys_get_temp_dir(), 'upload_');
        $fileContent = file_get_contents($srcFile);
        $chunkSize = 1048576;
        $offset = 0;
        $srcFp = fopen($srcFile, 'rb');

        do {
            fseek($srcFp, $offset);
            $chunk = fread($srcFp, $chunkSize);
            file_put_contents($tmpName, $chunk);

            $_FILES = array(
                'file' => array(
                    'name' => self::TEST_FILE_NAME_BIG,
                    'tmp_name' => $tmpName,
                    'error' => UPLOAD_ERR_OK,
                    'size' => strlen($chunk)
                )
            );

            $response = $this->post(
                sprintf('/files/uploads/%s', $uploadId),
                [
                    'offset' => $offset,
                ]
            );

            $result = json_decode($response->getBody());

            $this->assertEquals(200, $response->getStatusCode());
            $this->assertEquals(min($offset + $chunkSize, $fileSize), $result->offset);

            $offset = $result->offset;
        } while ($offset < $fileSize);

        $_FILES = [];

        //Finish upload
        $contentCreatedAt = Carbon::now()->format(DateTime::ISO8601);
        $contentModifiedAt = Carbon::now()->format(DateTime::ISO8601);
        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_ADD_IN_FOLDER, array('folder_id' => $folderId)),
            array(
                'name' => self::TEST_FILE_NAME_BIG,
                'upload_id' => $uploadId,
                'parent_id' => $folderId,
                'content_created_at' => $contentCreatedAt,
                'content_modified_at' => $contentModifiedAt
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(202, $response->getStatusCode());
        $this->assertNotEmpty($result->id);
        $this->assertEquals(7, $result->parent->id);
        $this->assertEquals(self::TEST_FILE_NAME_BIG, $result->name);
        $this->assertEquals($fileSize, $result->size);
        $this->assertEquals($contentCreatedAt, $result->content_created_at);
        $this->assertEquals($contentModifiedAt, $result->content_modified_at);
        $this->assertTrue($result->is_uploading);

        $fileId = $result->id;

        while ($result->is_uploading
            && ($response->getStatusCode() == 200 || $response->getStatusCode() == 202)) {
            sleep(3);
            $this->getEntityManager()->clear();
            $response = $this->get(
                $this->urlFor(FilesRouteConfig::ROUTE_FILE_GET, array('file_id' => $fileId)),
                array(),
                $headers
            );
            $result = Json::decode($response->getBody());
        }
        //Test file download
        $response = $this->get(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_GET_CONTENT, array('file_id' => $fileId)),
            array(),
            $headers
        );
        //Follow redirect
        $response = $this->get(
            $response->getHeaderLine('Location'),
            array(),
            array()
        );

        $bodyContent = $response->getBody()->getContents();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($fileSize, $response->getHeaderLine('Content-Length'));
        $this->assertEquals(strlen($fileContent), $response->getHeaderLine('Content-Length'));
        $this->assertEquals($fileContent, substr($bodyContent, 0, $fileSize));
    }

    public function testFileChunkedUploadToParentFolderInheritingMetaFields()
    {
        //create by portal admin
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $parentFolder = $this->getFileService()->getFile(self::FOLDER_ID);

        $metaFieldService = $this->getContainer()->get('MetaFieldService');
        $metaField = $metaFieldService->getMetaField(self::META_FIELD_ID);

        $value = $metaField->getOptions()->current();
        $metaFieldValue = $metaFieldService->addMetaFieldValue(
            $parentFolder,
            array(
                'meta_field' => array('id' => $metaField->getId()),
                'value' => $value
            ),
            self::USER_ID
        );

        $settings = $this->getContainer()->get('settings');
        $srcFile = $settings['testsPath'] . '/data/files/' . self::TEST_FILE_NAME_BIG;
        $fileSize = filesize($srcFile);
        // Create file upload
        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_CHUNKED_UPLOAD_IN_FOLDER, array('folder_id' => self::FOLDER_ID)),
            array(
                'size' => $fileSize,
                'hash' => hash_file('sha256', $srcFile)
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertNotEmpty($result->id);
        $this->assertNotEmpty($result->expires_at);
        $this->assertEquals(0, $result->offset);

        $uploadId = $result->id;

        //Starting to upload chunks
        $tmpName = tempnam(sys_get_temp_dir(), 'upload_');
        $fileContent = file_get_contents($srcFile);
        $chunkSize = 1048576;
        $offset = 0;
        $srcFp = fopen($srcFile, 'rb');

        do {
            fseek($srcFp, $offset);
            $chunk = fread($srcFp, $chunkSize);
            file_put_contents($tmpName, $chunk);

            $_FILES = array(
                'file' => array(
                    'name' => self::TEST_FILE_NAME_BIG,
                    'tmp_name' => $tmpName,
                    'error' => UPLOAD_ERR_OK,
                    'size' => strlen($chunk)
                )
            );

            $response = $this->post(
                sprintf('/files/uploads/%s', $uploadId),
                [
                    'offset' => $offset,
                ]
            );

            $result = json_decode($response->getBody());

            $this->assertEquals(200, $response->getStatusCode());
            $this->assertEquals(min($offset + $chunkSize, $fileSize), $result->offset);

            $offset = $result->offset;
        } while ($offset < $fileSize);

        $_FILES = [];

        //Finish upload
        $contentCreatedAt = Carbon::now()->format(DateTime::ISO8601);
        $contentModifiedAt = Carbon::now()->format(DateTime::ISO8601);
        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_ADD_IN_FOLDER, array('folder_id' => self::FOLDER_ID)),
            array(
                'name' => self::TEST_FILE_NAME_BIG,
                'upload_id' => $uploadId,
                'parent_id' => self::FOLDER_ID,
                'content_created_at' => $contentCreatedAt,
                'content_modified_at' => $contentModifiedAt
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(202, $response->getStatusCode());
        $this->assertNotEmpty($result->id);
        $this->assertEquals(7, $result->parent->id);
        $this->assertEquals(self::TEST_FILE_NAME_BIG, $result->name);
        $this->assertEquals($fileSize, $result->size);
        $this->assertEquals($contentCreatedAt, $result->content_created_at);
        $this->assertEquals($contentModifiedAt, $result->content_modified_at);
        $this->assertTrue($result->is_uploading);

        $fileId = $result->id;

        while ($result->is_uploading
            && ($response->getStatusCode() == 200 || $response->getStatusCode() == 202)) {
            sleep(3);
            $this->getEntityManager()->clear();
            $response = $this->get(
                $this->urlFor(FilesRouteConfig::ROUTE_FILE_GET, array('file_id' => $fileId)),
                array(),
                $headers
            );
            $result = Json::decode($response->getBody());
        }
        //Test file download
        $response = $this->get(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_GET_CONTENT, array('file_id' => $fileId)),
            array(),
            $headers
        );
        //Follow redirect
        $response = $this->get(
            $response->getHeaderLine('Location'),
            array(),
            array()
        );

        $bodyContent = $response->getBody()->getContents();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($fileSize, $response->getHeaderLine('Content-Length'));
        $this->assertEquals(strlen($fileContent), $response->getHeaderLine('Content-Length'));
        $this->assertEquals($fileContent, substr($bodyContent, 0, $fileSize));
        $file = $this->getFileService()->getFile($result->id);
        $this->assertNotEmpty($parentFolder->getMetaFieldsValues());
        $this->assertNotEmpty($file->getMetaFieldsValues());
    }

    public function testFileChunkedUploadToParentFolderByUserWithEditRightsIncorrectHash()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $workspaceId = 1;
        $folderId = 7;
        $workspace  = $this->getWorkspaceService()->getWorkspace($workspaceId);
        $folder     = $this->getFileService()->getFile($folderId);

        $createdBy  = $this->getUserService()->getUser(self::USER_ID);
        $grantTo    = $this->getUserService()->getUser(self::USER_ID2);

        $this->getPermissionService()->addPermission($folder, $grantTo, [PermissionType::FILE_EDIT], $createdBy, $workspace->getPortal());

        $settings = $this->getContainer()->get('settings');
        $srcFile = $settings['testsPath'] . '/data/files/' . self::TEST_FILE_NAME_BIG;
        $fileSize = filesize($srcFile);
        // Create file upload
        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_CHUNKED_UPLOAD_IN_FOLDER, array('folder_id' => $folderId)),
            array(
                'size' => $fileSize,
                'hash' =>  "incorrect_" . hash_file('sha256', $srcFile)
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertNotEmpty($result->id);
        $this->assertNotEmpty($result->expires_at);
        $this->assertEquals(0, $result->offset);

        $uploadId = $result->id;

        //Starting to upload chunks
        $tmpName = tempnam(sys_get_temp_dir(), 'upload_');
        //$fileContent = file_get_contents($srcFile);
        $chunkSize = 52428800;
        $offset = 0;
        $srcFp = fopen($srcFile, 'rb');

        do {
            fseek($srcFp, $offset);
            $chunk = fread($srcFp, $chunkSize);
            file_put_contents($tmpName, $chunk);

            $_FILES = array(
                'file' => array(
                    'name' => self::TEST_FILE_NAME_BIG,
                    'tmp_name' => $tmpName,
                    'error' => UPLOAD_ERR_OK,
                    'size' => strlen($chunk)
                )
            );

            $response = $this->post(
                sprintf('/files/uploads/%s', $uploadId),
                [
                    'offset' => $offset,
                ]
            );

            $result = json_decode($response->getBody());

            $this->assertEquals(200, $response->getStatusCode());
            $this->assertEquals(min($offset + $chunkSize, $fileSize), $result->offset);

            $offset = $result->offset;
        } while ($offset < $fileSize);

        $_FILES = [];

        //Finish upload
        $contentCreatedAt = Carbon::now()->format(DateTime::ISO8601);
        $contentModifiedAt = Carbon::now()->format(DateTime::ISO8601);
        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_ADD_IN_FOLDER, array('folder_id' => $folderId)),
            array(
                'name' => self::TEST_FILE_NAME_BIG,
                'upload_id' => $uploadId,
                'parent_id' => $folderId,
                'content_created_at' => $contentCreatedAt,
                'content_modified_at' => $contentModifiedAt
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(202, $response->getStatusCode());
        $this->assertNotEmpty($result->id);
        $this->assertEquals(7, $result->parent->id);
        $this->assertEquals(self::TEST_FILE_NAME_BIG, $result->name);
        $this->assertEquals($fileSize, $result->size);
        $this->assertEquals($contentCreatedAt, $result->content_created_at);
        $this->assertEquals($contentModifiedAt, $result->content_modified_at);
        $this->assertTrue($result->is_uploading);

        $fileId = $result->id;

        while ($response->getStatusCode() == 200 || $response->getStatusCode() == 202) {
            sleep(3);
            $this->getEntityManager()->clear();
            $response = $this->get(
                $this->urlFor(FilesRouteConfig::ROUTE_FILE_GET, array('file_id' => $fileId)),
                array(),
                $headers
            );
        }
        $this->assertEquals(404, $response->getStatusCode());
    }*/

    public function testFileGetByOwner()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        //getting with ownership access
        $response = $this->get(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_GET, array('file_id' => 1)),
            array(),
            $headers
        );
        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(1, $result->id);
        $this->assertNotEmpty($result->name);
    }

    public function testFileGetByUserWithReadAccess()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        //getting with read access
        $response = $this->get(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_GET, array('file_id' => 3)),
            array(),
            $headers
        );
        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(3, $result->id);
        $this->assertNotEmpty($result->name);
    }

    public function testFileGetByUserWithoutAccess()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        //getting without access
        $response = $this->get(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_GET, array('file_id' => 2)),
            array(),
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testFileGetNonExisting()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        //getting non-existing

        $response = $this->get(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_GET, array('file_id' => 101)),
            array(),
            $headers
        );

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testFileCreateByWorkspaceAdmin()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $testName = 'Document1.pdf';

        //mock environment
        $fileInfo = $this->mockFileUploadEnvironment();
        $fileSize = $fileInfo->getSize();
        $fileMimeType = $fileInfo->getMimetype();
        //end mock environment

        $nowDate = "2016-02-15T15:05:43+02:00";
        $contentCreatedAt = $nowDate;
        $contentModifiedAt = $nowDate;
        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_ADD, array('workspace_id' => 1)),
            array(
                'name' => $testName,
                'parent_id' => 7,
                'content_created_at' => $contentCreatedAt,
                'content_modified_at' => $contentModifiedAt
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertNotEmpty($result->id);
        $this->assertEquals(7, $result->parent->id);
        $this->assertEquals($testName, $result->name);
        $this->assertEquals($fileSize, $result->size);
        $this->assertEquals($fileMimeType, $result->mime_type);
        $this->assertEquals(Carbon::createFromTimestamp(strtotime($contentCreatedAt))->format(DateTime::ISO8601), $result->content_created_at);
        $this->assertEquals(Carbon::createFromTimestamp(strtotime($contentModifiedAt))->format(DateTime::ISO8601), $result->content_modified_at);


        //fetching newly created file
        $response = $this->get(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_GET, array('file_id' => $result->id)),
            array(),
            $headers
        );
        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($result->id);
        $this->assertEquals($testName, $result->name);
    }

    public function testFileCreateByWorkspaceAdminIncorrectHash()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $testName = 'Document1.pdf';

        //mock environment
        $fileInfo = $this->mockFileUploadEnvironment();

        //end mock environment

        $nowDate = "2016-02-15T15:05:43+02:00";
        $contentCreatedAt = $nowDate;
        $contentModifiedAt = $nowDate;
        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_ADD, array('workspace_id' => 1)),
            array(
                'name' => $testName,
                'parent_id' => 7,
                'content_created_at' => $contentCreatedAt,
                'content_modified_at' => $contentModifiedAt,
                'hash' => $fileInfo->getHash('sha256') . "_incorrect_hash"
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertNull($result->id);
    }

    public function testFileCreateWithEmptyName()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $this->mockFileUploadEnvironment();

        //create with empty name
        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_ADD, array('workspace_id' => 1)),
            array(
                'name' => ''
            ),
            $headers
        );
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testFileUploadIntoParentFolder()
    {
        //create by portal admin
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

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
        $fileInfo = new FileInfo($_FILES['file']['tmp_name']);
        $fileSize = $fileInfo->getSize();
        $fileMimeType = $fileInfo->getMimetype();
        //end mock environment

        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_ADD, array('workspace_id' => 1)),
            array(
                'name' => self::TEST_FILE_NAME,
                'parent_id' => 7
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertNotEmpty($result->id);
        $this->assertNotNull($result->parent);
        $this->assertEquals(7, $result->parent->id);
        $this->assertEquals(self::TEST_FILE_NAME, $result->name);
        $this->assertEquals($fileSize, $result->size);
        $this->assertEquals($fileMimeType, $result->mime_type);

    }
    public function testFileUploadIntoTrashedParentFolder()
    {
        //create by portal admin
        $workspaceId = 1;

        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);
        $parentFolder = $this->getFileService()->getFile(self::FOLDER_ID);
        $folder = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID, $parentFolder);
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
        $fileInfo = new FileInfo($_FILES['file']['tmp_name']);
        $fileSize = $fileInfo->getSize();
        $fileMimeType = $fileInfo->getMimetype();
        //end mock environment

        $response = $this->delete(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_DELETE, array(
                'folder_id' => $folder->getId()
            )),
            array(),
            $headers
        );

        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_ADD, array('workspace_id' => $workspaceId)),
            array(
                'name' => self::TEST_FILE_NAME,
                'parent_id' => $folder->getId()
            ),
            $headers
        );

        $this->assertEquals(400, $response->getStatusCode());


    }
    public function testZapierFileUploadIntoParentFolder()
    {
        //create by portal admin
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

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
        $fileInfo = new FileInfo($_FILES['file']['tmp_name']);
        $fileSize = $fileInfo->getSize();
        $fileMimeType = $fileInfo->getMimetype();
        //end mock environment

        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_ADD, array('workspace_id' => 1)),
            array(
                'parent_id' => 7,
                'data' => '{"name":"'.self::TEST_FILE_NAME.'"}'
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertNotEmpty($result->id);
        $this->assertNotNull($result->parent);
        $this->assertEquals(7, $result->parent->id);
        $this->assertEquals(self::TEST_FILE_NAME, $result->name);
        $this->assertEquals($fileSize, $result->size);
        $this->assertEquals($fileMimeType, $result->mime_type);

    }

    public function testFileUploadIntoParentFolderInheritingMetaFields()
    {
        //create by portal admin
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $parentFolder = $this->getFileService()->getFile(self::FOLDER_ID);

        $metaFieldService = $this->getContainer()->get('MetaFieldService');
        $metaField = $metaFieldService->getMetaField(self::META_FIELD_ID);

        $value = $metaField->getOptions()->current();
        $metaFieldValue = $metaFieldService->addMetaFieldValue(
            $parentFolder,
            array(
                'meta_field' => array('id' => $metaField->getId()),
                'value' => $value
            ),
            self::USER_ID
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
        $fileInfo = new FileInfo($_FILES['file']['tmp_name']);
        $fileSize = $fileInfo->getSize();
        $fileMimeType = $fileInfo->getMimetype();
        //end mock environment

        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_ADD, array('workspace_id' => 1)),
            array(
                'name' => self::TEST_FILE_NAME,
                'parent_id' => self::FOLDER_ID
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertNotEmpty($result->id);
        $this->assertNotNull($result->parent);
        $this->assertEquals(self::FOLDER_ID, $result->parent->id);
        $this->assertEquals(self::TEST_FILE_NAME, $result->name);
        $this->assertEquals($fileSize, $result->size);
        $this->assertEquals($fileMimeType, $result->mime_type);

        $file = $this->getFileService()->getFile($result->id);
        $this->assertNotEmpty($parentFolder->getMetaFieldsValues());
        $this->assertNotEmpty($file->getMetaFieldsValues());

        $metaFieldService->deleteMetaFieldValue($metaFieldValue,self::USER_ID);

    }

    public function testFileUploadIntoParentFolderByUserWithEditRights()
    {

        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

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
        $fileInfo = new FileInfo($_FILES['file']['tmp_name']);
        $fileSize = $fileInfo->getSize();
        $fileMimeType = $fileInfo->getMimetype();
        //end mock environment

        $workspaceId = 1;
        $folderId = 7;
        $workspace  = $this->getWorkspaceService()->getWorkspace($workspaceId);
        $folder     = $this->getFileService()->getFile($folderId);

        $createdBy  = $this->getUserService()->getUser(self::USER_ID);
        $grantTo    = $this->getUserService()->getUser(self::USER_ID2);

        $this->getPermissionService()->addPermission($folder, $grantTo, [PermissionType::FILE_EDIT], $createdBy, $workspace->getPortal());

        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_ADD_IN_FOLDER, array('folder_id' => $folderId)),
            array(
                'name' => self::TEST_FILE_NAME
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertNotEmpty($result->id);
        $this->assertNotNull($result->parent);
        $this->assertEquals(7, $result->parent->id);
        $this->assertEquals(self::TEST_FILE_NAME, $result->name);
        $this->assertEquals($fileSize, $result->size);
        $this->assertEquals($fileMimeType, $result->mime_type);

    }

    public function testFileUploadIntoParentFolderByUserWithReadRights()
    {

        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

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
        $fileInfo = new FileInfo($_FILES['file']['tmp_name']);
        $fileSize = $fileInfo->getSize();
        $fileMimeType = $fileInfo->getMimetype();
        //end mock environment

        $workspaceId = 1;
        $folderId = 7;
        $workspace  = $this->getWorkspaceService()->getWorkspace($workspaceId);
        $folder     = $this->getFileService()->getFile($folderId);

        $createdBy  = $this->getUserService()->getUser(self::USER_ID);
        $grantTo    = $this->getUserService()->getUser(self::USER_ID2);

        $this->getPermissionService()->addPermission($folder, $grantTo, [PermissionType::FILE_READ], $createdBy, $workspace->getPortal());

        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_ADD_IN_FOLDER, array('folder_id' => $folderId)),
            array(
                'name' => self::TEST_FILE_NAME,
                'parent_id' => $folderId,
                'content_modified_at' => '2015-09-07T14:06:51+02:00'
            ),
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());

    }

    public function testFileUploadIntoRootFolderByNotWorkspaceAdmin()
    {

        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

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
        $fileInfo = new FileInfo($_FILES['file']['tmp_name']);
        $fileSize = $fileInfo->getSize();
        $fileMimeType = $fileInfo->getMimetype();
        //end mock environment

        $folderId = 0;

        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_ADD_IN_FOLDER, array('folder_id' => $folderId)),
            array(
                'name' => self::TEST_FILE_NAME,
            ),
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());

    }

    public function testFileUploadIntoRootFolder()
    {
        //create by portal admin
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

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
        $fileInfo = new FileInfo($_FILES['file']['tmp_name']);
        $fileSize = $fileInfo->getSize();
        $fileMimeType = $fileInfo->getMimetype();
        //end mock environment

        $fileName = 'Document_File_Upload_Into_Root_Folder.pdf';
        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_ADD, array('workspace_id' => 3)),
            array(
                'name' => $fileName,
                'parent_id' => 0
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertNotEmpty($result->id);
        $this->assertNull($result->parent);
        $this->assertEquals($fileName, $result->name);
        $this->assertEquals($fileSize, $result->size);
        $this->assertEquals($fileMimeType, $result->mime_type);
    }

    public function testFileUploadIntoRootFolderWithDefaultSyncOff()
    {
        //create by portal admin
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::DESKTOP_CLIENT_ID);

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
        $fileInfo = new FileInfo($_FILES['file']['tmp_name']);
        $fileSize = $fileInfo->getSize();
        $fileMimeType = $fileInfo->getMimetype();
        //end mock environment

        $fileName = 'Document_File_Upload_Into_Root_Folder.pdf';
        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_ADD, array('workspace_id' => 3)),
            array(
                'name' => $fileName,
                'parent_id' => 0
            ),
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());
    }


    public function testFileUploadIntoRootFolderWithoutAccessToWorkspace()
    {
        //create if not have workspace access
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_ADD, array('workspace_id' => 2)),
            array(
                'name' => 'Test workspace'
            ),
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testFileDownloadAfterUploadIntoParentFolder()
    {
        //create by portal admin
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        //mock environment
        $tmpName = tempnam(sys_get_temp_dir(), 'upload_');
        $settings = $this->getContainer()->get('settings');
        $srcFile = $settings['testsPath'] . '/data/files/' . self::TEST_FILE_NAME2;
        copy($srcFile, $tmpName);
        $fileContent = file_get_contents($srcFile);
        $_FILES = array(
            'file' => array(
                'name' => self::TEST_FILE_NAME2,
                'tmp_name' => $tmpName,
                'error' => UPLOAD_ERR_OK
            )
        );
        $fileInfo = new FileInfo($_FILES['file']['tmp_name']);
        $fileSize = $fileInfo->getSize();
        $fileMimeType = $fileInfo->getMimetype();
        //end mock environment

        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_ADD, array('workspace_id' => 1)),
            array(
                'name' => self::TEST_FILE_NAME2,
                'parent_id' => 7
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertNotEmpty($result->id);
        $this->assertNotNull($result->parent);
        $this->assertEquals(7, $result->parent->id);
        $this->assertEquals(self::TEST_FILE_NAME2, $result->name);
        $this->assertEquals($fileSize, $result->size);
        $this->assertEquals($fileMimeType, $result->mime_type);

        $response = $this->get(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_GET_CONTENT, array('file_id' => $result->id)),
            array('open_style' => 'attachment'),
            $headers
        );

        $headers = $response->getHeaders();

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
        $this->assertArrayHasKey('Location', $headers);

        $location = $response->getHeaderLine('Location');

        $response = $this->get(
            $location,
            array(),
            array()
        );

        ob_start();
        $this->getApp()->respond($response);
        $bodyContent = ob_get_clean();

        $headers = $response->getHeaders();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('Content-Disposition', $headers);
        $this->assertArrayHasKey('Content-Length', $headers);
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertEquals($result->size, $response->getHeaderLine('Content-Length'));
        $this->assertEquals(strlen($fileContent), strlen($bodyContent));
        $this->assertEquals($fileMimeType, $response->getHeaderLine('Content-Type'));
        $this->assertContains('attachment', $response->getHeaderLine('Content-Disposition'));
        $this->assertEquals($fileContent, $bodyContent);

    }

    public function testFileContentUpdate()
    {
        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID);
        
        //create by portal admin
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

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
        $fileInfo = new FileInfo($_FILES['file']['tmp_name']);
        $fileSize = $fileInfo->getSize();
        $fileMimeType = $fileInfo->getMimetype();
        //end mock environment

        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_UPDATE_CONTENT, array('file_id' => $file->getId())),
            array(
                'content_modified_at' => '2015-09-07T14:06:51+02:00'
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($result->id);
        $this->assertEquals($fileSize, $result->size);
        $this->assertEquals($result->etag, 2);

        $fileService = $this->getContainer()->get('FileService');
        $downloader = $fileService->getFileDownloader($file->getId());
        $this->assertEquals(2, $downloader->getFileVersion()->getId());
    }

    public function testFileContentUpdateIncorrectHash()
    {
        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID);

        //create by portal admin
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        //mock environment
        $tmpName = tempnam(sys_get_temp_dir(), 'upload_');
        $settings = $this->getContainer()->get('settings');
        $srcFile = $settings['testsPath'] . '/data/files/' . self::TEST_FILE_NAME;
        copy($srcFile, $tmpName);
        $_FILES = array(
            'file' => array(
                'name' => self::TEST_FILE_NAME,
                'tmp_name' => $tmpName,
                'error' => UPLOAD_ERR_OK
            )
        );
        $fileInfo = new FileInfo($_FILES['file']['tmp_name']);
        $fileSize = $fileInfo->getSize();
        $fileMimeType = $fileInfo->getMimetype();
        //end mock environment

        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_UPDATE_CONTENT, array('file_id' => $file->getId())),
            array(
                'content_modified_at' => '2015-09-07T14:06:51+02:00',
                'hash' => hash_file('sha256' , $srcFile)."_incorrectHash"
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($result->id);
        $this->assertEquals($fileSize, $result->size);
        $this->assertEquals($result->etag, 1);
    }

    public function testFileContentUpdateWithGrantRightsButNotEdit()
    {
        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID);

        //create by portal admin
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);
        $grantTo    = $this->getUserService()->getUser(self::USER_ID2);
        $permission = $this->getPermissionService()->addPermission(
            $file,
            $grantTo,
            [PermissionType::FILE_GRANT_READ],
            self::USER_ID,
            $file->getWorkspace()->getPortal()
        );
        $this->getPermissionService()->clearCache();
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
        $fileInfo = new FileInfo($_FILES['file']['tmp_name']);
        //end mock environment

        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_UPDATE_CONTENT, array('file_id' => $file->getId())),
            array(
                'content_modified_at' => '2015-09-07T14:06:51+02:00'
            ),
            $headers
        );
        $this->assertEquals(403, $response->getStatusCode());
    }

    /*public function testFileContentUpdateChunkedByWorkspaceAdmin()
    {

        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, null, self::TEST_FILE_NAME2);

        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $settings = $this->getContainer()->get('settings');
        $srcFile = $settings['testsPath'] . '/data/files/' . self::TEST_FILE_NAME_BIG;
        $fileSize = filesize($srcFile);

        // Create file upload
        $response = $this->post(
            '/workspaces/1/files/uploads',
            array(
                'size' => $fileSize,
                'parent_id' => 7,
                'hash' => hash_file('sha256' , $srcFile)
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertNotEmpty($result->id);
        $this->assertNotEmpty($result->expires_at);
        $this->assertEquals(0, $result->offset);

        $uploadId = $result->id;

        //Starting to upload chunks
        $tmpName = tempnam(sys_get_temp_dir(), 'upload_');
        $fileContent = file_get_contents($srcFile);
        $chunkSize = 1048576;
        $offset = 0;
        $srcFp = fopen($srcFile, 'rb');

        do {
            fseek($srcFp, $offset);
            $chunk = fread($srcFp, $chunkSize);
            file_put_contents($tmpName, $chunk);

            $_FILES = array(
                'file' => array(
                    'name' => self::TEST_FILE_NAME_BIG,
                    'tmp_name' => $tmpName,
                    'error' => UPLOAD_ERR_OK,
                    'size' => strlen($chunk)
                )
            );

            $response = $this->post(
                sprintf('/files/uploads/%s', $uploadId),
                [
                    'offset' => $offset,
                ]
            );

            $result = json_decode($response->getBody());

            $this->assertEquals(200, $response->getStatusCode());
            $this->assertEquals(min($offset + $chunkSize, $fileSize), $result->offset);

            $offset = $result->offset;
        } while ($offset < $fileSize);

        $_FILES = [];

        //Finish upload


        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_UPDATE_CONTENT, array(
                'file_id' => $file->getId())),
            array(
                'upload_id' => $uploadId,
                'content_modified_at' => '2015-09-07T14:06:51+02:00'
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(202, $response->getStatusCode());
        $this->assertNotEmpty($result->id);
        $this->assertEquals($fileSize, $result->size);
        $this->assertEquals($result->etag, 2);

        $fileId = $result->id;

        while ($result->is_uploading
            && ($response->getStatusCode() == 200 || $response->getStatusCode() == 202)) {
            sleep(3);
            $this->getEntityManager()->clear();
            $response = $this->get(
                $this->urlFor(FilesRouteConfig::ROUTE_FILE_GET, array('file_id' => $fileId)),
                array(),
                $headers
            );
            $result = Json::decode($response->getBody());
        }
        //Test file download
        $response = $this->get(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_GET_CONTENT, array('file_id' => $fileId)),
            array(),
            $headers
        );
        //Follow redirect
        $response = $this->get(
            $response->getHeaderLine('Location'),
            array(),
            array()
        );

        $bodyContent = $response->getBody()->getContents();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($fileSize, $response->getHeaderLine('Content-Length'));
        $this->assertEquals(strlen($fileContent), $response->getHeaderLine('Content-Length'));
        $this->assertEquals($fileContent, substr($bodyContent, 0, $fileSize));
        $this->assertEquals($result->etag, 2);
    }

    public function testFileContentUpdateChunkedByWorkspaceAdminIncorrectHash()
    {

        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, null, self::TEST_FILE_NAME_BIG);
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $settings = $this->getContainer()->get('settings');
        $originalSrcFile = $settings['testsPath'] . '/data/files/' . self::TEST_FILE_NAME;
        $srcFile = $settings['testsPath'] . '/data/files/' . self::TEST_FILE_NAME_BIG;
        $originalFileSize = filesize($originalSrcFile);
        $fileSize = filesize($srcFile);
        $file->setContentModifiedAt(Carbon::parse(gmdate('Y-m-d H:i:s', filemtime($originalSrcFile))));
        // Create file upload
        $response = $this->post(
            '/workspaces/1/files/uploads',
            array(
                'size' => $fileSize,
                'parent_id' => 7,
                'hash' => hash_file('sha256' , $srcFile) . 'incorrect'
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertNotEmpty($result->id);
        $this->assertNotEmpty($result->expires_at);
        $this->assertEquals(0, $result->offset);

        $uploadId = $result->id;

        //Starting to upload chunks
        $tmpName = tempnam(sys_get_temp_dir(), 'upload_');
        $fileContent = file_get_contents($srcFile);
        $chunkSize = 1048576;
        $offset = 0;
        $srcFp = fopen($srcFile, 'rb');

        do {
            fseek($srcFp, $offset);
            $chunk = fread($srcFp, $chunkSize);
            file_put_contents($tmpName, $chunk);

            $_FILES = array(
                'file' => array(
                    'name' => self::TEST_FILE_NAME_BIG,
                    'tmp_name' => $tmpName,
                    'error' => UPLOAD_ERR_OK,
                    'size' => strlen($chunk)
                )
            );

            $response = $this->post(
                sprintf('/files/uploads/%s', $uploadId),
                [
                    'offset' => $offset,
                ]
            );

            $result = json_decode($response->getBody());

            $this->assertEquals(200, $response->getStatusCode());
            $this->assertEquals(min($offset + $chunkSize, $fileSize), $result->offset);

            $offset = $result->offset;
        } while ($offset < $fileSize);

        $_FILES = [];

        //Finish upload


        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_UPDATE_CONTENT, array(
                'file_id' => $file->getId())),
            array(
                'upload_id' => $uploadId,
                'content_modified_at' => '2015-09-07T14:06:51+02:00'
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(202, $response->getStatusCode());
        $this->assertNotEmpty($result->id);
        $this->assertEquals($fileSize, $result->size);
        $this->assertEquals($result->etag, 2);

        $fileId = $result->id;

        while ($result->is_uploading
            && ($response->getStatusCode() == 200 || $response->getStatusCode() == 202)) {
            sleep(3);
            $this->getEntityManager()->clear();
            $response = $this->get(
                $this->urlFor(FilesRouteConfig::ROUTE_FILE_GET, array('file_id' => $fileId)),
                array(),
                $headers
            );
            $result = Json::decode($response->getBody());
        }
        $this->assertEquals(200, $response->getStatusCode());

        //Test file download
        $response = $this->get(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_GET_CONTENT, array('file_id' => $fileId)),
            array(),
            $headers
        );
        //Follow redirect
        $response = $this->get(
            $response->getHeaderLine('Location'),
            array(),
            array()
        );

        $bodyContent = $response->getBody()->getContents();
        $originalFileContent = file_get_contents($originalSrcFile);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($originalFileSize, $response->getHeaderLine('Content-Length'));
        $this->assertEquals(strlen($originalFileContent), $response->getHeaderLine('Content-Length'));
        $this->assertEquals($originalFileContent, substr($bodyContent, 0, $originalFileSize));
        $this->assertEquals($result->etag, 1);
    }*/

    public function testFileGetVersions()
    {
        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID);

        //create by portal admin
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

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
            array(),
            $headers
        );


        $response = $this->get(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_VERSIONS_LIST, array('file_id' => $file->getId())),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(2, $result->entries);
    }

    public function testFileUpdateByPortalAdmin()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $newName = 'Updated name';

        $response = $this->put(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_UPDATE, array('file_id' => 1)),
            array(
                'name' => $newName
            ),
            $headers
        );
        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($newName, $result->name);
    }

    public function testRestoreFileByPortalAdmin()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID);
        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, $folder);

        $fileService = $this->getContainer()->get('FileService');
        $fileService->deleteFile($file, self::USER_ID);

        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_RESTORE, array('file_id' => $file->getId())),
            array(),
            $headers
        );
        $result = Json::decode($response->getBody());

        $this->assertEquals(201, $response->getStatusCode());
    }

    public function testDeleteDeletedFile() {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID);
        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, $folder);

        $fileService = $this->getContainer()->get('FileService');
        $fileService->deleteFile($file, self::USER_ID);

        $response = $this->delete(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_DELETE, array('file_id' => $file->getId())),
            array(),
            $headers
        );

        $this->assertEquals(204, $response->getStatusCode());
    }

    public function testRestoreFileNameConflict()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID);
        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, $folder);

        $fileService = $this->getContainer()->get('FileService');
        $fileService->deleteFile($file, self::USER_ID);

        $file2 = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, $folder, $file->getName());

        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_RESTORE, array('file_id' => $file->getId())),
            array(),
            $headers
        );

        $this->assertEquals(409, $response->getStatusCode());
    }

    public function testRestoreFileParentConflict()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID);
        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, $folder);

        $fileService = $this->getContainer()->get('FileService');
        $fileService->deleteFile($file, self::USER_ID);

        $folderService = $this->getContainer()->get('FolderService');
        $folderService->deleteFolder($folder, self::USER_ID);

        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_RESTORE, array('file_id' => $file->getId())),
            array(),
            $headers
        );

        $this->assertEquals(409, $response->getStatusCode());
    }

    public function testRestoreFileWhichIsNotTrashed()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID);
        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, $folder);

        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_RESTORE, array('file_id' => $file->getId())),
            array(),
            $headers
        );

        $this->assertEquals(405, $response->getStatusCode());
    }

    public function testFileUpdateWhichDoesntExists()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);
        $newName = 'Updated name';

        //update non-existing
        $response = $this->put(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_UPDATE, array('file_id' => 101)),
            array(
                'name' => $newName
            ),
            $headers
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testUpdateDeletedFile() {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID);
        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, $folder);

        $fileService = $this->getContainer()->get('FileService');
        $fileService->deleteFile($file, self::USER_ID);

        $newName = 'Updated name';

        //update deleted file
        $response = $this->put(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_UPDATE, array('file_id' => $file->getId())),
            array(
                'name' => $newName
            ),
            $headers
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testUpdateContentDeletedFile()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID);
        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, $folder);

        $fileService = $this->getContainer()->get('FileService');
        $fileService->deleteFile($file, self::USER_ID);

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
        $fileInfo = new FileInfo($_FILES['file']['tmp_name']);
        $fileSize = $fileInfo->getSize();
        $fileMimeType = $fileInfo->getMimetype();
        //end mock environment

        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_UPDATE_CONTENT, array('file_id' => $file->getId())),
            array(
                'content_modified_at' => '2015-09-07T14:06:51+02:00'
            ),
            $headers
        );
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testUpdateContentFolder()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID);


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
        $fileInfo = new FileInfo($_FILES['file']['tmp_name']);
        $fileSize = $fileInfo->getSize();
        $fileMimeType = $fileInfo->getMimetype();
        //end mock environment

        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_UPDATE_CONTENT, array('file_id' => $folder->getId())),
            array(
                'content_modified_at' => '2015-09-07T14:06:51+02:00'
            ),
            $headers
        );
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testFileUpdateByWorkspaceAdminWithoutPermissions()
    {
        //update by workspace admin
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);
        $newName = 'Updated name';

        $response = $this->put(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_UPDATE, array('file_id' => 5)),
            array(
                'name' => $newName
            ),
            $headers
        );
        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testFileUpdateByOwner()
    {
        //update by owner
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);
        $newName = 'Updated name';

        $response = $this->put(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_UPDATE, array('file_id' => 4)),
            array(
                'name' => $newName
            ),
            $headers
        );
        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($newName, $result->name);
    }

    public function testGetFilePermission()
    {


      //Test case purpose - return file permission
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->get(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_GET_PERMISSION, array(
                'file_id' => 6
            )),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals('edit', $result->actions);
    }

    public function testFileUpdateByUserWithReadPermissions()
    {
        //update by workspace user with only read permissions
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);
        $newName = 'Updated name';

        $response = $this->put(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_UPDATE, array('file_id' => 3)),
            array(
                'name' => $newName
            ),
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testFileUpdateByUserWithEditPermissions()
    {
        //update by workspace user with edit permissions
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);
        $newName = 'Updated name';

        $response = $this->put(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_UPDATE, array('file_id' => 6)),
            array(
                'name' => $newName
            ),
            $headers
        );
        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($newName, $result->name);
    }

    public function testFileTrash()
    {
        //delete by portal admin
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->delete(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_DELETE, array('file_id' => 1)),
            array(),
            $headers
        );

        $file = $this->getFileService()->getFile(1);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
        $this->assertTrue($file->getIsTrashed());
        $this->assertFalse($file->getIsDeleted());
    }

    public function testFileDelete()
    {
        //delete by portal admin
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->delete(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_DELETE_PERMANENTLY, array('file_id' => 1)),
            array(),
            $headers
        );

        $file = $this->getFileService()->getFile(1);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
        $this->assertTrue($file->getIsDeleted());
    }

    public function testFileDeleteByNonMemberInWorkspace()
    {
        //delete by user that is not member of workspace but has edit rights for file
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);
        $user2 = $this->getEntityManager()->find(User::getEntityName(), self::USER_ID2);
        $file = $this->getFileService()->getFile(8);

        $permission = $this->getPermissionService()->addPermission(
            $file->getWorkspace(),
            $user2,
            [PermissionType::WORKSPACE_ACCESS],
            self::USER_ID,
            $file->getWorkspace()->getPortal()
        );
        $this->getPermissionService()->deletePermission(
            $permission,
            self::USER_ID
        );
        $this->getPermissionService()->clearCache();
        $response = $this->delete(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_DELETE, array('file_id' => 8)),
            array(),
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testFileDeleteNonExisting()
    {
        //delete non-existing
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->delete(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_DELETE, array('file_id' => 101)),
            array(),
            $headers
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testFileDeleteByWorkspaceAdminWithoutPermissions()
    {
        //delete by workspace admin
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->delete(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_DELETE, array('file_id' => 5)),
            array(),
            $headers
        );

        $this->assertEquals(204, $response->getStatusCode());
    }

    public function testFileDeleteByOwner()
    {
        //delete by owner
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->delete(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_DELETE, array('file_id' => 4)),
            array(),
            $headers
        );

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testFileDeleteWithReadPermissions()
    {
        //delete by user with only read permissions
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->delete(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_DELETE, array('file_id' => 3)),
            array(),
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testFileDeleteWithEditPermissions()
    {
        //delete by user with edit permissions
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->delete(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_DELETE, array('file_id' => 4)),
            array(),
            $headers
        );

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testFileEncryptionDecryption()
    {
        $settings = $this->getContainer()->get('settings');

        $fileContent = FileHelper::getTestFileContent($this->getContainer());

        $storage = StorageFactory::createStorage($settings['fileStorage']);

        $encryptor = new Encryptor();
        $encryptor->setKey($this->getContainer()->get('settings')['fileEncryptionKey']);
        $encryptor->setIv($encryptor->generateIv());
        $encryptor->setStreamFilterForStorage($storage, Encryptor::ENCRYPT_STREAM_FILTER);

        $fp = $storage->fopen('test.txt', 'wb');
        fwrite($fp, $fileContent, strlen($fileContent));
        fclose($fp);

        $encryptor->setStreamFilterForStorage($storage, Encryptor::DECRYPT_STREAM_FILTER);
        $fp = $storage->fopen('test.txt', 'rb');
        $content = '';
        while (!feof($fp)) {
            $content .= fread($fp, $settings['responseChunkSize']);
        }
        fclose($fp);

        $this->assertEquals($fileContent, $content);
    }

    public function testFileDownloadAsAttachmentWithoutRange()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $file = FileHelper::createFile2($this->getContainer(), 1, self::USER_ID);
        $fileContent = FileHelper::getTestFile2Content($this->getContainer());

        //this request generates temporary download URL and returns 302 status code and
        //header "Location" with proper location of temporary download link
        $response = $this->get(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_GET_CONTENT, array('file_id' => $file->getId())),
            array('open_style' => 'attachment'),
            $headers
        );

        $headers = $response->getHeaders();

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
        $this->assertArrayHasKey('Location', $headers);

        $location = $response->getHeaderLine('Location');

        $response = $this->get(
            $location,
            array(),
            array()
        );

        ob_start();
        $this->getApp()->respond($response);
        $bodyContent = ob_get_clean();

        $headers = $response->getHeaders();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('Content-Disposition', $headers);
        $this->assertArrayHasKey('Content-Length', $headers);
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertEquals($file->getSize(), $response->getHeaderLine('Content-Length'));
        $this->assertEquals(strlen($fileContent), strlen($bodyContent));
        $this->assertEquals($file->getMimeType(), $response->getHeaderLine('Content-Type'));
        $this->assertContains('attachment', $response->getHeaderLine('Content-Disposition'));
        $this->assertEquals($fileContent, $bodyContent);
    }

    public function testFileDownloadVersionAsAttachmentWithoutRange()
    {

        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $file = FileHelper::createFile2($this->getContainer(), 1, self::USER_ID);
        $fileContent = FileHelper::getTestFile2Content($this->getContainer());

        //this request generates temporary download URL and returns 302 status code and
        //header "Location" with proper location of temporary download link
        $response = $this->get(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_GET_CONTENT,
                array('file_id' => $file->getId()),
                array('open_style' => 'attachment',
                    'version' => $file->getEtag())
            ),
            array(),
            $headers
        );

        $headers = $response->getHeaders();

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
        $this->assertArrayHasKey('Location', $headers);

        $location = $response->getHeaderLine('Location');

        $response = $this->get(
            $location,
            array(),
            array()
        );

        $bodyContent = $response->getBody();

        $headers = $response->getHeaders();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('Content-Disposition', $headers);
        $this->assertArrayHasKey('Content-Length', $headers);
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertEquals($file->getSize(), $response->getHeaderLine('Content-Length'));
        $this->assertEquals($file->getMimeType(), $response->getHeaderLine('Content-Type'));
        $this->assertContains('attachment', $response->getHeaderLine('Content-Disposition'));
        $this->assertEquals($fileContent, $bodyContent->read($response->getHeaderLine('Content-Length')));
    }

    public function testFileDownloadOldVersion()
    {
        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID);
        $version = $file->getVersion()->getId();
        $fileContent = FileHelper::getTestFileContent($this->getContainer());

        //create by portal admin
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

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
            array(),
            $headers
        );
        $response = $this->get(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_GET_CONTENT,
                array('file_id' => $file->getId()),
                array('open_style' => 'attachment',
                    'version' => $version)
            ),
            array(),
            $headers
        );

        $headers = $response->getHeaders();

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
        $this->assertArrayHasKey('Location', $headers);

        $location = $response->getHeaderLine('Location');

        $response = $this->get(
            $location,
            array(),
            array()
        );

        $bodyContent = $response->getBody();

        $headers = $response->getHeaders();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('Content-Disposition', $headers);
        $this->assertArrayHasKey('Content-Length', $headers);
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertEquals($file->getSize(), $response->getHeaderLine('Content-Length'));
        $this->assertEquals($file->getMimeType(), $response->getHeaderLine('Content-Type'));
        $this->assertContains('attachment', $response->getHeaderLine('Content-Disposition'));
        $this->assertEquals($fileContent, $bodyContent->read($response->getHeaderLine('Content-Length')));

    }

    public function testFileDownloadDeletedFile()
    {
        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID);
        $version = $file->getVersion()->getId();
        $fileContent = FileHelper::getTestFileContent($this->getContainer());

        //create by portal admin
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $fileService = $this->getContainer()->get('FileService');
        $fileService->deleteFile($file, self::USER_ID);

        $response = $this->get(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_GET_CONTENT,
                array('file_id' => $file->getId()),
                array('open_style' => 'attachment')
            ),
            array(),
            $headers
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testUpdateFileVersionComment()
    {
        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID);
        $version = $file->getVersion()->getId();
        $fileContent = FileHelper::getTestFileContent($this->getContainer());

        //create by portal admin
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

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
            array(),
            $headers
        );
        $response = $this->put(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_VERSION_UPDATE,
                array('file_version_id' => $version)
            ),
            array('comment' => 'New comment for version'),
            $headers
        );

        $result = json_decode((string)$response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($result->comment, 'New comment for version');

    }

    public function testFileDownloadServerOptionsGet()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID);
        $fileContent = FileHelper::getTestFileContent($this->getContainer());

        $response = $this->options(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_GET_CONTENT, array('file_id' => $file->getId())),
            array('open_style' => 'attachment'),
            $headers
        );

        $result = json_decode((string)$response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty('url', $result);

        $location = $result->url;

        $response = $this->get(
            $location,
            array(),
            array()
        );

        $bodyContent = $response->getBody()->getContents();

        $headers = $response->getHeaders();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('Content-Disposition', $headers);
        $this->assertArrayHasKey('Content-Length', $headers);
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertEquals($file->getSize(), $response->getHeaderLine('Content-Length'));
        $this->assertEquals(strlen($fileContent), strlen((string)$response->getBody()));
        $this->assertEquals($file->getMimeType(), $response->getHeaderLine('Content-Type'));
        $this->assertContains('attachment', $response->getHeaderLine('Content-Disposition'));
        $this->assertEquals($fileContent, $bodyContent);
        $this->assertEquals($file->getSize(), strlen((string)$response->getBody()));
    }

    /*public function testDownloadZipFilesOnly() {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $workspaceId = 1;
        $folder = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID);
        $subFolder = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID, $folder);
        $file1 = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, $folder);
        $file2 = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, $folder);
        $file3 = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, $subFolder);
        $file4 = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, $subFolder);
        $files = array($file1->getId(), $file2->getId());
        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_GET_ZIP_CONTENT,
                array(),
                array('open_style' => 'attachment')
            ),
            array('files' => $files),
            $headers
        );
        $result = json_decode((string)$response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty((string)$result->Location);

        $response = $this->get(
            $result->Location,
            array(),
            array()
        );

        $bodyContent = $response->getBody()->getContents();
        $headers = $response->getHeaders();
    }

    public function testDownloadZipFile() {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $workspaceId = 1;
        $folder = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID, null, 'folder1');
        $subFolder = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID, $folder, 'subfolder1');
        $subFolder2 = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID, $folder, 'subfolder2');
        $file1 = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, $folder, 'folder1_file1');
        $file2 = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, $folder, 'folder1_file2');
        $file3 = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, $subFolder, 'subfolder1_file1');
        $file4 = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, $subFolder, 'subfolder1_file2');
        $files = array($folder->getId());
        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_GET_ZIP_CONTENT,
                array(),
                array('open_style' => 'attachment')
            ),
            array('files' => $files),
            $headers
        );
        $result = json_decode((string)$response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty((string)$result->Location);

        $response = $this->get(
            $result->Location,
            array(),
            array()
        );

        $bodyContent = $response->getBody()->getContents();
        $headers = $response->getHeaders();
    }*/
    public function testFileMove()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder1 = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID, null);
        $folder2 = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID, null);

        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, $folder1);

        $response = $this->put(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_UPDATE, array('file_id' => $file->getId())),
            array('parent' => array('id' => $folder2->getId()), 'name' => $file->getName()),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($folder2->getId(), $result->parent->id);

        //fetching updated file

        $response = $this->get(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_GET, array('file_id' => $file->getId())),
            array(),
            $headers
        );
        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($folder2->getId(), $result->parent->id);

        //fetching children of the folder where file was moved to

        $response = $this->get(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_CHILDREN_GET, array('folder_id' => $folder2->getId())),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertCount(1, $result->entries);
    }

    public function testFileMoveInheritLabels()
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
        $response = $this->put(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_UPDATE, array('file_id' => $file->getId())),
            array('parent' => array('id' => $folder2->getId()), 'name' => $file->getName()),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($folder2->getId(), $result->parent->id);

        //fetching updated file

        $response = $this->get(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_GET, array('file_id' => $file->getId())),
            array(),
            $headers
        );
        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($folder2->getId(), $result->parent->id);

        //fetching children of the folder where file was moved to

        $response = $this->get(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_CHILDREN_GET, array('folder_id' => $folder2->getId())),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertCount(1, $result->entries);
        $newfile = $this->getFileService()->getFile($result->entries[0]->id);
        $this->assertEquals(2,count($newfile->getMetaFieldsValues()));
    }

    public function testFileMoveFileExists()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder1 = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID, null);
        $folder2 = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID, null);

        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, $folder1);

        // Create a copy with same name at move destination folder
        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_COPY, array('file_id' => $file->getId())),
            array(
                'parent' => array('id' => $folder2->getId()),
                'name' => $file->getName()
            ),
            $headers
        );

        $response = $this->put(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_UPDATE, array('file_id' => $file->getId())),
            array('parent' => array('id' => $folder2->getId()), 'name' => $file->getName()),
            $headers
        );

        $this->assertEquals(409, $response->getStatusCode());

        //fetching updated file

        $response = $this->get(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_GET, array('file_id' => $file->getId())),
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

    public function testFileMoveAndRenameFile()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder1 = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID, null);
        $folder2 = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID, null);

        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, $folder1);

        // Create a copy with same name at move destination folder
        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_COPY, array('file_id' => $file->getId())),
            array(
                'parent' => array('id' => $folder2->getId()),
                'name' => $file->getName()
            ),
            $headers
        );

        $response = $this->put(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_UPDATE, array('file_id' => $file->getId())),
            array('parent' => array('id' => $folder2->getId()), 'name' => 'New name for '.$file->getName()),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());

        //fetching updated file

        $response = $this->get(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_GET, array('file_id' => $file->getId())),
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

        $this->assertCount(2, $result->entries);
    }

    public function testFileMoveAndRenameFileInSameOperation()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder1 = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID, null);
        $folder2 = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID, null);

        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, $folder1);


        $response = $this->put(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_UPDATE, array('file_id' => $file->getId())),
            array('parent' => array('id' => $folder2->getId()), 'name' => 'New name for '.$file->getName()),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());

        //fetching updated file

        $response = $this->get(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_GET, array('file_id' => $file->getId())),
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

    //TEMPORARY DOWNLOAD LINKS

    public function testFileMoveToRootFolder()
    {
        $workspaceId = 1;
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder1 = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID, null);
        $file = FileHelper::createFile($this->getContainer(), $workspaceId, self::USER_ID, $folder1);

        $response = $this->put(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_UPDATE, array('file_id' => $file->getId())),
            array('parent' => array('id' => 0)),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNull($result->parent);

        //fetching updated file

        $response = $this->get(
            $this->urlFor(FoldersRouteConfig::ROUTE_ROOT_FOLDER_CHILDREN_GET, array('workspace_id' => $workspaceId)),
            array(),
            $headers
        );
        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());

        $foundInRootDir = false;
        foreach ($result->entries as $entry) {
            if ($entry->id == $file->getId()) {
                $foundInRootDir = true;
                break;
            }
        }
        $this->assertTrue($foundInRootDir);
    }

    public function testFileMoveToRootFolderWithReadAccess()
    {
        $workspaceId = 1;
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder1 = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID, null);
        $grantTo    = $this->getUserService()->getUser(self::USER_ID2);
        $permission = $this->getPermissionService()->addPermission(
            $folder1,
            $grantTo,
            [PermissionType::FILE_GRANT_EDIT],
            self::USER_ID,
            $folder1->getWorkspace()->getPortal()
        );
        $this->getPermissionService()->clearCache();
        $file = FileHelper::createFile($this->getContainer(), $workspaceId, self::USER_ID2, $folder1);

        $response = $this->put(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_UPDATE, array('file_id' => $file->getId())),
            array('parent' => array('id' => 0)),
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());


    }

    public function testFileMoveToDifferentWorkspace()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder1 = FileHelper::createFolder($this->getContainer(), self::WORKSPACE_ID, self::USER_ID, null);
        $folder2 = FileHelper::createFolder($this->getContainer(), self::WORKSPACE_ID2, self::USER_ID, null);

        $file = FileHelper::createFile($this->getContainer(), self::WORKSPACE_ID, self::USER_ID, $folder1);

        // Move folder to destination folder in different workspace

        $response = $this->put(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_UPDATE, array('file_id' => $file->getId())),
            array('parent' => array('id' => $folder2->getId()), 'name' => $file->getName()),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($folder2->getWorkspace()->getId(), $result->workspace->id);

    }

    public function testFileCopy()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder1 = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID, null);
        $folder2 = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID, null);

        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, $folder1);

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

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($folder2->getId(), $result->parent->id);
        $this->assertEquals($newName, $result->name);
    }

    public function testFileCopyWithLabels()
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

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($folder2->getId(), $result->parent->id);
        $this->assertEquals($newName, $result->name);
        $file = $this->getFileService()->getFile($file->getId());
        $newfile = $this->getFileService()->getFile($result->id);
        $this->assertEquals(1,count($file->getMetaFieldsValues()));
        $this->assertEquals(2,count($newfile->getMetaFieldsValues()));
    }

    public function testFileCopyToRoot()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder1 = FileHelper::createFolder($this->getContainer(), 3, self::USER_ID, null);

        $file = FileHelper::createFile($this->getContainer(), 3, self::USER_ID, $folder1);

        $newName = $file->getName() . ' (copy)';
        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_COPY, array('file_id' => $file->getId())),
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

    public function testFileCopyDeletedFile()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder1 = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID, null);
        $folder2 = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID, null);

        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, $folder1);
        $fileService = $this->getContainer()->get('FileService');
        $fileService->deleteFile($file, self::USER_ID);

        $newName = $file->getName() . ' (copy)';
        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_COPY, array('file_id' => $file->getId())),
            array(
                'parent' => array('id' => $folder2->getId()),
                'name' => $newName
            ),
            $headers
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testFileNameConflict()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $file1 = FileHelper::createFile($this->getContainer(), 1, self::USER_ID);
        $fileName = $file1->getName();

        //mock environment
        $fileInfo = $this->mockFileUploadEnvironment();
        //end mock environment

        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_ADD, array('workspace_id' => 1)),
            array(
                'name' => $fileName
            ),
            $headers
        );

        $this->assertEquals(409, $response->getStatusCode());
    }

    public function testFileNameNoConflict()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $fileName = "kalla.txt";
        $file1 = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, null, $fileName);


        //mock environment
        $fileInfo = $this->mockFileUploadEnvironment();
        //end mock environment

        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_ADD, array('workspace_id' => 1)),
            array(
                'name' => "källa.txt"
            ),
            $headers
        );

        $this->assertEquals(201, $response->getStatusCode());
    }

    public function testFileNameCapitalConflict()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $fileName = "kalla.txt";
        $file1 = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, null, $fileName);


        //mock environment
        $fileInfo = $this->mockFileUploadEnvironment();
        //end mock environment

        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_ADD, array('workspace_id' => 1)),
            array(
                'name' => "Kalla.txt"
            ),
            $headers
        );

        $this->assertEquals(409, $response->getStatusCode());
    }

    public function testFileNameNoConflictWithDeleted()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $file1 = FileHelper::createFile($this->getContainer(), 1, self::USER_ID);
        $fileName = $file1->getName();

        $fileService = $this->getContainer()->get('FileService');
        $fileService->deleteFile($file1, self::USER_ID);

        //mock environment
        $fileInfo = $this->mockFileUploadEnvironment();
        //end mock environment

        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_ADD, array('workspace_id' => 1)),
            array(
                'name' => $fileName
            ),
            $headers
        );

        $this->assertEquals(201, $response->getStatusCode());
    }

    public function testFileChunkedNameConflict()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $file1 = FileHelper::createFile($this->getContainer(), 1, self::USER_ID);
        $fileName = $file1->getName();

        // Create file upload
        $response = $this->post(
            '/workspaces/1/files/uploads',
            array(
                'size' => 100000,
                'name' => $fileName,
                'hash' => '499980ad3270befe6310fa9cabc07123713ee2bbcb1054f982b9ca26c534ccfd'
            ),
            $headers
        );

        $this->assertEquals(409, $response->getStatusCode());
    }

    public function testFileDeleteWithIfMatch()
    {
        //delete by user with edit permissions
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);
        $headers['If-Match'] = 123;

        $response = $this->delete(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_DELETE, array('file_id' => 4)),
            array(),
            $headers
        );

        $this->assertEquals(412, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testGetFilePath()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder1 = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID, null);
        $folder2 = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID, $folder1);
        $file1 = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, $folder2);

        $response = $this->get(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_GET_PATH, array('file_id' => $file1->getId())),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $path = $folder2->getWorkspace()->getName()."/".$folder1->getName()."/".$folder2->getName()."/".$file1->getName();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($path, $result->path);
    }

    private function mockFileUploadEnvironment()
    {
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
        return new FileInfo($tmpName);
    }

    /**
     * @return FileService
     */
    private function getFileService()
    {
        return $this->getContainer()->get('FileService');
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
    //    public function testFileDownloadAsAttachmentWithRange()
//    {
//        //todo
//    }
//
//    public function testFileDownloadAsStream()
//    {
//        //todo
//    }
}
