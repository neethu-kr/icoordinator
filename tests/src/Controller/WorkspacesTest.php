<?php

namespace iCoordinator;

use iCoordinator\Config\Route\FilesRouteConfig;
use iCoordinator\Config\Route\FoldersRouteConfig;
use iCoordinator\Config\Route\UsersRouteConfig;
use iCoordinator\Config\Route\WorkspacesRouteConfig;
use iCoordinator\Entity\Acl\AclResource\AclPortalResource;
use iCoordinator\Entity\Acl\AclResource\AclWorkspaceResource;
use iCoordinator\Entity\Acl\AclRole\AclGroupRole;
use iCoordinator\Entity\Acl\AclRole\AclUserRole;
use iCoordinator\Entity\MetaFieldCriterion;
use iCoordinator\Entity\Portal;
use iCoordinator\Entity\Workspace;
use iCoordinator\Permissions\BitMask;
use iCoordinator\Permissions\PermissionType;
use iCoordinator\PHPUnit\DbUnit\DataSet\ArrayDataSet;
use iCoordinator\PHPUnit\Helper\FileHelper;
use iCoordinator\PHPUnit\TestCase;
use Laminas\Json\Json;


class WorkspacesTest extends TestCase
{
    const USER_ID = 1;
    const USER_ID2 = 2;
    const GROUP_ID1 = 1;
    const GROUP_ID2 = 2;
    const USERNAME = 'test@user.com';
    const USERNAME2 = 'test2@user.com';
    const PASSWORD = 'password';
    const PUBLIC_CLIENT_ID = 'public_test';
    const PORTAL_ID1 = 1;
    const PORTAL_ID2 = 2;
    const WORKSPACE_ID1 = 1;
    const LICENSE_ID = 1;
    const META_FIELD_ID = 1;

    public function testWorkspaceGet()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->get(
            $this->urlFor(WorkspacesRouteConfig::ROUTE_WORKSPACE_GET, array('workspace_id' => 3)),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(3, $result->id);
        $this->assertNotEmpty($result->name);
    }

    public function testWorkspaceGetNoAccess()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->get(
            $this->urlFor(WorkspacesRouteConfig::ROUTE_WORKSPACE_GET, array('workspace_id' => 2)),
            array(),
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());

