<?php

namespace iCoordinator;

use iCoordinator\Config\Route\FilesRouteConfig;
use iCoordinator\Config\Route\FoldersRouteConfig;
use iCoordinator\Config\Route\HistoryEventsRouteConfig;
use iCoordinator\Config\Route\SmartFoldersRouteConfig;
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


class HistoryEventsTest extends TestCase
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
        FileHelper::clearTmpStorage($this);
    }

    public function testRemoveTimeAndTimezoneFromDate() {
        $date = "2018-10-17T00:00:00+0200 -> 2018-10-18T00:00:00+0200";
        $pattern = "/T\d{2}:\d{2}:\d{2}((\+|-)[0-1][0-9]{3})/";
        $newDate = preg_replace($pattern,"",$date);
        $this->assertEquals("2018-10-17 -> 2018-10-18", $newDate);
    }
    public function testGetHistoryEvents()
    {
        $workspaceId = 1;
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID);
        FileHelper::createFile($this->getContainer(), 1, self::USER_ID);
        FileHelper::createFile($this->getContainer(), 1, self::USER_ID2);
        $folder = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID);

        $response = $this->put(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_UPDATE, array('file_id' => $file->getId())),
            array('parent' => array('id' => $folder->getId()), 'name' => $file->getName()),
            $headers
        );

        $response = $this->post(
            $this->urlFor(SmartFoldersRouteConfig::ROUTE_SMART_FOLDER_ADD, array('workspace_id' => 1)),
            array(
                'name' => 'Smart Folder'
            ),
            $headers
        );

        $response = $this->get(
            $this->urlFor(HistoryEventsRouteConfig::ROUTE_HISTORY_EVENTS_GET),
            array(
                'portal' => 1,
                'workspace' => $workspaceId
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(6, $result->entries);
    }

    public function testGetHistoryEventsForPermissions()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $workspaceId = 1;
        $email = 'fredrik.lindvall@designtech.se';
        $response = $this->post(
            '/invitations',
            array(
                'portal' => array(
                    'id' => self::PORTAL_ID
                ),
                'email' => $email
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $folder = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID);
        $response = $this->post(
            $this->urlFor(SmartFoldersRouteConfig::ROUTE_SMART_FOLDER_ADD, array('workspace_id' => $workspaceId)),
            array(
                'name' => 'Smart Folder'
            ),
            $headers
        );
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
        $this->getPermissionService()->clearCache();
        FileHelper::createFile($this->getContainer(), $workspaceId, self::USER_ID2, $folder);

        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID2);

        $response = $this->get(
            $this->urlFor(HistoryEventsRouteConfig::ROUTE_HISTORY_EVENTS_GET),
            array(
                'portal' => 1
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(4, $result->entries);
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
            'groups' => array(),
            'group_memberships' => array(),
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
                //workspace permissions
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
                array(
                    'acl_role_id' => 1,
                    'acl_resource_id' => 2,
                    'bit_mask' => $workspaceBitMask->getBitMask(PermissionType::WORKSPACE_ADMIN),
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'acl_role_id' => 2,
                    'acl_resource_id' => 2,
                    'bit_mask' => $workspaceBitMask->getBitMask(PermissionType::WORKSPACE_ACCESS),
                    'portal_id' => self::PORTAL_ID
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
            'history_events' => array(

            ),
            'meta_fields' => array(

            ),
            'meta_fields_criteria' => array(

            ),
            'meta_fields_values' => array(

            ),
            'locks' => array()
        ));
    }

    /**
     * @return PermissionService
     */
    private function getPermissionService()
    {
        return $this->getContainer()->get('PermissionService');
    }
}
