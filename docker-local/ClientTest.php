<?php

namespace iCoordinator;

use Carbon\Carbon;
use Datetime;
use iCoordinator\Config\Route\FilesRouteConfig;
use iCoordinator\Config\Route\FoldersRouteConfig;
use iCoordinator\PHPUnit\Helper\FileHelper;
use iCoordinator\PHPUnit\TestCase;
use Upload\FileInfo;
use Laminas\Json\Json;


class ClientTest extends TestCase
{
    const USER_ID = 1;
    const USER_ID2 = 2;
    const USERNAME = 'test@user.com';
    const USERNAME2 = 'test2@user.com';
    const PASSWORD = 'password';
    const PUBLIC_CLIENT_ID = 'public_test';
    const DESKTOP_CLIENT_ID = 'icoordinator_desktop';
    const TEST_FILE_NAME = 'Document1.pdf';
    const TEST_FILE_NAME2 = 'textfile.txt';
    const TEST_FILE_NAME_BIG = 'bigfile.ifc';
    const PORTAL_ID = 1;
    const META_FIELD_ID = 1;
    const FOLDER_ID = 7;
    const LICENSE_ID = 1;
    const GB =  1073741824;
    const WORKSPACE_ID = 1;
    const WORKSPACE_ID2 = 2;

    public function setUp(): void
    {
        //parent::setUp();

        //FileHelper::initializeFileMocks($this);
        //FileHelper::clearTmpStorage($this);
    }

    protected function tearDown(): void
    {
        //parent::tearDown();
    }

    protected function getDataSet()
    {

    }

