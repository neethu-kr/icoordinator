<?php

namespace iCoordinator;

use iCoordinator\Config\Route\FilesRouteConfig;
use iCoordinator\Config\Route\FoldersRouteConfig;
use iCoordinator\Config\Route\MetaFieldsRouteConfig;
use iCoordinator\Entity\Acl\AclResource\AclPortalResource;
use iCoordinator\Entity\Acl\AclResource\AclWorkspaceResource;
use iCoordinator\Entity\Acl\AclRole\AclUserRole;
use iCoordinator\Entity\MetaField;
use iCoordinator\Entity\Portal;
use iCoordinator\Entity\Workspace;
use iCoordinator\Permissions\BitMask;
use iCoordinator\Permissions\PermissionType;
use iCoordinator\PHPUnit\DbUnit\DataSet\ArrayDataSet;
use iCoordinator\PHPUnit\Helper\FileHelper;
use iCoordinator\PHPUnit\TestCase;
use Laminas\Json\Json;


class MetaFieldsTest extends TestCase
{
    const USER_ID = 1;
    const USER_ID2 = 2;
    const USERNAME = 'test@user.com';
    const USERNAME2 = 'test2@user.com';
    const PASSWORD = 'password';
    const PUBLIC_CLIENT_ID = 'public_test';
    const META_FIELD_ID = 1;
    const PORTAL_ID = 1;

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
            'portals' => array(
                array(
                    'id' => self::PORTAL_ID,
                    'name' => 'Test Portal',
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
                //portal permissions
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
                    'bit_mask' => $workspaceBitMask->getBitMask(PermissionType::WORKSPACE_ACCESS),
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'acl_role_id' => self::USER_ID,
                    'acl_resource_id' => 2,
                    'bit_mask' => $workspaceBitMask->getBitMask(PermissionType::WORKSPACE_ACCESS),
                    'portal_id' => self::PORTAL_ID
                )
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
            'meta_fields_values' => array(),
            'meta_fields_criteria' => array(),
            'files' => array(),
            'events' => array()
        ));
    }

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

    public function testGetMetaFieldsHasMore()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->get(
            $this->urlFor(
                MetaFieldsRouteConfig::ROUTE_META_FIELDS_LIST,
                array('portal_id' => self::PORTAL_ID),
                array('limit' => 1)
            ),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(1, $result->entries);
        $this->assertEquals(1, $result->next_offset);
        $this->assertTrue($result->has_more);
    }

    public function testGetMetaFieldsNoMore()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->get(
            $this->urlFor(
                MetaFieldsRouteConfig::ROUTE_META_FIELDS_LIST,
                array('portal_id' => self::PORTAL_ID),
                array('limit' => 1, 'offset' => 1)
            ),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(1, $result->entries);
        $this->assertNull($result->next_offset);
        $this->assertFalse($result->has_more);
    }

    public function testMetaFieldGet()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->get(
            $this->urlFor(MetaFieldsRouteConfig::ROUTE_META_FIELD_GET, array('meta_field_id' => self::META_FIELD_ID)),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(self::META_FIELD_ID, $result->id);
    }

    public function testMetaFieldAdd()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->post(
            $this->urlFor(MetaFieldsRouteConfig::ROUTE_META_FIELD_ADD, array('portal_id' => self::PORTAL_ID)),
            array(
                'name' => 'Meta Field 1',
                'type' => MetaField::TYPE_STRING
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertNotEmpty($result->id);
        $this->assertEquals('Meta Field 1', $result->name);
        $this->assertEquals(MetaField::TYPE_STRING, $result->type);



        //add meta field with same name
        $response = $this->post(
            $this->urlFor(MetaFieldsRouteConfig::ROUTE_META_FIELD_ADD, array('portal_id' => self::PORTAL_ID)),
            array(
                'name' => 'Meta Field 1',
                'type' => MetaField::TYPE_STRING
            ),
            $headers
        );

        $this->assertEquals(409, $response->getStatusCode());
    }

    public function testMetaFieldUpdate()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->put(
            $this->urlFor(MetaFieldsRouteConfig::ROUTE_META_FIELD_UPDATE, array('meta_field_id' => self::META_FIELD_ID)),
            array(
                'name' => 'New Meta Name'
            ),
            $headers
        );

        $result = Json::decode($response->getBody());


        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($result->id);
        $this->assertEquals('New Meta Name', $result->name);
    }

    public function testMetaFieldUpdateAccessDenied()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->put(
            $this->urlFor(MetaFieldsRouteConfig::ROUTE_META_FIELD_UPDATE, array('meta_field_id' => self::META_FIELD_ID)),
            array(
                'name' => 'New Meta Name'
            ),
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testMetaFieldDelete()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->delete(
            $this->urlFor(MetaFieldsRouteConfig::ROUTE_META_FIELD_DELETE, array('meta_field_id' => self::META_FIELD_ID)),
            null,
            $headers
        );

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testMetaFieldDeleteAccessDenied()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->delete(
            $this->urlFor(MetaFieldsRouteConfig::ROUTE_META_FIELD_DELETE, array('meta_field_id' => self::META_FIELD_ID)),
            null,
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testAddFileMetaFieldValue()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID);

        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_META_FIELD_VALUE_ADD, array('file_id' => $file->getId())),
            array(
                'meta_field' => array(
                    'id' => self::META_FIELD_ID
                ),
                'value' => 'option1'
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertNotEmpty($result->id);
        $this->assertEquals(self::META_FIELD_ID, $result->meta_field->id);
        $this->assertEquals('option1', $result->value);


        $response = $this->get(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_META_FIELDS_VALUES_GET_LIST, array('file_id' => $file->getId())),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertCount(1, $result);
        $this->assertEquals($file->getId(), $result[0]->resource->id);
    }

    public function testAddFolderMetaFieldValue()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID);

        $response = $this->post(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_META_FIELD_VALUE_ADD, array('folder_id' => $folder->getId())),
            array(
                'meta_field' => array(
                    'id' => self::META_FIELD_ID
                ),
                'value' => 'option1'
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertNotEmpty($result->id);
        $this->assertEquals(self::META_FIELD_ID, $result->meta_field->id);
        $this->assertEquals('option1', $result->value);

        $response = $this->get(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_META_FIELDS_VALUES_GET_LIST, array('folder_id' => $folder->getId())),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertCount(1, $result);
        $this->assertEquals($folder->getId(), $result[0]->resource->id);
    }

    public function testUpdateMetaFieldValue()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID);

        $metaFieldsService = $this->getContainer()->get('MetaFieldService');
        $metaFieldValue = $metaFieldsService->addMetaFieldValue(
            $file,
                array(
                'meta_field' => array('id' => self::META_FIELD_ID),
                'value' => 'option1'
            ),
            self::USER_ID
        );

        $response = $this->put(
            $this->urlFor(MetaFieldsRouteConfig::ROUTE_META_FIELD_VALUE_UPDATE, array(
                'meta_field_value_id' => $metaFieldValue->getId()
            )),
            array(
                'value' => 'option2'
            ),
            $headers
        );

        $result = Json::decode($response->getBody());


        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($result->id);
        $this->assertEquals('option2', $result->value);
    }

    public function testDeleteMetaFieldValue()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID);

        $metaFieldsService = $this->getContainer()->get('MetaFieldService');
        $metaFieldValue = $metaFieldsService->addMetaFieldValue(
            $file,
            array(
                'meta_field' => array('id' => self::META_FIELD_ID),
                'value' => 'option1'
            ),
            self::USER_ID
        );

        $response = $this->delete(
            $this->urlFor(MetaFieldsRouteConfig::ROUTE_META_FIELD_VALUE_DELETE, array(
                    'meta_field_value_id' => $metaFieldValue->getId())
            ),
            null,
            $headers
        );

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }
}
