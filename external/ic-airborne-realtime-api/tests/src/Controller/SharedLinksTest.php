<?php

namespace iCoordinator;

use iCoordinator\Config\Route\FilesRouteConfig;
use iCoordinator\Config\Route\SharedLinkRouteConfig;
use iCoordinator\Controller\AbstractRestController;
use iCoordinator\Entity\Acl\AclResource\AclPortalResource;
use iCoordinator\Entity\Acl\AclResource\AclWorkspaceResource;
use iCoordinator\Entity\Acl\AclRole\AclGroupRole;
use iCoordinator\Entity\Acl\AclRole\AclUserRole;
use iCoordinator\Entity\File;
use iCoordinator\Entity\Portal;
use iCoordinator\Entity\Workspace;
use iCoordinator\Permissions\BitMask;
use iCoordinator\Permissions\PermissionType;
use iCoordinator\PHPUnit\DbUnit\DataSet\ArrayDataSet;
use iCoordinator\PHPUnit\Helper\FileHelper;
use iCoordinator\PHPUnit\TestCase;
use Laminas\Json\Json;


class SharedLinksTest extends TestCase
{
    const USER_ID = 1;
    const USER_ID2 = 2;
    const USER_ID3 = 3;
    const USERNAME = 'test@user.com';
    const USERNAME2 = 'test2@user.com';
    const USERNAME3 = 'test3@user.com';
    const PASSWORD = 'password';
    const PUBLIC_CLIENT_ID = 'public_test';
    const TEST_FILE_NAME = 'Document1.pdf';
    const PORTAL_ID = 1;
    const PORTAL_ID2 = 2;
    const GROUP_ID = 1;

    public function setUp(): void
    {
        parent::setUp();

        FileHelper::initializeFileMocks($this);
    }

