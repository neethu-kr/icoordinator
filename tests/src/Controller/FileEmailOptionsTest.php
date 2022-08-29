<?php

namespace iCoordinator;

use iCoordinator\Config\Route\FilesRouteConfig;
use iCoordinator\Config\Route\FoldersRouteConfig;
use iCoordinator\Doctrine\Proxy\__CG__\iCoordinator\Entity\User;
use iCoordinator\Entity\Acl\AclResource\AclPortalResource;
use iCoordinator\Entity\Acl\AclResource\AclWorkspaceResource;
use iCoordinator\Entity\Acl\AclRole\AclUserRole;
use iCoordinator\Entity\File;
use iCoordinator\Entity\Portal;
use iCoordinator\Entity\Workspace;
use iCoordinator\Permissions\BitMask;
use iCoordinator\Permissions\PermissionType;
use iCoordinator\PHPUnit\DbUnit\DataSet\ArrayDataSet;
use iCoordinator\PHPUnit\Helper\FileHelper;
use iCoordinator\PHPUnit\TestCase;
use iCoordinator\Service\EventNotificationService;
use Laminas\Json\Json;


class FileEmailOptionsTest extends TestCase
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

    public function testGetNonExistingFileEmailOptions()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID);

        $response = $this->get(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_EMAIL_OPTIONS_GET, array('file_id' => $file->getId())),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNull($result);

    }

    public function testGetNonExistingFolderEmailOptions()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID);

        $response = $this->get(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_EMAIL_OPTIONS_GET, array('folder_id' => $folder->getId())),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNull($result);

    }

    public function testSetAndGetFileEmailOptions()
    {
        //create file and set email options
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID);
        $user = $this->getEntityManager()->find(User::getEntityName(), self::USER_ID);

        $response = $this->put(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_EMAIL_OPTIONS_SET, array('file_id' => $file->getId())),
            array(
                'upload_notification' => true
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($result->upload_notification);
        $this->assertFalse($result->download_notification);
        $this->assertFalse($result->delete_notification);

        //get email options

        $response = $this->get(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_EMAIL_OPTIONS_GET, array('file_id' => $file->getId())),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($result->upload_notification);
        $this->assertFalse($result->download_notification);
        $this->assertFalse($result->delete_notification);

        //get email options by other user

        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->get(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_EMAIL_OPTIONS_GET, array('file_id' => $file->getId())),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNull($result);
    }

    public function testSetAndDeleteFileEmailOptions()
    {
        //create file and set email options
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID);
        $user = $this->getEntityManager()->find(User::getEntityName(), self::USER_ID);

        $response = $this->put(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_EMAIL_OPTIONS_SET, array('file_id' => $file->getId())),
            array(
                'upload_notification' => true
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($result->upload_notification);
        $this->assertFalse($result->download_notification);
        $this->assertFalse($result->delete_notification);

        //unset email options

        $response = $this->put(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_EMAIL_OPTIONS_SET, array('file_id' => $file->getId())),
            array(
                'upload_notification' => false
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse($result->upload_notification);
        $this->assertFalse($result->download_notification);
        $this->assertFalse($result->delete_notification);

    }

    public function testFolderSetFileEmailOptionsAndCreateUploadDeleteFile()
    {
        //create file and set email options
        $workspaceId = 1;
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID);

        $response = $this->put(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_EMAIL_OPTIONS_SET, array('file_id' => $folder->getId())),
            array(
                'upload_notification' => true,
                'delete_notification' => true
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($result->upload_notification);
        $this->assertFalse($result->download_notification);
        $this->assertTrue($result->delete_notification);

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
        $eventNotificationService = $this->getEventNotificationService();
        $brand = getenv('BRAND');
        $eventNotifications = $eventNotificationService->getUserEventNotifications($user, $brand, false);
        $this->assertCount(3,$eventNotifications);
    }

    public function testFileSetFileEmailOptionsAndCreateUploadDeleteFile()
    {
        //create file and set email options
        $workspaceId = 1;
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID);
        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, $folder);

        $response = $this->put(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_EMAIL_OPTIONS_SET, array('file_id' => $file->getId())),
            array(
                'upload_notification' => true,
                'delete_notification' => true
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($result->upload_notification);
        $this->assertFalse($result->download_notification);
        $this->assertTrue($result->delete_notification);

        $user = $this->getEntityManager()->find(User::getEntityName(), self::USER_ID);
        
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
        $eventNotificationService = $this->getEventNotificationService();
        $brand = getenv('BRAND');
        $eventNotifications = $eventNotificationService->getUserEventNotifications($user, $brand, false);
        $this->assertCount(2,$eventNotifications);

    }

    public function testSetFileEmailOptionsAndfGetRootDirListing()
    {
        //create file and set email options
        $workspaceId = 1;
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $file = FileHelper::createFile($this->getContainer(), $workspaceId, self::USER_ID);
        $user = $this->getEntityManager()->find(User::getEntityName(), self::USER_ID);

        $response = $this->put(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_EMAIL_OPTIONS_SET, array('file_id' => $file->getId())),
            array(
                'upload_notification' => true
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($result->upload_notification);
        $this->assertFalse($result->download_notification);
        $this->assertFalse($result->delete_notification);

        $response = $this->get(
            $this->urlFor(FoldersRouteConfig::ROUTE_ROOT_FOLDER_CHILDREN_GET, array(
                'workspace_id' => $workspaceId
            )) . '?limit=5',
            array(),
            $headers
        );
        $result = Json::decode($response->getBody());

        $this->assertCount(1, $result->entries);
        $this->assertNotNull($result->entries[0]->file_email_options);
        $this->assertNull($result->next_offset);
        $this->assertFalse($result->has_more);
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
            'file_email_options' => array(),
            'shared_links' => array(),
            'locks' => array(),
            'event_notifications' => array()
        ));
    }

    /**
     * @return EventNotificationService
     */
    private function getEventNotificationService()
    {
        return $this->getContainer()->get('EventNotificationService');
    }
}
