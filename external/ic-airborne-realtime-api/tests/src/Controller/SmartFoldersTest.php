<?php

namespace iCoordinator;

use iCoordinator\Config\Route\SmartFoldersRouteConfig;
use iCoordinator\Entity\Acl\AclResource\AclPortalResource;
use iCoordinator\Entity\Acl\AclResource\AclWorkspaceResource;
use iCoordinator\Entity\Acl\AclRole\AclUserRole;
use iCoordinator\Entity\File;
use iCoordinator\Entity\Folder;
use iCoordinator\Entity\MetaFieldCriterion;
use iCoordinator\Entity\Portal;
use iCoordinator\Entity\Workspace;
use iCoordinator\Permissions\BitMask;
use iCoordinator\Permissions\PermissionType;
use iCoordinator\PHPUnit\DbUnit\DataSet\ArrayDataSet;
use iCoordinator\PHPUnit\Helper\FileHelper;
use iCoordinator\PHPUnit\TestCase;
use Laminas\Json\Json;


class SmartFoldersTest extends TestCase
{
    const USER_ID = 1;
    const USER_ID2 = 2;
    const USERNAME = 'test@user.com';
    const USERNAME2 = 'test2@user.com';
    const PASSWORD = 'password';
    const PUBLIC_CLIENT_ID = 'public_test';
    const TEST_FILE_NAME = 'Document1.pdf';
    const META_FIELD_ID = 1;
    const WORKSPACE_ID = 1;
    const PORTAL_ID = 1;

    public function setUp(): void
    {
        parent::setUp();

        FileHelper::initializeFileMocks($this);
    }

    public function testAddSmartFolderByWorkspaceAdmin()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $testName = 'Test folder';

        $response = $this->post(
            $this->urlFor(SmartFoldersRouteConfig::ROUTE_SMART_FOLDER_ADD, array('workspace_id' => 1)),
            array(
                'name' => $testName
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals($result->name, $testName);
        $this->assertNotEmpty($result->id);
    }

    public function testAddSubSmartFolderByWorkspaceAdmin()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $workspaceId = 1;
        $folder = FileHelper::createFolder($this->getContainer(), $workspaceId, self::USER_ID);

        $testName = 'Test folder';
        $response = $this->post(
            $this->urlFor(SmartFoldersRouteConfig::ROUTE_SMART_FOLDER_ADD, array('workspace_id' => $workspaceId)),
            array(
                'name' => $testName,
                'parent' => $folder->jsonSerialize()
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals($result->name, $testName);
        $this->assertNotEmpty($result->id);
        $this->assertEquals($result->parent->id, $folder->getId());
    }

    public function testGetSmartFolder()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $workspaceId = 1;
        $smartFolder = $this->createFolderTree($workspaceId, self::USER_ID);

        $this->getEntityManager()->clear();

        $response = $this->get(
            $this->urlFor(SmartFoldersRouteConfig::ROUTE_SMART_FOLDER_GET, array(
                'smart_folder_id' => $smartFolder->getId()
            )),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($smartFolder->getId(), $result->id);
    }

    private function createFolderTree($workspaceId, $userId)
    {
        $folder1 = FileHelper::createFolder($this->getContainer(), $workspaceId, $userId);
        $folder2 = FileHelper::createFolder($this->getContainer(), $workspaceId, $userId);

        $smartFolder = $this->createSmartFolder($workspaceId, $userId, $folder1);

        //add 3 children to folder1
        FileHelper::createFolder($this->getContainer(), $workspaceId, $userId, $folder1);
        FileHelper::createFolder($this->getContainer(), $workspaceId, $userId, $folder1);
        FileHelper::createFile($this->getContainer(), $workspaceId, $userId, $folder1);

        //add 1 child to folder2
        FileHelper::createFolder($this->getContainer(), $workspaceId, $userId, $folder2);

        return $smartFolder;
    }

    private function createSmartFolder($workspaceId, $userId, Folder $parent = null)
    {
        $smartFolderService = $this->getContainer()->get('SmartFolderService');

        if ($parent instanceof Folder) {
            $parent = $parent->jsonSerialize();
        }

        $smartFolder = $smartFolderService->createSmartFolder(array(
            'name' => uniqid('folder_'),
            'parent' => $parent
        ), $workspaceId, $userId);

        return $smartFolder;
    }

    public function testUpdateSmartFolderName()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $smartFolder = $this->createSmartFolder(1, self::USER_ID);

        $newName = 'New folder name';

        $response = $this->put(
            $this->urlFor(SmartFoldersRouteConfig::ROUTE_SMART_FOLDER_UPDATE, array(
                'smart_folder_id' => $smartFolder->getId()
            )),
            array(
                'name' => $newName
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($newName, $result->name);
    }


    public function testTrashSmartFolder()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $workspaceId = 1;
        $smartFolder = $this->createSmartFolder($workspaceId, self::USER_ID);

        $response = $this->delete(
            $this->urlFor(SmartFoldersRouteConfig::ROUTE_SMART_FOLDER_DELETE, array(
                'smart_folder_id' => $smartFolder->getId()
            )),
            array(),
            $headers
        );

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
        $this->assertTrue($smartFolder->getIsTrashed());
        $this->assertFalse($smartFolder->getIsDeleted());
    }

    public function testDeleteSmartFolder()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $workspaceId = 1;
        $smartFolder = $this->createSmartFolder($workspaceId, self::USER_ID);

        $response = $this->delete(
            $this->urlFor(SmartFoldersRouteConfig::ROUTE_SMART_FOLDER_DELETE_PERMANENTLY, array(
                'smart_folder_id' => $smartFolder->getId()
            )),
            array(),
            $headers
        );

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
        $this->assertTrue($smartFolder->getIsDeleted());
    }

    public function testRestoreSmartFolderByPortalAdmin()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $folder = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID);
        $smartFolder = $this->createSmartFolder(1, self::USER_ID, $folder);