        //getting non-existing
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);
        $response = $this->get(
            $this->urlFor(WorkspacesRouteConfig::ROUTE_WORKSPACE_GET, array('workspace_id' => 101)),
            array(),
            $headers
        );

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testGetWorkspacesByPortalAdminWithFilterAll()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->get(
            sprintf('/portals/%d/workspaces?filter=all', self::PORTAL_ID1),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(2, $result->entries);
    }

    public function testGetWorkspacesByPortalAdminWithFilterAccessible()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);
        $response = $this->get(
            sprintf('/portals/%d/workspaces?filter=accessible', self::PORTAL_ID1),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(1, $result->entries);
    }

    public function testGetUserWorkspacesByPortalAdmin()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->get(
            $this->urlFor(UsersRouteConfig::ROUTE_USER_GET_WORKSPACES_LIST, array(
                'user_id' => self::USER_ID2,
                'portal_id' => self::PORTAL_ID1
            )),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(1, $result->entries);
    }

    public function testGetWorkspacesByNormalUserWithFilterAll()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->get(
            sprintf('/portals/%d/workspaces?filter=all', self::PORTAL_ID1),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(1, $result->entries);
    }

    public function testGetWorkspacesByNormalUserWithFilterAccessible()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->get(
            sprintf('/portals/%d/workspaces?filter=accessible', self::PORTAL_ID1),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(1, $result->entries);
    }

    public function testWorkspaceCreate()
    {
        //create by portal admin
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->post(
            $this->urlFor(WorkspacesRouteConfig::ROUTE_WORKSPACE_ADD, array(
                'portal_id' => self::PORTAL_ID1
            )),
            array(
                'name' => 'Test workspace',
                'desktop_sync' => 0
            ),
            $headers
        );
        $result = Json::decode($response->getBody());

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertNotEmpty($result->id);
        $this->assertEquals('Test workspace', $result->name);

        //fetching newly created workspace

        $response = $this->get(
            $this->urlFor(WorkspacesRouteConfig::ROUTE_WORKSPACE_GET, array('workspace_id' => $result->id)),
            array(),
            $headers
        );
        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($result->id);
        $this->assertEquals('Test workspace', $result->name);

        //create with empty name


        try {
            $this->post(
                $this->urlFor(WorkspacesRouteConfig::ROUTE_WORKSPACE_ADD, array(
                    'portal_id' => self::PORTAL_ID1
                )),
                array(
                    'name' => ''
                ),
                $headers
            );
            $this->assertEquals(400, $response->getStatusCode());
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }

        //create if not portal admin


        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->post(
            $this->urlFor(WorkspacesRouteConfig::ROUTE_WORKSPACE_ADD, array(
                'portal_id' => self::PORTAL_ID2
            )),
            array(
                'name' => 'Test workspace'
            ),
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testWorkspaceCreateExisting()
    {
        //create by portal admin
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->post(
            $this->urlFor(WorkspacesRouteConfig::ROUTE_WORKSPACE_ADD, array(
                'portal_id' => self::PORTAL_ID1
            )),
            array(
                'name' => 'Test workspace'
            ),
            $headers
        );
        $result = Json::decode($response->getBody());

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertNotEmpty($result->id);
        $this->assertEquals('Test workspace', $result->name);

        $response = $this->post(
            $this->urlFor(WorkspacesRouteConfig::ROUTE_WORKSPACE_ADD, array(
                'portal_id' => self::PORTAL_ID1
            )),
            array(
                'name' => 'Test workspace'
            ),
            $headers
        );

        $this->assertEquals(409, $response->getStatusCode());

    }
    public function testWorkspaceUpdate()
    {
        $newName = 'Workspace 1';

        //update by portal admin
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->put(
            $this->urlFor(WorkspacesRouteConfig::ROUTE_WORKSPACE_UPDATE, array('workspace_id' => 2)),
            array(
                'name' => $newName
            ),
            $headers
        );

        $this->assertEquals(409, $response->getStatusCode());

        $newName = 'Updated name';

        //update by portal admin
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->put(
            $this->urlFor(WorkspacesRouteConfig::ROUTE_WORKSPACE_UPDATE, array('workspace_id' => 2)),
            array(
                'name' => $newName
            ),
            $headers
        );
        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($newName, $result->name);

        //update non-existing
        $response = $this->put(
            $this->urlFor(WorkspacesRouteConfig::ROUTE_WORKSPACE_UPDATE, array('workspace_id' => 101)),
            array(
                'name' => $newName
            ),
            $headers
        );

        $this->assertEquals(404, $response->getStatusCode());

        //update by workspace admin (workspace admin can't delete or update workspace)


        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->put(
            $this->urlFor(WorkspacesRouteConfig::ROUTE_WORKSPACE_UPDATE, array('workspace_id' => 3)),
            array(
                'name' => $newName
            ),
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());

        //update by workspace user (access only)


        $response = $this->put(
            $this->urlFor(WorkspacesRouteConfig::ROUTE_WORKSPACE_UPDATE, array('workspace_id' => 1)),
            array(
                'name' => $newName
            ),
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());

        //update by workspace user without any privileges


        $response = $this->put(
            $this->urlFor(WorkspacesRouteConfig::ROUTE_WORKSPACE_UPDATE, array('workspace_id' => 4)),
            array(
                'name' => $newName
            ),
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testWorkspaceDelete()
    {
        //delete by portal admin
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->delete(
            $this->urlFor(WorkspacesRouteConfig::ROUTE_WORKSPACE_DELETE, array('workspace_id' => 2)),
            array(),
            $headers
        );

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());

        //delete non-existing
        $response = $this->delete(
            $this->urlFor(WorkspacesRouteConfig::ROUTE_WORKSPACE_DELETE, array('workspace_id' => 101)),
            array(),
            $headers
        );

        $this->assertEquals(404, $response->getStatusCode());

        //delete by workspace admin (workspace admin cant delete workspace)


        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->delete(
            $this->urlFor(WorkspacesRouteConfig::ROUTE_WORKSPACE_DELETE, array('workspace_id' => 3)),
            array(),
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());

        //delete by portal admin


        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->delete(
            $this->urlFor(WorkspacesRouteConfig::ROUTE_WORKSPACE_DELETE, array('workspace_id' => 1)),
            array(),
            $headers
        );

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());


        //delete by workspace user (access only)

        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->delete(
            $this->urlFor(WorkspacesRouteConfig::ROUTE_WORKSPACE_DELETE, array('workspace_id' => 1)),
            array(),
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());

        //delete by user without any privileges


        $response = $this->delete(
            $this->urlFor(WorkspacesRouteConfig::ROUTE_WORKSPACE_DELETE, array('workspace_id' => 4)),
            array(),
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testWorkspaceCopy()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $workspaceId = 1;
        $this->createFolderTree($workspaceId, self::USER_ID, $headers);
        $smartFolderService = $this->getContainer()->get('SmartFolderService');

        $smartFolder = $smartFolderService->createSmartFolder(array(
            'name' => uniqid('folder_'),
            'parent' => null
        ), $workspaceId, self::USER_ID);
        $metaFieldService = $this->getContainer()->get('MetaFieldService');
        $smartFolderService = $this->getContainer()->get('SmartFolderService');

        $metaField = $metaFieldService->getMetaField(self::META_FIELD_ID);
        $value = $metaField->getOptions()->current();

        $metaFieldCriterion = $smartFolderService->addSmartFolderCriterion($smartFolder, array(
            'meta_field' => $metaField->jsonSerialize(),
            'condition_type' => MetaFieldCriterion::CONDITION_EQUALS,
            'value' => $value
        ), self::USER_ID);

        $response = $this->post(
            $this->urlFor(WorkspacesRouteConfig::ROUTE_WORKSPACE_COPY, array(
                'workspace_id' => self::WORKSPACE_ID1
            )),
            array(
                'name' => 'Copied workspace',
                'users' => true,
                'groups' => true,
                'folders' => true,
                'files' => true,
                'permissions' => true,
                'labels' => true,
                'smartfolders' => true,
                'desktop_sync' => 0
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(201, $response->getStatusCode());
    }

    private function createFolderTree($workspaceId, $userId, $headers)
    {
        $folder1 = FileHelper::createFolder($this->getContainer(), $workspaceId, $userId);
        $folder2 = FileHelper::createFolder($this->getContainer(), $workspaceId, $userId);

        //add 3 children to folder1
        $permissionsFolder = FileHelper::createFolder($this->getContainer(), $workspaceId, $userId, $folder1);
        FileHelper::createFolder($this->getContainer(), $workspaceId, $userId, $folder1);
        FileHelper::createFile($this->getContainer(), $workspaceId, $userId, $folder1);
        $noPermissionsFile = FileHelper::createFile($this->getContainer(), $workspaceId, $userId, $folder1);
        $response = $this->post(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_PERMISSION_ADD, array('folder_id' => $permissionsFolder->getId())),
            array(
                'grant_to' => array(
                    'entity_type' => AclGroupRole::ACL_ROLE_ENTITY_TYPE,
                    'entity_id' => 1
                ),
                'actions' => array(
                    PermissionType::FILE_READ
                )
            ),
            $headers
        );
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
        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_META_FIELD_VALUE_ADD, array('file_id' => $noPermissionsFile->getId())),
            array(
                'meta_field' => array(
                    'id' => self::META_FIELD_ID
                ),
                'value' => 'option1'
            ),
            $headers
        );

        //add 1 child to folder2
        FileHelper::createFolder($this->getContainer(), $workspaceId, $userId, $folder2);

        return array($folder1, $folder2);
    }

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
            'user_locales' => array(
                array(
                    'id' => 1,
                    'user_id' => self::USER_ID,
                    'lang' => 'en',
                    'date_format' => 'dd/mm/yyyy',
                    'time_format' => 'HH:MM',
                    'first_week_day' => 1
                )
            ),
            'portals' => array(
                array(
                    'id' => self::PORTAL_ID1,
                    'name' => 'Portal 1',
                    'owned_by' => self::USER_ID
                ),
                array(
                    'id' => self::PORTAL_ID2,
                    'name' => 'Portal 2',
                    'owned_by' => self::USER_ID
                )
            ),
            'groups' => array(
                array(
                    'id' => 1,
                    'name' => 'Test Group 1',
                    'portal_id' => self::PORTAL_ID1,
                    'scope_type' => 'workspace',
                    'scope_id' => self::WORKSPACE_ID1
                ),
                array(
                    'id' => 2,
                    'name' => 'Test Group 2',
                    'portal_id' => self::PORTAL_ID1,
                    'scope_type' => 'workspace',
                    'scope_id' => self::WORKSPACE_ID1
                )
            ),
            'group_memberships' => array(
                array(
                    'id' => 1,
                    'user_id' => self::USER_ID,
                    'group_id' => self::GROUP_ID1
                ),
                array(
                    'id' => 2,
                    'user_id' => self::USER_ID2,
                    'group_id' => self::GROUP_ID1
                ),
                array(
                    'id' => 3,
                    'user_id' => self::USER_ID,
                    'group_id' => self::GROUP_ID2
                ),
                array(
                    'id' => 4,
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
                    'entity_id' => 1
                )
            ),
            'acl_resources' => array(
                array(
                    'id' => 1,
                    'entity_type' => AclPortalResource::ACL_RESOURCE_ENTITY_TYPE,
                    'entity_id' => self::PORTAL_ID1
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
                    'entity_type' => AclPortalResource::ACL_RESOURCE_ENTITY_TYPE,
                    'entity_id' => self::PORTAL_ID2
                ),
            ),
            'acl_permissions' => array(
                array(
                    'acl_role_id' => self::USER_ID,
                    'acl_resource_id' => 1,
                    'bit_mask' => $portalBitMask->getBitMask(PermissionType::PORTAL_ADMIN),
                    'portal_id' => self::PORTAL_ID1
                ),
                array(
                    'acl_role_id' => self::USER_ID2,
                    'acl_resource_id' => 1,
                    'bit_mask' => $portalBitMask->getBitMask(PermissionType::PORTAL_ACCESS),
                    'portal_id' => self::PORTAL_ID1
                ),
                array(
                    'acl_role_id' => self::USER_ID2,
                    'acl_resource_id' => 6,
                    'bit_mask' => $portalBitMask->getBitMask(PermissionType::PORTAL_ACCESS),
                    'portal_id' => self::PORTAL_ID1
                ),
                //workspace permissions
                array(
                    'acl_role_id' => self::USER_ID,
                    'acl_resource_id' => 2,
                    'bit_mask' => $workspaceBitMask->getBitMask(PermissionType::WORKSPACE_ACCESS),
                    'portal_id' => self::PORTAL_ID1
                ),
                array(
                    'acl_role_id' => self::USER_ID2,
                    'acl_resource_id' => 2,
                    'bit_mask' => $workspaceBitMask->getBitMask(PermissionType::WORKSPACE_ACCESS),
                    'portal_id' => self::PORTAL_ID1
                ),
                array(
                    'acl_role_id' => self::USER_ID2,
                    'acl_resource_id' => 4,
                    'bit_mask' => $workspaceBitMask->getBitMask(PermissionType::WORKSPACE_ADMIN),
                    'portal_id' => self::PORTAL_ID2
                ),
                array(
                    'acl_role_id' => self::USER_ID2,
                    'acl_resource_id' => 5,
                    'bit_mask' => 0,
                    'portal_id' => self::PORTAL_ID2
                ),
            ),
            'workspaces' => array(
                array(
                    'id' => 1,
                    'name' => 'Workspace 1',
                    'portal_id' => self::PORTAL_ID1
                ),
                array(
                    'id' => 2,
                    'name' => 'Workspace 2',
                    'portal_id' => self::PORTAL_ID1
                ),
                array(
                    'id' => 3,
                    'name' => 'Workspace 3',
                    'portal_id' => self::PORTAL_ID2
                ),
                array(
                    'id' => 4,
                    'name' => 'Workspace 4',
                    'portal_id' => self::PORTAL_ID2
                )
            ),
            'licenses' => [
                [
                    'id' => self::LICENSE_ID,
                    'users_limit' => 3,
                    'workspaces_limit' => 5,
                    'storage_limit' => 0
                ]
            ],
            'license_chargify_mappers' => [
                [
                    'id' => 1,
                    'license_id' => 1,
                    'chargify_website_id' => 'ic_eur',
                    'chargify_product_handle' => 'basic-*',
                    'chargify_users_component_ids' => '123|345',
                    'chargify_workspaces_component_ids' => null,
                    'chargify_storage_component_ids' => null
                ]
            ],
            'meta_fields' => array(
                array(
                    'id' => self::META_FIELD_ID,
                    'name' => 'Test metafield',
                    'type' => 'list',
                    'options' => "option1\noption2\noption3"
                ),
                array(
                    'id' => 2,
                    'name' => 'Test metafield 2',
                    'type' => 'number'
                )
            ),
            'meta_fields_values' => array(),
            'meta_fields_criteria' => array()
        ));
    }
}
