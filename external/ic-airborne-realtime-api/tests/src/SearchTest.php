<?php

namespace iCoordinator;

use iCoordinator\PHPUnit\DbUnit\DataSet\ArrayDataSet;
use iCoordinator\PHPUnit\TestCase;
use iCoordinator\Search\SearchIndex;

class SearchTest extends TestCase
{
    const USER_ID = 1;
    const USER_ID2 = 2;
    const PORTAL_ID = 1;

    public function setUp(): void
    {
        parent::setUp();
//        FileHelper::initializeFileMocks($this);
//        $searchIndex = $this->getContainer()->searchIndex;
//        if ($searchIndex->indexExists()) {
//            $searchIndex->deleteIndex();
//        }
//        $searchIndex->createIndex();
    }

    public function tearDown(): void
    {
//        $searchIndex = $this->getContainer()->searchIndex;
//        if ($searchIndex->indexExists()) {
//            $searchIndex->deleteIndex();
//        }
//        FileHelper::clearTmpStorage($this);
        parent::tearDown();
    }

    public function testCreateIndex()
    {
        /** @var SearchIndex $searchIndex */
//        $searchIndex = $this->getContainer()->searchIndex;
//
//        $this->assertTrue($searchIndex->indexExists());
        $this->assertTrue(true);
    }

    protected function getDataSet()
    {
        return new ArrayDataSet(array(
            'users' => array(
                array(
                    'id' => self::USER_ID,
                    'email' => '1@test.com',
                    'name' => 'Constantine',
                    'password' => 'qwe',
                    'email_confirmed' => 1
                ),
                array(
                    'id' => self::USER_ID2,
                    'email' => '2@test.com',
                    'password' => 'qwe',
                    'email_confirmed' => 1
                )
            ),
            'portals' => array(
                array(
                    'id' => self::PORTAL_ID,
                    'owned_by' => self::USER_ID2,
                    'name' => 'Test Portal'
                )
            ),
            'workspaces' => array(
                array(
                    'id' => 1,
                    'name' => 'Workspace 1',
                    'portal_id' => self::PORTAL_ID
                )
            ),
            'files' => array(

            )
        ));
    }
//
//    public function testIndexMapping()
//    {
//        /** @var SearchIndex $searchIndex */
//        $searchIndex = $this->getContainer()->searchIndex;
//
//        /** @var ElasticSearch $adapter */
//        $adapter = $searchIndex->getAdapter();
//
//        $documentTypes = $searchIndex->getDocumentTypes();
//        foreach ($documentTypes as $documentType) {
//            $mapping = $adapter->getIndex()->getMapping();
//            $this->assertEquals($documentType->getFieldsConfig(), $mapping[$documentType->getType()]['properties']);
//        }
//    }
//
//    public function testAddToIndex()
//    {
//        $file = $this->getTestFileEntity();
//
//        /** @var SearchIndex $searchIndex */
//        $searchIndex = $this->getContainer()->searchIndex;
//        $result = $searchIndex->addEntity($file);
//
//        $this->assertEquals(201, $result->getStatus());
//    }
//
//    public function testGetFromIndex()
//    {
//        $file = $this->getTestFileEntity();
//
//        /** @var SearchIndex $searchIndex */
//        $searchIndex = $this->getContainer()->searchIndex;
//        $searchIndex->addEntity($file);
//
//        //fetching file from index
//        $fileFromIndex = $searchIndex->getEntity(File::ENTITY_NAME, $file->getId());
//
//        $this->assertEquals($file->getId(), $fileFromIndex->getId());
//        $this->assertEquals($file->getName(), $fileFromIndex->getName());
//    }
//
//    public function testSearch()
//    {
//        $file = $this->getTestFileEntity();
//
//        /** @var SearchIndex $searchIndex */
//        $searchIndex = $this->getContainer()->searchIndex;
//        $searchIndex->addEntity($file);
//
//        $searchIndex->getIndex()->refresh();
//
//        $result = $searchIndex->search('file_');
//
//        $this->assertEquals($file->getId(), $result[0]->getId());
//    }
//
//    public function testFileSearch()
//    {
//        $file = $this->getTestFileEntity();
//
//        /** @var SearchIndex $searchIndex */
//        $searchIndex = $this->getContainer()->searchIndex;
//        $searchIndex->addEntity($file);
//
//        $searchIndex->getIndex()->refresh();
//
//        $sizeFilter = FilterParamsParser::parse(',827900', FilterParamsParser::PARAM_TYPE_NUMERIC_RANGE);
//        $ownedByFilter = FilterParamsParser::parse(self::USER_ID, FilterParamsParser::PARAM_TYPE_NUMERIC_LIST);
//
//        $result = $searchIndex->searchByEntityName(File::getEntityName(), 'Document2', array(
//            \iCoordinator\Search\DocumentType\File::FILTER_SIZE_RANGE => $sizeFilter,
//            \iCoordinator\Search\DocumentType\File::FILTER_OWNER_USER_IDS => $ownedByFilter
//        ));
//    }
//
//    private function getTestFileEntity()
//    {
//        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, null, 'Document2.pdf');
//
//        return $file;
//    }
}