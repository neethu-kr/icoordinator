<?php

namespace iCoordinator;

use iCoordinator\Entity\Folder;
use iCoordinator\Entity\MetaFieldCriterion;
use iCoordinator\Entity\User;
use iCoordinator\Permissions\PermissionType;
use iCoordinator\PHPUnit\DbUnit\DataSet\ArrayDataSet;
use iCoordinator\PHPUnit\Helper\FileHelper;
use iCoordinator\PHPUnit\TestCase;


class SmartFolderServiceTest extends TestCase
{
    const USER_ID = 1;
    const USER_ID2 = 2;
    const USERNAME = 'test@user.com';
    const USERNAME2 = 'test2@user.com';
    const PASSWORD = 'password';
    const PUBLIC_CLIENT_ID = 'public_test';
    const TEST_FILE_NAME = 'Document1.pdf';
    const META_FIELD_ID = 1;
    const META_FIELD_ID3 = 3;
    const WORKSPACE_ID = 1;
    const PORTAL_ID = 1;

    public function testCreateSmartFolder()
    {
        $smartFolder = $this->createSmartFolder(1, self::USER_ID);

        $this->assertInstanceOf('iCoordinator\Entity\SmartFolder', $smartFolder);
        $this->assertNotEmpty($smartFolder->getId());
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

    public function testGetSmartFolder()
    {
        $smartFolder = $this->createSmartFolder(1, self::USER_ID);
        $smartFolderId = $smartFolder->getId();

        $this->getEntityManager()->clear();

        $smartFolderService = $this->getContainer()->get('SmartFolderService');
        $smartFolder = $smartFolderService->getSmartFolder($smartFolderId);

        $this->assertInstanceOf('iCoordinator\Entity\SmartFolder', $smartFolder);
        $this->assertEquals($smartFolderId, $smartFolder->getId());
    }

    public function testDeleteSmartFolder()
    {
        $smartFolder = $this->createSmartFolder(1, self::USER_ID);
        $smartFolderId = $smartFolder->getId();

        $this->getEntityManager()->clear();

        $smartFolderService = $this->getContainer()->get('SmartFolderService');
        $smartFolderService->deleteSmartFolder($smartFolderId, self::USER_ID, true);

        $deletedSmartFolder = $smartFolderService->getSmartFolder($smartFolderId);
        $this->assertTrue($deletedSmartFolder->getIsDeleted());
    }

    public function testUpdateSmartFolderName()
    {
        $smartFolder = $this->createSmartFolder(1, self::USER_ID);
        $smartFolderId = $smartFolder->getId();

        $this->getEntityManager()->clear();

        $newName = 'New folder name';

        $smartFolderService = $this->getContainer()->get('SmartFolderService');
        $smartFolderService->updateSmartFolder($smartFolderId, array(
           'name' => $newName
        ), self::USER_ID);

        $this->getEntityManager()->clear();

        $smartFolder = $smartFolderService->getSmartFolder($smartFolderId);

        $this->assertEquals($newName, $smartFolder->getName(), 'Folder name was not updated');
    }


    public function testUpdateSmartFolderParent()
    {
        $smartFolder = $this->createSmartFolder(1, self::USER_ID);
        $smartFolderId = $smartFolder->getId();

        $folder1 = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID);

        $this->getEntityManager()->clear();

        $smartFolderService = $this->getContainer()->get('SmartFolderService');
        $smartFolderService->updateSmartFolder($smartFolderId, array(
            'parent' => array(
                'id' => $folder1->getId()
            ),
        ), self::USER_ID);

        $this->getEntityManager()->clear();

        $smartFolder = $smartFolderService->getSmartFolder($smartFolderId);

        $this->assertEquals($smartFolder->getParent()->getId(), $folder1->getId());
    }

