<?php

namespace iCoordinator;

use Carbon\Carbon;
use Datetime;
use iCoordinator\Config\Route\FilesRouteConfig;
use iCoordinator\Config\Route\FoldersRouteConfig;
use iCoordinator\Entity\Acl\AclResource\AclPortalResource;
use iCoordinator\Entity\Acl\AclResource\AclWorkspaceResource;
use iCoordinator\Entity\Acl\AclRole\AclUserRole;
use iCoordinator\Entity\File;
use iCoordinator\Entity\Portal;
use iCoordinator\Entity\User;
use iCoordinator\Entity\Workspace;
use iCoordinator\Permissions\BitMask;
use iCoordinator\Permissions\PermissionType;
use iCoordinator\PHPUnit\DbUnit\DataSet\ArrayDataSet;
use iCoordinator\PHPUnit\Helper\FileHelper;
use iCoordinator\PHPUnit\TestCase;
use Laminas\Json\Json;


class LocksTest extends TestCase
{
    const USER_ID = 1;
    const USER_ID2 = 2;
    const USERNAME = 'test@user.com';
    const USERNAME2 = 'test2@user.com';
    const PASSWORD = 'password';
    const PUBLIC_CLIENT_ID = 'public_test';
    const TEST_FILE_NAME = 'Document1.pdf';
    const PORTAL_ID = 1;

    public function setUp(): void
    {
        parent::setUp();

        FileHelper::initializeFileMocks($this);
    }

    public function testFileLockUnlock()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID);

        $response = $this->put(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_UPDATE, array('file_id' => $file->getId())),
            array(
                'lock' => array(
                    'expires_at' => Carbon::tomorrow()->format(DateTime::ISO8601)
                )
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(1, $result->etag);
        $this->assertNotEmpty($result->lock);
        $this->assertNotEmpty($result->lock->id);


        $response = $this->put(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_UPDATE, array('file_id' => $file->getId())),
            array(
                'lock' => null
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEmpty($result->lock);

        $response = $this->get(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_GET, array('file_id' => $file->getId())),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEmpty($result->lock);
    }

    public function testFileLockUnlockByAdmin()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID);

        $response = $this->put(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_UPDATE, array('file_id' => $file->getId())),
            array(
                'lock' => array(
                    'expires_at' => Carbon::tomorrow()->format(DateTime::ISO8601)
                )
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(1, $result->etag);
        $this->assertNotEmpty($result->lock);
        $this->assertNotEmpty($result->lock->id);

        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->put(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_UPDATE, array('file_id' => $file->getId())),
            array(
                'lock' => null
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEmpty($result->lock);

        $response = $this->get(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_GET, array('file_id' => $file->getId())),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEmpty($result->lock);
    }

    public function testAccessLockedFile()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID);

        $response = $this->put(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_UPDATE, array('file_id' => $file->getId())),
            array(
                'lock' => array(
                    'expires_at' => Carbon::now()->addDay(1)->format(DateTime::ISO8601)
                )
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($result->lock);
        $this->assertNotEmpty($result->lock->id);

        $user = $this->getEntityManager()->find(User::getEntityName(), self::USER_ID);

        $permissionService = $this->getContainer()->get('PermissionService');
        $permissionService->addPermission(
            $file,
            $user,
            [PermissionType::FILE_EDIT],
            self::USER_ID,
            $file->getWorkspace()->getPortal()
        );

        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->put(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_UPDATE, array('file_id' => $file->getId())),
            array(
                'name' => 'New name'
            ),
            $headers
        );

        $this->assertEquals('Test user 1', $response->getReasonPhrase());
        $this->assertEquals(423, $response->getStatusCode());
    }

    public function testFolderLockUnlock()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID);

        $response = $this->put(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_UPDATE, array('folder_id' => $folder->getId())),
            array(
                'lock' => array(
                    'expires_at' => Carbon::tomorrow()->format(DateTime::ISO8601)
                )
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(1, $result->etag);
        $this->assertNotEmpty($result->lock);
        $this->assertNotEmpty($result->lock->id);

        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);
        $response = $this->put(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_UPDATE, array('folder_id' => $folder->getId())),
            array(
                'lock' => null
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEmpty($result->lock);

        $response = $this->get(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_GET, array('folder_id' => $folder->getId())),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEmpty($result->lock);
    }

    public function testFolderLockUnlockByAdmin()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID);

        $response = $this->put(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_UPDATE, array('folder_id' => $folder->getId())),
            array(
                'lock' => array(
                    'expires_at' => Carbon::tomorrow()->format(DateTime::ISO8601)
                )
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(1, $result->etag);
        $this->assertNotEmpty($result->lock);
        $this->assertNotEmpty($result->lock->id);

        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);
        $response = $this->put(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_UPDATE, array('folder_id' => $folder->getId())),
            array(
                'lock' => null
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEmpty($result->lock);

        $response = $this->get(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_GET, array('folder_id' => $folder->getId())),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEmpty($result->lock);
    }

    public function testAccessLockedFolder()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID);

        $response = $this->put(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_UPDATE, array('folder_id' => $folder->getId())),
            array(
                'lock' => array(
                    'expires_at' => Carbon::now()->addDay(1)->format(DateTime::ISO8601)
                )
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($result->lock);
        $this->assertNotEmpty($result->lock->id);

        $user = $this->getEntityManager()->find(User::getEntityName(), self::USER_ID);

        $permissionService = $this->getContainer()->get('PermissionService');
        $permissionService->addPermission(
            $folder,
            $user,
            [PermissionType::FILE_EDIT],
            self::USER_ID,
            $folder->getWorkspace()->getPortal()
        );

        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->put(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_UPDATE, array('folder_id' => $folder->getId())),
            array(
                'name' => 'New name'
            ),
            $headers
        );

        $this->assertEquals('Test user 1', $response->getReasonPhrase());
        $this->assertEquals(423, $response->getStatusCode());
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
                )
            ),
            'oauth_access_tokens' => array(),
            'users' => array(
                array(
                    'id' => self::USER_ID,
                    'email' => self::USERNAME,
                    'name' => 'Test user 1',
                    'password' => password_hash(self::PASSWORD, PASSWORD_DEFAULT),
                    'email_confirmed' => 1
                ),
                array(
                    'id' => self::USER_ID2,
                    'email' => self::USERNAME2,
                    'name' => 'Test user 2',
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
                    'acl_role_id' => self::USER_ID2,
                    'acl_resource_id' => 2,
                    'bit_mask' => $workspaceBitMask->getBitMask(PermissionType::WORKSPACE_ADMIN),
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'acl_role_id' => self::USER_ID,
                    'acl_resource_id' => 2,
                    'bit_mask' => $workspaceBitMask->getBitMask(PermissionType::WORKSPACE_ADMIN),
                    'portal_id' => self::PORTAL_ID
                )
            ),
            'files' => array(),
            'shared_links' => array(),
            'locks' => array()
        ));
    }
}