    public function testAddFolderByWorkspaceAdmin()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::DESKTOP_CLIENT_ID);

        $testName = 'Test folder';

        $response = $this->post(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_ADD, array('workspace_id' => 1)),
            array(
                'name' => $testName,
                'parent' => array(
                    'id' => 0
                )
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals($result->name, $testName);
        $this->assertNotEmpty($result->id);
    }

    public function testFileChunkedUploadByWorkspaceAdmin()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::DESKTOP_CLIENT_ID);

        $settings = $this->getContainer()->get('settings');
        $srcFile = $settings['testsPath'] . '/data/files/' . self::TEST_FILE_NAME_BIG;
        $fileSize = filesize($srcFile);
        // Create file upload
        $response = $this->post(
            '/workspaces/1/files/uploads',
            array(
                'size' => $fileSize,
                'parent_id' => 1,
                'hash' => hash_file('sha256', $srcFile)
            ),
            $headers
        );


        $this->assertEquals(201, $response->getStatusCode());
        $result = Json::decode($response->getBody());

        $this->assertNotEmpty($result->id);
        $this->assertNotEmpty($result->expires_at);
        $this->assertEquals(0, $result->offset);

        $uploadId = $result->id;

        //Starting to upload chunks
        $tmpName = tempnam(sys_get_temp_dir(), 'upload_');
        $settings = $this->getContainer()->get('settings');
        $srcFile = $settings['testsPath'] . '/data/files/' . self::TEST_FILE_NAME_BIG;
        $fileContent = file_get_contents($srcFile);
        $fileSize = filesize($srcFile);
        $chunkSize = 1048576;
        $offset = 0;
        $srcFp = fopen($srcFile, 'rb');

        do {
            fseek($srcFp, $offset);
            $chunk = fread($srcFp, $chunkSize);
            file_put_contents($tmpName, $chunk);

            $_FILES = array(
                'file' => array(
                    'name' => self::TEST_FILE_NAME_BIG,
                    'tmp_name' => $tmpName,
                    'error' => UPLOAD_ERR_OK,
                    'size' => strlen($chunk)
                )
            );

            $response = $this->post(
                sprintf('/files/uploads/%s', $uploadId),
                [
                    'offset' => $offset,
                ]
            );

            $result = json_decode($response->getBody());

            $this->assertEquals(200, $response->getStatusCode());
            $this->assertEquals(min($offset + $chunkSize, $fileSize), $result->offset);

            $offset = $result->offset;
        } while ($offset < $fileSize);

        $_FILES = [];

        //Finish upload
        $contentCreatedAt = Carbon::now()->format(DateTime::ISO8601);
        $contentModifiedAt = Carbon::now()->format(DateTime::ISO8601);
        $response = $this->post(
            '/workspaces/1/files/content',
            array(
                'name' => self::TEST_FILE_NAME_BIG,
                'upload_id' => $uploadId,
                'parent_id' => 1,
                'content_created_at' => $contentCreatedAt,
                'content_modified_at' => $contentModifiedAt
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(202, $response->getStatusCode());
        $this->assertNotEmpty($result->id);
        $this->assertEquals(1, $result->parent->id);
        $this->assertEquals(self::TEST_FILE_NAME_BIG, $result->name);
        $this->assertEquals($fileSize, $result->size);
        $this->assertEquals($contentCreatedAt, $result->content_created_at);
        $this->assertEquals($contentModifiedAt, $result->content_modified_at);

        $fileId = $result->id;

        while ($result->is_uploading
            && ($response->getStatusCode() == 200 || $response->getStatusCode() == 202)) {
            sleep(3);
            $this->getEntityManager()->clear();
            $response = $this->get(
                $this->urlFor(FilesRouteConfig::ROUTE_FILE_GET, array('file_id' => $fileId)),
                array(),
                $headers
            );
            $result = Json::decode($response->getBody());
        }
        //Test file download
        $response = $this->get(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_GET_CONTENT, array('file_id' => $fileId)),
            array(),
            $headers
        );
        //Follow redirect
        $response = $this->get(
            $response->getHeaderLine('Location'),
            array(),
            array()
        );

        $bodyContent = $response->getBody()->getContents();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($fileSize, $response->getHeaderLine('Content-Length'));
        $this->assertEquals(strlen($fileContent), $response->getHeaderLine('Content-Length'));
        $this->assertEquals($fileContent, substr($bodyContent, 0, $fileSize));
    }

    public function testFileMoveAndRenameFileInSameOperation()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::DESKTOP_CLIENT_ID);

        $folder1 = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID, null);
        $folder2 = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID, null);

        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, $folder1);


        sleep(60);
        $response = $this->put(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_UPDATE, array('file_id' => $file->getId())),
            array('parent' => array('id' => $folder2->getId()), 'name' => 'New name for '.$file->getName()),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());

        //fetching updated file

        $response = $this->get(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_GET, array('file_id' => $file->getId())),
            array(),
            $headers
        );
        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($folder1->getId(), $result->parent->id);

        //fetching children of the folder where file move attempt was made

        $response = $this->get(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_CHILDREN_GET, array('folder_id' => $folder2->getId())),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertCount(1, $result->entries);
    }

    private function mockFileUploadEnvironment()
    {
        $tmpName = tempnam(sys_get_temp_dir(), 'upload_');
        $settings = $this->getContainer()->get('settings');
        copy($settings['testsPath'] . '/data/files/' . self::TEST_FILE_NAME, $tmpName);
        $_FILES = array(
            'file' => array(
                'name' => self::TEST_FILE_NAME,
                'tmp_name' => $tmpName,
                'error' => UPLOAD_ERR_OK
            )
        );
        return new FileInfo($tmpName);
    }

    /**
     * @return FileService
     */
    private function getFileService()
    {
        return $this->getContainer()->get('FileService');
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
     * @return WorkspaceService
     */
    private function getWorkspaceService()
    {
        return $this->getContainer()->get('WorkspaceService');
    }
    //    public function testFileDownloadAsAttachmentWithRange()
//    {
//        //todo
//    }
//
//    public function testFileDownloadAsStream()
//    {
//        //todo
//    }
}