    public function testAddMetaFieldCriteriaToTheSmartFolder()
    {
        $smartFolder = $this->createSmartFolder(self::WORKSPACE_ID, self::USER_ID);

        $metaFieldService = $this->getContainer()->get('MetaFieldService');
        $smartFolderService = $this->getContainer()->get('SmartFolderService');

        $metaField = $metaFieldService->getMetaField(self::META_FIELD_ID);
        $value = $metaField->getOptions()->current();

        $metaFieldCriterion = $smartFolderService->addSmartFolderCriterion($smartFolder, array(
            'meta_field' => $metaField->jsonSerialize(),
            'condition_type' => MetaFieldCriterion::CONDITION_EQUALS,
            'value' => $value
        ), self::USER_ID);


        $this->assertEquals($metaField->getId(), $metaFieldCriterion->getMetaField()->getId());
        $this->assertEquals($smartFolder->getId(), $metaFieldCriterion->getSmartFolder()->getId());
        $this->assertEquals($value, $metaFieldCriterion->getValue());
        $this->assertEquals(MetaFieldCriterion::CONDITION_EQUALS, $metaFieldCriterion->getConditionType());

        //check if it was added to the file
        $this->assertCount(1, $smartFolder->getMetaFieldsCriteria());

        //fetch from the server
        $this->getEntityManager()->clear();
        $smartFolderService = $this->getContainer()->get('SmartFolderService');
        $smartFolder = $smartFolderService->getSmartFolder($smartFolder->getId());
        $this->assertCount(1, $smartFolder->getMetaFieldsCriteria());
    }

    public function testUpdateMetaFieldCriterionValue()
    {
        $smartFolder = $this->createSmartFolder(self::WORKSPACE_ID, self::USER_ID);

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

        $metaFieldCriterion = $smartFolderService->updateSmartFolderCriterion($metaFieldCriterion, array(
            'condition_type' => MetaFieldCriterion::CONDITION_CONTAINS,
            'value' => $newValue
        ), self::USER_ID);

        $this->assertEquals($newValue, $metaFieldCriterion->getValue());
        $this->assertEquals(MetaFieldCriterion::CONDITION_CONTAINS, $metaFieldCriterion->getConditionType());
    }


    //criteria test

    public function testUpdateMetaFieldCriterionWithIncorrectValue()
    {
        $smartFolder = $this->createSmartFolder(self::WORKSPACE_ID, self::USER_ID);

        $metaFieldService = $this->getContainer()->get('MetaFieldService');
        $smartFolderService = $this->getContainer()->get('SmartFolderService');

        $metaField = $metaFieldService->getMetaField(self::META_FIELD_ID);
        $value = $metaField->getOptions()->current();

        $metaFieldCriterion = $smartFolderService->addSmartFolderCriterion($smartFolder, array(
            'meta_field' => $metaField->jsonSerialize(),
            'condition_type' => MetaFieldCriterion::CONDITION_EQUALS,
            'value' => $value
        ), self::USER_ID);

        $newValue = 'not existing option';

        try {
            $metaFieldCriterion = $smartFolderService->updateSmartFolderCriterion($metaFieldCriterion, array(
                'condition_type' => MetaFieldCriterion::CONDITION_CONTAINS,
                'value' => $newValue
            ), self::USER_ID);
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertInstanceOf('iCoordinator\Service\Exception\ValidationFailedException', $e);
        }
    }

    public function testDeleteMetaFieldCriterion()
    {
        $smartFolder = $this->createSmartFolder(self::WORKSPACE_ID, self::USER_ID);

        $metaFieldService = $this->getContainer()->get('MetaFieldService');
        $smartFolderService = $this->getContainer()->get('SmartFolderService');

        $metaField = $metaFieldService->getMetaField(self::META_FIELD_ID);
        $value = $metaField->getOptions()->current();

        $metaFieldCriterion = $smartFolderService->addSmartFolderCriterion($smartFolder, array(
            'meta_field' => $metaField->jsonSerialize(),
            'condition_type' => MetaFieldCriterion::CONDITION_EQUALS,
            'value' => $value
        ), self::USER_ID);

        $smartFolderService->deleteSmartFolderCriterion($metaFieldCriterion, self::USER_ID);

        $this->assertCount(0, $smartFolder->getMetaFieldsCriteria());

        //fetch from the server
        $this->getEntityManager()->clear();
        $smartFolder = $smartFolderService->getSmartFolder($smartFolder->getId());
        $this->assertCount(0, $smartFolder->getMetaFieldsCriteria());
    }

