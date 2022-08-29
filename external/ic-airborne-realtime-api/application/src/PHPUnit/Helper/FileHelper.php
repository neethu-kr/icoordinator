<?php

namespace iCoordinator\PHPUnit\Helper;

use iCoordinator\Entity\Folder;
use iCoordinator\PHPUnit\TestCase;
use Psr\Container\ContainerInterface;

class FileHelper
{
    const TEST_FILE_NAME = 'Document1.pdf';
    const TEST_FILE_NAME2 = 'Testfil6.docx';
    const TEST_FILE_NAME3 = 'textfile.txt';

    public static function initializeFileMocks($testCase)
    {
        \Upload\FileInfo::setFactory(function ($tmpName, $name) use ($testCase) {
            $fileInfo = $testCase->getMock(
                '\Upload\FileInfo',
                array('isUploadedFile'),
                array($tmpName, $name)
            );
            $fileInfo
                ->expects($testCase->any())
                ->method('isUploadedFile')
                ->will($testCase->returnValue(true));

            return $fileInfo;
        });

        \iCoordinator\File\Storage\FileSystem::setFactory(function ($directory, $overwrite) use ($testCase) {
            $fileSystem = $testCase->getMock(
                '\iCoordinator\Upload\Storage\FileSystem',
                array('moveUploadedFile'),
                array($directory, $overwrite)
            );
            $fileSystem
                ->expects($testCase->any())
                ->method('moveUploadedFile')
                ->will($testCase->returnCallback(function ($source, $destination) {
                    return copy($source, $destination);
                }));

            return $fileSystem;
        });
    }

    public static function clearTmpStorage(TestCase $testCase)
    {
        $testCase->getContainer()->get('FileService')->getFileStorage()->clear();
        $testCase->getContainer()->get('FileService')->getUploadsStorage()->clear();
    }

    public static function getTestFileContent(ContainerInterface $c)
    {
        $settings = $c->get('settings');
        return file_get_contents($settings['testsPath'] . '/data/files/' . self::TEST_FILE_NAME);
    }

    public static function getTestFile2Content(ContainerInterface $c)
    {
        $settings = $c->get('settings');
        return file_get_contents($settings['testsPath'] . '/data/files/' . self::TEST_FILE_NAME2);
    }

    public static function getTestFile3Content(ContainerInterface $c)
    {
        $settings = $c->get('settings');
        return file_get_contents($settings['testsPath'] . '/data/files/' . self::TEST_FILE_NAME3);
    }

    public static function createFile(ContainerInterface $c, $workspaceId, $userId, Folder $parent = null, $name = null)
    {
        $settings = $c->get('settings');
        $tmpName = tempnam(sys_get_temp_dir(), 'upload_');
        copy($settings['testsPath'] . '/data/files/' . self::TEST_FILE_NAME, $tmpName);
        //mock environment
        $_FILES = array(
            'file' => array(
                'name' => self::TEST_FILE_NAME,
                'tmp_name' => $tmpName,
                'error' => UPLOAD_ERR_OK
            )
        );

        $fileService = $c->get('FileService');

        if ($parent instanceof Folder) {
            $parent = $parent->jsonSerialize();
        }

        if ($name == null) {
            $name = uniqid('file_') . '.pdf';
        }

        $file = $fileService->createFile(array(
            'name' => $name,
            'parent_id' => (isset($parent)) ? $parent['id'] : null,
        ), $workspaceId, $userId);

        $_FILES = [];

        return $file;
    }

    public static function createFile2(
        ContainerInterface $c,
        $workspaceId,
        $userId,
        Folder $parent = null,
        $name = null
    ) {
        $settings = $c->get('settings');
        $tmpName = tempnam(sys_get_temp_dir(), 'upload_');
        copy($settings['testsPath'] . '/data/files/' . self::TEST_FILE_NAME2, $tmpName);
        //mock environment
        $_FILES = array(
            'file' => array(
                'name' => self::TEST_FILE_NAME2,
                'tmp_name' => $tmpName,
                'error' => UPLOAD_ERR_OK
            )
        );

        $fileService = $c->get('FileService');

        if ($parent instanceof Folder) {
            $parent = $parent->jsonSerialize();
        }

        if ($name == null) {
            $name = uniqid('file_') . '.docx';
        }

        $file = $fileService->createFile(array(
            'name' => $name,
            'parent_id' => (isset($parent)) ? $parent['id'] : null,
        ), $workspaceId, $userId);

        $_FILES = [];

        return $file;
    }

    public static function createFile3(
        ContainerInterface $c,
        $workspaceId,
        $userId,
        Folder $parent = null,
        $name = null
    ) {
        $settings = $c->get('settings');
        $tmpName = tempnam(sys_get_temp_dir(), 'upload_');
        copy($settings['testsPath'] . '/data/files/' . self::TEST_FILE_NAME3, $tmpName);
        //mock environment
        $_FILES = array(
            'file' => array(
                'name' => self::TEST_FILE_NAME3,
                'tmp_name' => $tmpName,
                'error' => UPLOAD_ERR_OK
            )
        );

        $fileService = $c->get('FileService');

        if ($parent instanceof Folder) {
            $parent = $parent->jsonSerialize();
        }

        if ($name == null) {
            $name = uniqid('file_') . '.txt';
        }

        $file = $fileService->createFile(array(
            'name' => $name,
            'parent_id' => (isset($parent)) ? $parent['id'] : null,
        ), $workspaceId, $userId);

        $_FILES = [];

        return $file;
    }

    /**
     * @param ContainerInterface $c
     * @param $workspaceId
     * @param $userId
     * @param Folder|null $parent
     * @param null $name
     * @param array $options
     * @return Folder
     */
    public static function createFolder(
        ContainerInterface $c,
        $workspaceId,
        $userId,
        Folder $parent = null,
        $name = null,
        $options = array()
    ) {
        $folderService = $c->get('FolderService');

        if ($name == null) {
            $name = uniqid('folder_');
        }

        if (isset($options['etag'])) {
            $etag = $options['etag'];
        } else {
            $etag = 1;
        }

        $folder = $folderService->createFolder(array(
            'name' => $name,
            'parent' => (isset($parent)) ? array('id' => $parent->getId()) : null,
            'etag' => $etag
        ), $workspaceId, $userId);

        return $folder;
    }
}
