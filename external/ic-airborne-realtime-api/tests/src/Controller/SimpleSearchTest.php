<?php

namespace iCoordinator;

use Carbon\Carbon;
use iCoordinator\Config\Route\SearchRouteConfig;
use iCoordinator\Entity\Acl\AclResource\AclFileResource;
use iCoordinator\Entity\Acl\AclResource\AclPortalResource;
use iCoordinator\Entity\Acl\AclResource\AclWorkspaceResource;
use iCoordinator\Entity\Acl\AclRole\AclUserRole;
use iCoordinator\Entity\File;
use iCoordinator\Entity\Portal;
use iCoordinator\Entity\Workspace;
use iCoordinator\Permissions\BitMask;
use iCoordinator\Permissions\PermissionType;
use iCoordinator\PHPUnit\DbUnit\DataSet\ArrayDataSet;
use iCoordinator\PHPUnit\TestCase;
use Laminas\Json\Json;


class SimpleSearchTest extends TestCase
{
    const USER_ID = 1;
    const USER_ID2 = 2;
    const USERNAME = 'test@user.com';
    const USERNAME2 = 'test2@user.com';
    const PASSWORD = 'password';
    const PUBLIC_CLIENT_ID = 'public_test';
    const TEST_FILE_NAME = 'Document1.pdf';
    const TEST_FILE_NAME2 = 'Döcument1.pdf';
    const TEST_FILE_NAME3 = 'ÅÄÖ.docx';
    const PORTAL_ID = 1;
    const WORKSPACE_ID = 1;

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
                    'name' => 'Döcument1.pdf',
                    'workspace_id' => 1,
                    'size' => 999,
                    'etag' => 1,
                    'created_by' => self::USER_ID2,
                    'owned_by' => self::USER_ID2,
                    'content_created_at' => Carbon::now()->toDateTimeString(),
                    'content_modified_at' => Carbon::now()->toDateTimeString()
                ),
                array(
                    'id' => 3,
                    'type' => 'file',
                    'name' => 'ÅÄÖ.pdf',
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
                    'id' => 7,
                    'type' => 'folder',
                    'name' => 'Folder 1',
                    'workspace_id' => 1,
                    'size' => 0,
                    'etag' => 1,
                    'created_by' => self::USER_ID,
                    'owned_by' => self::USER_ID,
                    'content_created_at' => Carbon::now()->toDateTimeString(),
                    'content_modified_at' => Carbon::now()->toDateTimeString()
                )
            ),
            'file_versions' => array(),
            'events' => array(),
            'shared_links' => array(),
            'locks' => array()
        ));
    }

    public function testGetMatchingFiles()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->get(
            $this->urlFor(SearchRouteConfig::ROUTE_SEARCH_LIST),
            array(
                'limit' => 100,
                'search' => 'document1',
                'portal' => self::PORTAL_ID
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(1, $result->entries);

        $response = $this->get(
            $this->urlFor(SearchRouteConfig::ROUTE_SEARCH_LIST),
            array(
                'limit' => 100,
                'search' => 'Folder',
                'portal' => self::PORTAL_ID
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(0, $result->entries);
    }

    public function testGetMatchingWorkspaceFiles()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->get(
            $this->urlFor(SearchRouteConfig::ROUTE_SEARCH_LIST),
            array(
                'limit' => 100,
                'search' => 'document1',
                'workspace' => self::WORKSPACE_ID
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(1, $result->entries);

        $response = $this->get(
            $this->urlFor(SearchRouteConfig::ROUTE_SEARCH_LIST),
            array(
                'limit' => 100,
                'search' => 'Folder',
                'workspace' => self::WORKSPACE_ID
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(0, $result->entries);
    }

    public function testGetMatchingSpecialCharacterFiles()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->get(
            $this->urlFor(SearchRouteConfig::ROUTE_SEARCH_LIST),
            array(
                'limit' => 100,
                'search' => 'Döcument1',
                'portal' => self::PORTAL_ID
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(1, $result->entries);

        $response = $this->get(
            $this->urlFor(SearchRouteConfig::ROUTE_SEARCH_LIST),
            array(
                'limit' => 100,
                'search' => 'AAO',
                'portal' => self::PORTAL_ID
            ),
            $headers
        );

        $result = Json::decode($response->getBody());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(0, $result->entries);
    }
}