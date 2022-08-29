<?php

namespace iCoordinator;

use iCoordinator\Entity\MetaField;
use iCoordinator\PHPUnit\DbUnit\DataSet\ArrayDataSet;
use iCoordinator\PHPUnit\Helper\FileHelper;
use iCoordinator\PHPUnit\TestCase;

class MetaFieldServiceTest extends TestCase
{
    const USER_ID = 1;
    const USER_ID2 = 2;
    const USERNAME = 'test@user.com';
    const USERNAME2 = 'test2@user.com';
    const PASSWORD = 'password';
    const PUBLIC_CLIENT_ID = 'public_test';
    const META_FIELD_ID = 1;
    const META_FIELD_ID3 = 3;
    const WORKSPACE_ID = 1;
    const PORTAL_ID = 1;

    public function testGetMetaField()
    {
        $metaFieldService = $this->getContainer()->get('MetaFieldService');
        $metaField = $metaFieldService->getMetaField(self::META_FIELD_ID);

        $this->assertInstanceOf('iCoordinator\Entity\MetaField', $metaField);
        $this->assertEquals(self::META_FIELD_ID, $metaField->getId());
    }

    public function testGetMetaFieldsList()
    {
        $metaFieldService = $this->getContainer()->get('MetaFieldService');
        $paginator = $metaFieldService->getMetaFields(self::PORTAL_ID, 1);

        $this->assertEquals(3, $paginator->count());
        $this->assertCount(1, $paginator->getIterator());
    }

    public function testUpdateMetaField()
    {
        $metaFieldService = $this->getContainer()->get('MetaFieldService');
        $metaField = $metaFieldService->updateMetaField(
            self::META_FIELD_ID,
            array('name' => 'Updated name', 'type' => MetaField::TYPE_STRING),
            self::USER_ID
        );

        $this->assertEquals('Updated name', $metaField->getName());
        $this->assertEquals(MetaField::TYPE_STRING, $metaField->getType());
        $this->assertEmpty($metaField->getOptions());
    }

    public function testDeleteMetaField()
    {
        $metaFieldService = $this->getContainer()->get('MetaFieldService');
        $metaFieldService->deleteMetaField(self::META_FIELD_ID, self::USER_ID);

        $metaField = $metaFieldService->getMetaField(self::META_FIELD_ID);

        $this->assertNull($metaField);
    }

    public function testAddMetaFieldValueToTheFile()
    {
        $file = FileHelper::createFile($this->getContainer(), self::WORKSPACE_ID, self::USER_ID);

        $metaFieldService = $this->getContainer()->get('MetaFieldService');
        $metaField = $metaFieldService->getMetaField(self::META_FIELD_ID);

        $metaFieldValue = $this->addTestMetaFieldValueToTheFile($file, $metaField, $metaFieldService);

        $value = $metaField->getOptions()->current();

        $this->assertEquals($metaField->getId(), $metaFieldValue->getMetaField()->getId());
        $this->assertEquals($file->getId(), $metaFieldValue->getResource()->getId());
        $this->assertEquals($value, $metaFieldValue->getValue());

        //check if it was added to the file
        $this->assertCount(1, $file->getMetaFieldsValues());

        //fetch from the server
        $this->getEntityManager()->clear();
        $fileService = $this->getContainer()->get('FileService');
        $file = $fileService->getFile($file->getId());
        $this->assertCount(1, $file->getMetaFieldsValues());
    }

    private function addTestMetaFieldValueToTheFile($file, $metaField, $metaFieldService)
    {
        $value = $metaField->getOptions()->current();
        if ($metaField->getType() =='date' && $value == false) {
            $value = date('Y-m-d\TH:i:sO',time());
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

    public function testUpdateMetaFieldValue()
    {
        $file = FileHelper::createFile($this->getContainer(), self::WORKSPACE_ID, self::USER_ID);

        $metaFieldService = $this->getContainer()->get('MetaFieldService');
        $metaField = $metaFieldService->getMetaField(self::META_FIELD_ID);

        $metaFieldValue = $this->addTestMetaFieldValueToTheFile($file, $metaField, $metaFieldService);

        $newValue = $metaField->getOptions()->next();
        $metaFieldValue = $metaFieldService->updateMetaFieldValue($metaFieldValue, array('value' => $newValue), self::USER_ID);

        $this->assertEquals($newValue, $metaFieldValue->getValue());
    }

    public function testUpdateMetaFieldWithIncorrectValue()
    {
        $file = FileHelper::createFile($this->getContainer(), self::WORKSPACE_ID, self::USER_ID);

        $metaFieldService = $this->getContainer()->get('MetaFieldService');
        $metaField = $metaFieldService->getMetaField(self::META_FIELD_ID);

        $metaFieldValue = $this->addTestMetaFieldValueToTheFile($file, $metaField, $metaFieldService);

        $newValue = 'not existing option';
        try {
            $metaFieldValue = $metaFieldService->updateMetaFieldValue($metaFieldValue, $newValue, self::USER_ID);
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertInstanceOf('iCoordinator\Service\Exception\ValidationFailedException', $e);
        }
    }

    public function testUpdateDateMetaFieldValue()
    {
        $file = FileHelper::createFile($this->getContainer(), self::WORKSPACE_ID, self::USER_ID);

        $metaFieldService = $this->getContainer()->get('MetaFieldService');
        $metaField = $metaFieldService->getMetaField(self::META_FIELD_ID3);

        $metaFieldValue = $this->addTestMetaFieldValueToTheFile($file, $metaField, $metaFieldService);
        $tomorrow = time() + 86400;
        $newvalue = date('Y-m-d\TH:i:sO',$tomorrow);
        $metaFieldValue = $metaFieldService->updateMetaFieldValue($metaFieldValue, array('value' => $newvalue), self::USER_ID);

        $this->assertEquals($newvalue, $metaFieldValue->getValue());
    }

    public function testDeleteMetaFieldValue()
    {
        $file = FileHelper::createFile($this->getContainer(), self::WORKSPACE_ID, self::USER_ID);

        $metaFieldService = $this->getContainer()->get('MetaFieldService');
        $metaField = $metaFieldService->getMetaField(self::META_FIELD_ID);

        $metaFieldValue = $this->addTestMetaFieldValueToTheFile($file, $metaField, $metaFieldService);

        $metaFieldService->deleteMetaFieldValue($metaFieldValue, self::USER_ID);

        $this->assertCount(0, $file->getMetaFieldsValues());

        //fetch from the server
        $this->getEntityManager()->clear();
        $fileService = $this->getContainer()->get('FileService');
        $file = $fileService->getFile($file->getId());
        $this->assertCount(0, $file->getMetaFieldsValues());
    }

    protected function setUp(): void
    {
        parent::setUp();
        FileHelper::initializeFileMocks($this);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        FileHelper::clearTmpStorage($this);
    }

    protected function getDataSet()
    {
        return new ArrayDataSet(array(
            'oauth_clients' => array(
                array(
                    'client_id' => self::PUBLIC_CLIENT_ID
                )
            ),
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
            'events' => array(),
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
                ),
                array(
                    'id' => 3,
                    'name' => 'Test metafield 3',
                    'type' => 'date',
                    'portal_id' => self::PORTAL_ID
                )

            ),
            'meta_fields_values' => array(),
            'meta_fields_criteria' => array()
        ));
    }
}