        $smartFolderService = $this->getContainer()->get('SmartFolderService');
        $smartFolderService->deleteSmartFolder($smartFolder, self::USER_ID);

        $response = $this->post(
            $this->urlFor(
                SmartFoldersRouteConfig::ROUTE_SMART_FOLDER_RESTORE,
                array('smart_folder_id' => $smartFolder->getId())
            ),
            array(),
            $headers
        );

        $this->assertEquals(201, $response->getStatusCode());
    }


    public function testAddSmartFolderCriterion()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $workspaceId = 1;
        $smartFolder = $this->createSmartFolder($workspaceId, self::USER_ID);


        $metaFieldService = $this->getContainer()->get('MetaFieldService');
        $metaField = $metaFieldService->getMetaField(self::META_FIELD_ID);
        $value = $metaField->getOptions()->current();

        $response = $this->post(
            $this->urlFor(
                SmartFoldersRouteConfig::ROUTE_SMART_FOLDER_META_FIELD_CRITERION_ADD,
                array('smart_folder_id' => $smartFolder->getId())
            ),
            array(
                'meta_field' => array(
                    'id' => self::META_FIELD_ID
                ),
                'condition_type' => MetaFieldCriterion::CONDITION_EQUALS,
                'value' => $value
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertNotEmpty($result->id);
        $this->assertEquals(self::META_FIELD_ID, $result->meta_field->id);
        $this->assertEquals($value, $result->value);
        $this->assertEquals(MetaFieldCriterion::CONDITION_EQUALS, $result->condition_type);
    }


    public function testUpdateSmartFolderCriterion()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $workspaceId = 1;
        $smartFolder = $this->createSmartFolder($workspaceId, self::USER_ID);


        $metaFieldService = $this->getContainer()->get('MetaFieldService');
        $smartFolderService = $this->getContainer()->get('SmartFolderService');
        $metaField = $metaFieldService->getMetaField(self::META_FIELD_ID);
        $value = $metaField->getOptions()->current();

        $metaFieldCriterion = $smartFolderService->addSmartFolderCriterion($smartFolder, array(
            'meta_field' => $metaField->jsonSerialize(),
            'condition_type' => MetaFieldCriterion::CONDITION_EQUALS,
            'value' => $value
        ), self::USER_ID);

        $newValue = $metaField->getOptions()->next();

        $response = $this->put(
            $this->urlFor(
                SmartFoldersRouteConfig::ROUTE_SMART_FOLDER_META_FIELD_CRITERION_UPDATE,
                array('meta_field_criterion_id' => $metaFieldCriterion->getId())
            ),
            array(
                'condition_type' => MetaFieldCriterion::CONDITION_GREATER_OR_EQUALS,
                'value' => $newValue
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(self::META_FIELD_ID, $result->meta_field->id);
        $this->assertEquals($newValue, $result->value);
        $this->assertEquals(MetaFieldCriterion::CONDITION_GREATER_OR_EQUALS, $result->condition_type);
    }


    public function testDeleteSmartFolderCriterion()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $workspaceId = 1;
        $smartFolder = $this->createSmartFolder($workspaceId, self::USER_ID);


        $metaFieldService = $this->getContainer()->get('MetaFieldService');
        $smartFolderService = $this->getContainer()->get('SmartFolderService');
        $metaField = $metaFieldService->getMetaField(self::META_FIELD_ID);
        $value = $metaField->getOptions()->current();

        $metaFieldCriterion = $smartFolderService->addSmartFolderCriterion($smartFolder, array(
            'meta_field' => $metaField->jsonSerialize(),
            'condition_type' => MetaFieldCriterion::CONDITION_EQUALS,
            'value' => $value
        ), self::USER_ID);


        $response = $this->delete(
            $this->urlFor(
                SmartFoldersRouteConfig::ROUTE_SMART_FOLDER_META_FIELD_CRITERION_DELETE,
                array('meta_field_criterion_id' => $metaFieldCriterion->getId())
            ),
            null,
            $headers
        );

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }


    public function testGetSmartFolderCriteria()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $workspaceId = 1;
        $smartFolder = $this->createSmartFolder($workspaceId, self::USER_ID);


        $metaFieldService = $this->getContainer()->get('MetaFieldService');
        $smartFolderService = $this->getContainer()->get('SmartFolderService');
        $metaField = $metaFieldService->getMetaField(self::META_FIELD_ID);
        $value = $metaField->getOptions()->current();

        $metaFieldCriterion = $smartFolderService->addSmartFolderCriterion($smartFolder, array(
            'meta_field' => $metaField->jsonSerialize(),
            'condition_type' => MetaFieldCriterion::CONDITION_EQUALS,
            'value' => $value
        ), self::USER_ID);


        $response = $this->get(
            $this->urlFor(
                SmartFoldersRouteConfig::ROUTE_SMART_FOLDER_META_FIELD_CRITERIA_GET_LIST,
                array('smart_folder_id' => $smartFolder->getId())
            ),
            null,
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(1, $result);
    }


    public function testSmartFolderGetChildren()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        //creating smart folder and 3 files
        $smartFolder = $this->createSmartFolder(self::WORKSPACE_ID, self::USER_ID);
        $file1 = FileHelper::createFile($this->getContainer(), self::WORKSPACE_ID, self::USER_ID);
        $file2 = FileHelper::createFile($this->getContainer(), self::WORKSPACE_ID, self::USER_ID);
        $file3 = FileHelper::createFile($this->getContainer(), self::WORKSPACE_ID, self::USER_ID);

        $metaFieldService = $this->getContainer()->get('MetaFieldService');
        $smartFolderService = $this->getContainer()->get('SmartFolderService');

        //getting predefined test meta field
        $metaField = $metaFieldService->getMetaField(self::META_FIELD_ID);

        //define 2 meta values
        $metaValue1 = $metaField->getOptions()->current();
        $metaValue2 = $metaField->getOptions()->next();

        //add different values to different files
        $this->addTestMetaFieldValueToTheFile($file1, $metaField, $metaFieldService, $metaValue1);
        $this->addTestMetaFieldValueToTheFile($file2, $metaField, $metaFieldService, $metaValue1);
        $this->addTestMetaFieldValueToTheFile($file2, $metaField, $metaFieldService, $metaValue2);


        //adding criteria to smart folder
        $metaFieldCriterion = $smartFolderService->addSmartFolderCriterion($smartFolder, array(
            'meta_field' => $metaField->jsonSerialize(),
            'condition_type' => MetaFieldCriterion::CONDITION_CONTAINS,
            'value' => $metaValue1
        ), self::USER_ID);


        //getting smart folder children
        $response = $this->get(
            $this->urlFor(
                SmartFoldersRouteConfig::ROUTE_SMART_FOLDER_CHILDREN_GET,
                array('smart_folder_id' => $smartFolder->getId())
            ),
            null,
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(2, $result->entries);
    }

    private function addTestMetaFieldValueToTheFile($file, $metaField, $metaFieldService, $value = null)
    {
        if ($value == null) {
            $value = $metaField->getOptions()->current();
        }

        $metaFieldValue = $metaFieldService->addMetaFieldValue(
            $file,
            array(
                'meta_field' => array('id' => $metaField->getId()),
                'value' => $value
            ),
            self::USER_ID
        );

        return $metaFieldValue;
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
                )
            ),
            'acl_permissions' => array(
                array(
                    'acl_role_id' => self::USER_ID,
                    'acl_resource_id' => 1,
                    'bit_mask' => $portalBitMask->getBitMask(PermissionType::PORTAL_ADMIN),
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
                )
            ),
            'files' => array(),
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
