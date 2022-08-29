<?php

namespace iCoordinator;

use iCoordinator\Config\Route\EventsRouteConfig;
use iCoordinator\Config\Route\FilesRouteConfig;
use iCoordinator\Config\Route\FoldersRouteConfig;
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
use Laminas\Json\Json;


class EventsTest extends TestCase
{
    const USER_ID = 1;
    const USER_ID2 = 2;
    const USERNAME = 'test@user.com';
    const USERNAME2 = 'test2@user.com';
    const PASSWORD = 'password';
    const PUBLIC_CLIENT_ID = 'public_test';
    const PUBLIC_CLIENT_ID2 = 'icoordinator_desktop';
    const PORTAL_ID = 1;

    public function setUp(): void
    {
        parent::setUp();
        FileHelper::initializeFileMocks($this);
    }

    public function testGetHistoryEventsForObject()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $workspaceId = 1;
        $folder = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID);
        $folder2 = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID);

        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, $folder);

        $response = $this->put(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_UPDATE, array('file_id' => $file->getId())),
            array('name' => 'New name for '.$file->getName()),
            $headers
        );

        $response = $this->put(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_UPDATE, array('file_id' => $file->getId())),
            array('parent' => array('id' => $folder2->getId())),
            $headers
        );

        $response = $this->delete(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_DELETE, array('file_id' => $file->getId())),
            array(),
            $headers
        );

        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID2);

        $response = $this->get(
            $this->urlFor(EventsRouteConfig::ROUTE_EVENTS_FOR_OBJECT_GET),
            array("source_id" => $file->getId(), "source_type" => "file"),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(4, $result->entries);
        $this->assertFalse($result->has_more);
    }

    public function testGetHistoryEvents()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        FileHelper::createFile($this->getContainer(), 1, self::USER_ID);
        FileHelper::createFile($this->getContainer(), 1, self::USER_ID);
        FileHelper::createFile($this->getContainer(), 1, self::USER_ID2);

        $response = $this->get(
            $this->urlFor(EventsRouteConfig::ROUTE_HISTORY_GET) . '?limit=10',
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(3, $result->entries);
        $this->assertFalse($result->has_more);
    }

    public function testGetHistoryEventsForPermissions()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $workspaceId = 1;
        $folder = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID);

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

        FileHelper::createFile($this->getContainer(), 1, self::USER_ID, $folder);

        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID2);

        $response = $this->get(
            $this->urlFor(EventsRouteConfig::ROUTE_EVENTS_GET) . '?limit=10',
            array("cursor_position" => 2),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(1, $result->entries);
        $this->assertFalse($result->has_more);
    }

    public function testGetEventsWithZeroCursorPosition()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        FileHelper::createFile($this->getContainer(), 1, self::USER_ID);
        FileHelper::createFile($this->getContainer(), 1, self::USER_ID);
        FileHelper::createFile($this->getContainer(), 1, self::USER_ID2);

        $response = $this->get(
            $this->urlFor(EventsRouteConfig::ROUTE_EVENTS_GET) . '?limit=1',
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(0, $result->entries);
        $this->assertFalse($result->has_more);
        $this->assertEquals(3, $result->next_cursor_position);
    }

    public function testGetEventsWithDefinedCursorPositionHasMore()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        FileHelper::createFile($this->getContainer(), 1, self::USER_ID2);
        FileHelper::createFile($this->getContainer(), 1, self::USER_ID);
        FileHelper::createFile($this->getContainer(), 1, self::USER_ID);
        FileHelper::createFolder($this->getContainer(), 1, self::USER_ID);

        $response = $this->get(
            $this->urlFor(EventsRouteConfig::ROUTE_EVENTS_GET) . '?limit=2&cursor_position=2',
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(2, $result->entries);
        $this->assertTrue($result->has_more);
        $this->assertEquals(4, $result->next_cursor_position);
    }

    public function testGetEventsWithDefinedCursorPositionHasNotMore()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        FileHelper::createFile($this->getContainer(), 1, self::USER_ID);
        FileHelper::createFile($this->getContainer(), 1, self::USER_ID);

        $response = $this->get(
            $this->urlFor(EventsRouteConfig::ROUTE_EVENTS_GET) . '?limit=2&cursor_position=2',
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(0, $result->entries);
        $this->assertFalse($result->has_more);
        $this->assertEquals(2, $result->next_cursor_position);
    }

    public function testGetEventsWithBrokenResourceAssociation()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID);
        $this->getEntityManager()->remove($folder);
        $this->getEntityManager()->flush($folder);

        $response = $this->get(
            $this->urlFor(EventsRouteConfig::ROUTE_EVENTS_GET) . '?limit=1&cursor_position=0',
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(0, $result->entries);
        $this->assertFalse($result->has_more);
    }

    public function testGetRealTimeServerPreflightRequest()
    {
        $response = $this->options('/events', [], [
            'Access-Control-Request-Headers' => 'authorization, x-requested-with',
            'Access-Control-Request-Method' => 'GET'
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testGetRealTimeServerAction()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);
        $response = $this->options(
            '/events',
            [],
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('realtime_server', $result->entity_type);
        $this->assertNotEmpty($result->url);
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
                    'client_id' => self::PUBLIC_CLIENT_ID,
                ),
                array(
                    'client_id' => self::PUBLIC_CLIENT_ID2,
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
            'acl_permissions' => array(
                //workspace permissions
                array(
                    'acl_resource_id' => 1,
                    'acl_role_id' => 1,
                    'bit_mask' => $portalBitMask->getBitMask(PermissionType::PORTAL_ADMIN),
                    'portal_id' => 1
                ),
                array(
                    'acl_resource_id' => 1,
                    'acl_role_id' => 2,
                    'bit_mask' => $portalBitMask->getBitMask(PermissionType::PORTAL_ACCESS),
                    'portal_id' => 1
                ),
                array(
                    'acl_role_id' => 1,
                    'acl_resource_id' => 2,
                    'bit_mask' => $workspaceBitMask->getBitMask(PermissionType::WORKSPACE_ADMIN),
                    'portal_id' => 1
                ),
                array(
                    'acl_role_id' => 2,
                    'acl_resource_id' => 2,
                    'bit_mask' => $workspaceBitMask->getBitMask(PermissionType::WORKSPACE_ACCESS),
                    'portal_id' => 1
                )
            ),
            'files' => array(

            ),
            'file_versions' => array(

            ),
            'file_email_options' => array(

            ),
            'events' => array(

            ),
            'meta_fields_criteria' => array(

            ),
            'locks' => array()
        ));
    }
}
