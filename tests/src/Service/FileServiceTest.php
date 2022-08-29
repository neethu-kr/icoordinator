<?php

namespace iCoordinator;

use iCoordinator\Entity\User;
use iCoordinator\File\Encryptor;
use iCoordinator\Permissions\PermissionType;
use iCoordinator\PHPUnit\DbUnit\DataSet\ArrayDataSet;
use iCoordinator\PHPUnit\Helper\FileHelper;
use iCoordinator\PHPUnit\TestCase;
use iCoordinator\Service\Exception\ConflictException;


class FileServiceTest extends TestCase
{
    const USER_ID = 1;
    const USER_ID2 = 2;
    const USERNAME = 'test@user.com';
    const USERNAME2 = 'test2@user.com';
    const PASSWORD = 'password';
    const PUBLIC_CLIENT_ID = 'public_test';
    const TEST_FILE_NAME = 'Document1.pdf';
    const TEST_FILE_NAME2 = 'Testfil6.docx';
    const PORTAL_ID = 1;

    public function tearDown(): void
    {
        parent::tearDown();

        FileHelper::clearTmpStorage($this);
    }

    public function testGetMatchingFiles(){

        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, null, self::TEST_FILE_NAME);

        $fileService = $this->getContainer()->get('FileService');
        $entries = $fileService->getMatchingFiles(self::PORTAL_ID, $fileService::FILES_LIMIT_DEFAULT, 0, self::TEST_FILE_NAME);
        $this->assertEquals(1,count($entries));
        foreach ($entries as $entry) {
            $file = $fileService->getFile($entry['id']);
            $this->assertEquals(self::TEST_FILE_NAME,$file->getName());
        }

    }

    public function testFileDownload()
    {
        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID);

        $fileService = $this->getContainer()->get('FileService');
        $downloader = $fileService->getFileDownloader($file);

        $headers = $downloader->getHeaders();

        $this->assertEquals($file->getId(), $downloader->getFileVersion()->getFile()->getId());
        $this->assertEquals(200, $downloader->getStatusCode());
        $this->assertArrayHasKey('Content-Disposition', $headers);
        $this->assertArrayHasKey('Content-Length', $headers);
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertEquals($file->getSize(), $headers->offsetGet('Content-Length'));
        $this->assertEquals($file->getMimeType(), $headers->offsetGet('Content-Type'));
    }

    public function testFileCopy()
    {
        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID);
        $folder = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID);

        $fileService = $this->getContainer()->get('FileService');
        $newName = $file->getName() . ' (copy)';
        $fileCopy = $fileService->copyFile($file, self::USER_ID, array(
            'name' => $newName,
            'parent' => array(
                'id' => $folder->getId()
            )
        ));

        $downloader = $fileService->getFileDownloader($fileCopy);
        $headers = $downloader->getHeaders();

        $this->assertNotEquals($file->getId(), $fileCopy->getId());
        $this->assertEquals($newName, $fileCopy->getName());
        $this->assertEquals(1, $fileCopy->getEtag());

        //test downloaded content
        $this->assertEquals($fileCopy->getId(), $downloader->getFileVersion()->getFile()->getId());
        $this->assertEquals(200, $downloader->getStatusCode());
        $this->assertArrayHasKey('Content-Disposition', $headers);
        $this->assertArrayHasKey('Content-Length', $headers);
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertEquals($fileCopy->getSize(), $headers->offsetGet('Content-Length'));
        $this->assertEquals($fileCopy->getMimeType(), $headers->offsetGet('Content-Type'));
    }

    public function testFileCopySameNameAndLocation()
    {
        $folder = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID);
        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, $folder);

        $fileService = $this->getContainer()->get('FileService');
        try {
            $fileCopy = $fileService->copyFile($file, self::USER_ID, array(
                'parent' => array(
                    'id' => $folder->getId()
                )
            ));
            $this->assertTrue(false);
        } catch (ConflictException $e) {
            $this->assertTrue(true);
        }
    }

    public function testFileNameConflict()
    {
        $file1 = FileHelper::createFile($this->getContainer(), 1, self::USER_ID);
        $fileName = $file1->getName(); //using same name

        try {
            FileHelper::createFile($this->getContainer(), 1, self::USER_ID, null, $fileName);
            $this->assertTrue(false);
        } catch (ConflictException $e) {
            $this->assertTrue(true);
        }
    }

    public function testEncryptDecrypt()
    {
        $settings = $this->getContainer()->get('settings');
        $filePath = $settings['testsPath'] . '/data/files/' . self::TEST_FILE_NAME2;
        $originalContent = file_get_contents($filePath);

        $encryptor = new Encryptor();
        $encryptor->encrypt($filePath);
        $encryptor->decrypt($filePath);

        $content = file_get_contents($filePath);

        $this->assertEquals($originalContent, $content);
    }

    public function testCreateFileHash()
    {
        FileHelper::createFile($this->getContainer(), 1, self::USER_ID);
        FileHelper::createFile($this->getContainer(), 1, self::USER_ID);
        FileHelper::createFile($this->getContainer(), 1, self::USER_ID);
        FileHelper::createFile($this->getContainer(), 1, self::USER_ID);
        $fileService = $this->getContainer()->get('FileService');
        $entries = $fileService->getAllFiles();
        foreach ($entries as $entry) {
            $file = $fileService->getFile($entry['id']);
            $file->setHash(null);
            $this->getEntityManager()->flush();
        }
        $count = $fileService->createFileHash();
        $this->assertEquals($count, 4);
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
            'file_versions' => array(),
            'events' => array()
        ));
    }
}