    public function testGetSmartFolderChildren()
    {
        //adding criteria

        $smartFolder = $this->createSmartFolder(self::WORKSPACE_ID, self::USER_ID);
        $file = FileHelper::createFile($this->getContainer(), self::WORKSPACE_ID, self::USER_ID);

        $metaFieldService = $this->getContainer()->get('MetaFieldService');
        $smartFolderService = $this->getContainer()->get('SmartFolderService');

        $metaField = $metaFieldService->getMetaField(self::META_FIELD_ID);

        $this->addTestMetaFieldValueToTheFile($file, $metaField, $metaFieldService);

        $value = $metaField->getOptions()->current();

        $metaFieldCriterion = $smartFolderService->addSmartFolderCriterion($smartFolder, array(
            'meta_field' => $metaField->jsonSerialize(),
            'condition_type' => MetaFieldCriterion::CONDITION_EQUALS,
            'value' => $value
        ), self::USER_ID);


        $paginator = $smartFolderService->getSmartFolderChildren($smartFolder);

        $this->assertCount(1, $paginator->getIterator());
    }

    public function testGetSmartFolderChildrenDateSpan()
    {
        //adding criteria

        $smartFolder = $this->createSmartFolder(self::WORKSPACE_ID, self::USER_ID);
        $file = FileHelper::createFile($this->getContainer(), self::WORKSPACE_ID, self::USER_ID);

        $metaFieldService = $this->getContainer()->get('MetaFieldService');
        $smartFolderService = $this->getContainer()->get('SmartFolderService');

        $metaField = $metaFieldService->getMetaField(self::META_FIELD_ID3);

        $date = date('Y-m-d\TH:i:sO',time());
        $this->addTestMetaFieldDateValueToTheFile($date, $file, $metaField, $metaFieldService);

        $value1 = date('Y-m-d\TH:i:sO',time()-86400);
        $value2 = date('Y-m-d\TH:i:sO',time()+86400);

        $metaFieldCriterion1 = $smartFolderService->addSmartFolderCriterion($smartFolder, array(
            'meta_field' => $metaField->jsonSerialize(),
            'condition_type' => MetaFieldCriterion::CONDITION_GREATER_OR_EQUALS,
            'value' => $value1
        ), self::USER_ID);

        $metaFieldCriterion2 = $smartFolderService->addSmartFolderCriterion($smartFolder, array(
            'meta_field' => $metaField->jsonSerialize(),
            'condition_type' => MetaFieldCriterion::CONDITION_LESS_OR_EQUALS,
            'value' => $value2
        ), self::USER_ID);

        $paginator = $smartFolderService->getSmartFolderChildren($smartFolder);

        $this->assertCount(1, $paginator->getIterator());
    }

    private function addTestMetaFieldDateValueToTheFile($date, $file, $metaField, $metaFieldService)
    {
        $metaFieldValue = $metaFieldService->addMetaFieldValue(
            $file,
            array(
                'meta_field' => array('id' => $metaField->getId()),
                'value' => $date
            ),
            self::USER_ID
        );

        return $metaFieldValue;
    }

    private function addTestMetaFieldValueToTheFile($file, $metaField, $metaFieldService)
    {
        $value = $metaField->getOptions()->current();
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

    protected function setUp(): void
    {
        parent::setUp();

        FileHelper::initializeFileMocks($this);

        $workspaceService = $this->getContainer()->get('WorkspaceService');
        $workspace = $workspaceService->getWorkspace(1);
        $user = $this->getEntityManager()->find(User::getEntityName(), self::USER_ID);

        $permissionsService = $this->getContainer()->get('PermissionService');
        $permissionsService->addPermission(
            $workspace,
            $user,
            [PermissionType::WORKSPACE_ACCESS],
            self::USER_ID,
            $workspace->getPortal()
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        FileHelper::clearTmpStorage($this);
    }

    protected function getDataSet()
    {
        return new ArrayDataSet(array(
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
            ),
            'acl_permissions' => array(),
            'acl_roles' => array(),
            'acl_resources' => array(),
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
                ),
                array(
                    'id' => 3,
                    'name' => 'Test metafield 3',
                    'type' => 'date'
                )
            ),
            'meta_fields_values' => array(),
            'meta_fields_criteria' => array(),
            'events' => array()
        ));
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
}