    public function testFileCreateDeleteSharedLink()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID);

        $response = $this->put(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_UPDATE, array('file_id' => $file->getId())),
            array(
                'shared_link' => array(
                    'access_type' => 'public'
                )
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($result->shared_link);
        $this->assertNotEmpty($result->shared_link->token);


        $response = $this->put(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_UPDATE, array('file_id' => $file->getId())),
            array(
                'shared_link' => null
            ),
            $headers
        );

        $result = Json::decode($response->getBody());


        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEmpty($result->shared_link);

        $response = $this->get(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_GET, array('file_id' => $file->getId())),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEmpty($result->shared_link);
    }

    public function testFileCreateSharedLinkGetUrl()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID);

        $response = $this->put(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_UPDATE, array('file_id' => $file->getId())),
            array(
                'shared_link' => array(
                    'access_type' => 'public'
                )
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($result->shared_link);
        $this->assertNotEmpty($result->shared_link->token);

        $response = $this->get(
            $this->urlFor(SharedLinkRouteConfig::ROUTE_SHARED_LINK_URL_GET, array(
                'shared_link_id' => $file->getSharedLink()->getId()
            )),
            array()
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($result->shared_link_url);
    }

    public function testFileCreateUpdateSharedLink()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID);

        $response = $this->put(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_UPDATE, array('file_id' => $file->getId())),
            array(
                'shared_link' => array(
                    'access_type' => 'portal'
                )
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($result->shared_link);
        $this->assertNotEmpty($result->shared_link->token);


        $response = $this->put(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_UPDATE, array('file_id' => $file->getId())),
            array(
                'shared_link' => array(
                    'access_type' => 'public'
                )
            ),
            $headers
        );

        $result = Json::decode($response->getBody());


        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($result->shared_link);
        $this->assertNotEmpty($result->shared_link->token);
    }

    public function testGetFileUsingPublicSharedLink()
    {
        $file = $this->createFileWithSharedLink('public');

        $response = $this->get(
            $this->urlFor(SharedLinkRouteConfig::ROUTE_SHARED_LINK_GET, array(
                'token' => $file->getSharedLink()->getToken()
            ))
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($file->getId(), $result->id);
    }

    private function createFileWithSharedLink($acessType)
    {
        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID);

        $filesService = $this->getContainer()->get('FileService');
        $file = $filesService->updateFile(
            $file,
            array('shared_link' => array('access_type' => $acessType)),
            self::USER_ID
        );

        return $file;
    }

    public function testGetFileUsingPortalSharedLink()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $file = $this->createFileWithSharedLink('portal');

        $response = $this->get(
            $this->urlFor(SharedLinkRouteConfig::ROUTE_SHARED_LINK_GET, array(
                'token' => $file->getSharedLink()->getToken()
            )),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($file->getId(), $result->id);
    }

    public function testGetFileUsingPortalSharedLinkAuthorizationRequest()
    {
        $file = $this->createFileWithSharedLink('portal');

        $response = $this->get(
            $this->urlFor(SharedLinkRouteConfig::ROUTE_SHARED_LINK_GET, array(
                'token' => $file->getSharedLink()->getToken()
            ))
        );

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testGetFileUsingRestrictedSharedLink()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $file = $this->createFileWithSharedLink('restricted');

        $response = $this->get(
            $this->urlFor(SharedLinkRouteConfig::ROUTE_SHARED_LINK_GET, array(
                'token' => $file->getSharedLink()->getToken()
            )),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($file->getId(), $result->id);
    }

    public function testGetFileUsingRestrictedSharedLinkAuthorizationRequest()
    {
        $file = $this->createFileWithSharedLink('restricted');

        $response = $this->get(
            $this->urlFor(SharedLinkRouteConfig::ROUTE_SHARED_LINK_GET, array(
                'token' => $file->getSharedLink()->getToken()
            )),
            array()
        );

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testGetFileUsingRestrictedSharedLinkForbidden()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $file = $this->createFileWithSharedLink('restricted');

        $response = $this->get(
            $this->urlFor(SharedLinkRouteConfig::ROUTE_SHARED_LINK_GET, array(
                'token' => $file->getSharedLink()->getToken()
            )),
            array(),
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testGetFileInsideFolderWithSharedLink()
    {
        $folder = $this->createFolderWithSharedLink('public');
        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, $folder);

        $response = $this->get(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_GET, array(
                'file_id' => $file->getId()
            )),
            array(),
            array(
                AbstractRestController::HEADER_SHARED_LINK_TOKEN => $folder->getSharedLink()->getToken()
            )
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($file->getId(), $result->id);
        $this->assertContains(
            AbstractRestController::HEADER_SHARED_LINK_TOKEN,
            $response->getHeaderLine('Access-Control-Allow-Headers')
        );
    }

    private function createFolderWithSharedLink($accessType)
    {
        $folder = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID);

        $folderService = $this->getContainer()->get('FolderService');
        $file = $folderService->updateFolder(
            $folder,
            array('shared_link' => array('access_type' => $accessType)),
            self::USER_ID
        );

        return $folder;
    }

    public function testSendPublicSharedLinkNotification()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $file = $this->createFileWithSharedLink('public');

        $response = $this->post(
            $this->urlFor(SharedLinkRouteConfig::ROUTE_SHARED_LINK_SEND_NOTIFICATION, array(
                'shared_link_id' => $file->getSharedLink()->getId(),
                'message' => 'Test message'
            )),
            array(
                'emails' => array(
                    'test@user.com','fredrik.lindvall@designtech.se'
                )
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertCount(2, $result->successful_emails);
    }


    public function testGetFileUsingRestrictedSharedLinkAndQueryParams()
    {
        $file = $this->createFileWithSharedLink('restricted');
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $accessToken = str_replace('Bearer ', '', $headers['Authorization']);
        $sharedLinkToken = $file->getSharedLink()->getToken();

        $response = $this->get(
            $this->urlFor(
                FilesRouteConfig::ROUTE_FILE_GET_CONTENT,
                array('file_id' => $file->getId()),
                array('access_token' => $accessToken,
                    AbstractRestController::HEADER_SHARED_LINK_TOKEN => $sharedLinkToken,
                    'open_style' => 'attachment')
            ),
            array()
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
        $headers = $response->getHeaders();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('Content-Disposition', $headers);
        $this->assertArrayHasKey('Content-Length', $headers);
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertArrayHasKey('Access-Control-Allow-Headers', $headers);
        $this->assertEquals($file->getSize(), $response->getHeaderLine('Content-Length'));
        $this->assertEquals($file->getMimeType(), $response->getHeaderLine('Content-Type'));
        $this->assertContains('attachment', $response->getHeaderLine('Content-Disposition'));
        $this->assertNotEmpty((string)$response->getBody());
        $this->assertEquals($file->getSize(), strlen((string)$response->getBody()));
    }


    public function testFileDownloadUsingPublicSharedLink()
    {
        $file = $this->createFileWithSharedLink('public');

        $sharedLinkToken = $file->getSharedLink()->getToken();

        $response = $this->get(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_GET_CONTENT, [
                'file_id' => $file->getId()
            ]),
            [],
            [
                AbstractRestController::HEADER_SHARED_LINK_TOKEN => $sharedLinkToken
            ]
        );

        $this->assertEquals(302, $response->getStatusCode());

        $location = $response->getHeaderLine('Location');

        $response = $this->get(
            $location,
            array(),
            array()
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testFileDownloadUsingPortalSharedLinkWithGroupButNoPortalPermissions()
    {
        $file = $this->createFileWithSharedLink('portal');
        $createdBy  = $this->getUserService()->getUser(self::USER_ID);
        $grantTo    = $this->getGroupService()->getGroup(self::GROUP_ID);
        $portal = $this->getPortalService()->getPortal(self::PORTAL_ID);

        $this->getPermissionService()->addPermission($file, $grantTo, [PermissionType::FILE_EDIT], $createdBy, $portal);

        $headers = $this->getAuthorizationHeaders(self::USERNAME3, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $accessToken = str_replace('Bearer ', '', $headers['Authorization']);


        $sharedLinkToken = $file->getSharedLink()->getToken();

        $response = $this->get(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_GET_CONTENT, [
                'file_id' => $file->getId()
            ],
            [
                'access_token' => $accessToken,
                AbstractRestController::HEADER_SHARED_LINK_TOKEN => $sharedLinkToken
            ]
            ),
            []

        );

        $this->assertEquals(403, $response->getStatusCode());

        $this->assertEmpty((string)$response->getBody());
    }

    /* Private helper functions */

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
                    'name' => 'Constantine',
                    'password' => password_hash(self::PASSWORD, PASSWORD_DEFAULT),
                    'email_confirmed' => 1
                ),
                array(
                    'id' => self::USER_ID2,
                    'email' => self::USERNAME2,
                    'name' => 'John',
                    'password' => password_hash(self::PASSWORD, PASSWORD_DEFAULT),
                    'email_confirmed' => 1
                ),
                array(
                    'id' => self::USER_ID3,
                    'email' => self::USERNAME3,
                    'name' => 'User in other portal',
                    'password' => password_hash(self::PASSWORD, PASSWORD_DEFAULT),
                    'email_confirmed' => 1
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
                    'name' => 'Test Portal2',
                    'owned_by' => self::USER_ID3
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
                    'name' => 'Workspace 21',
                    'portal_id' => self::PORTAL_ID2
                )
            ),
            'groups' => array(
                array(
                    'id' => 1,
                    'name' => 'Test Group 1',
                    'portal_id' => self::PORTAL_ID,
                    'scope_type' => 'portal',
                    'scope_id' => self::PORTAL_ID
                )
            ),
            'group_memberships' => array(
                array(
                    'id' => 1,
                    'user_id' => self::USER_ID3,
                    'group_id' => self::GROUP_ID
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
                    'entity_type' => AclGroupRole::ACL_ROLE_ENTITY_TYPE,
                    'entity_id' => 1
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
                ),array(
                    'id' => 3,
                    'entity_type' => AclPortalResource::ACL_RESOURCE_ENTITY_TYPE,
                    'entity_id' => self::PORTAL_ID2
                ),
                array(
                    'id' => 4,
                    'entity_type' => AclWorkspaceResource::ACL_RESOURCE_ENTITY_TYPE,
                    'entity_id' => 2
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
                array(
                    'acl_role_id' => self::USER_ID2,
                    'acl_resource_id' => 2,
                    'bit_mask' => $workspaceBitMask->getBitMask(PermissionType::WORKSPACE_ACCESS),
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'acl_role_id' => self::USER_ID,
                    'acl_resource_id' => 2,
                    'bit_mask' => $workspaceBitMask->getBitMask(PermissionType::WORKSPACE_ACCESS),
                    'portal_id' => self::PORTAL_ID
                ),array(
                    'acl_role_id' => self::USER_ID3,
                    'acl_resource_id' => 3,
                    'bit_mask' => $portalBitMask->getBitMask(PermissionType::PORTAL_ADMIN),
                    'portal_id' => self::PORTAL_ID2
                ),
            ),
            'files' => array(),
            'shared_links' => array(),
            'invitations' => array(),
            'email_confirmations' => array(),
            'file_email_options' => array(),
            'events' => array(),
            'file_versions' => array()
        ));
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
     * @return GroupService
     */
    private function getGroupService()
    {
        return $this->getContainer()->get('GroupService');
    }
    /**
     * @return PortalService
     */
    private function getPortalService()
    {
        return $this->getContainer()->get('PortalService');
    }
}
