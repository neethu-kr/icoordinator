<?php

namespace iCoordinator;

use iCoordinator\Entity\Portal;
use iCoordinator\Entity\User;
use iCoordinator\Permissions\PermissionType;
use iCoordinator\PHPUnit\DbUnit\DataSet\ArrayDataSet;
use iCoordinator\PHPUnit\Helper\FileHelper;
use iCoordinator\PHPUnit\TestCase;
use iCoordinator\Service\Exception\ConflictException;
use iCoordinator\Service\FolderService;
use iCoordinator\Service\PermissionService;

class FolderServiceTest extends TestCase
{
    const USER_ID = 1;
    const USER_ID2 = 2;
    const USERNAME = 'test@user.com';
    const USERNAME2 = 'test2@user.com';
    const PASSWORD = 'password';
    const PUBLIC_CLIENT_ID = 'public_test';
    const TEST_FILE_NAME = 'Document1.pdf';
    const PORTAL_ID = 1;

    public function tearDown(): void
    {
        parent::tearDown();

        FileHelper::clearTmpStorage($this);
    }

    public function testGetFolder()
    {
        list($folder, $folder2) = $this->createFolderTree(1, self::USER_ID);
        $folderId = $folder->getId();

        $folderService = $this->getContainer()->get('FolderService');
        $folder = $folderService->getFolder($folderId);

        $this->assertInstanceOf('iCoordinator\Entity\Folder', $folder);
        $this->assertEquals($folderId, $folder->getId());
    }

    private function createFolderTree($workspaceId, $userId)
    {
        $folder1 = FileHelper::createFolder($this->getContainer(), $workspaceId, $userId);
        $folder2 = FileHelper::createFolder($this->getContainer(), $workspaceId, $userId);

        //add 3 children to folder1
        FileHelper::createFolder($this->getContainer(), $workspaceId, $userId, $folder1);
        FileHelper::createFolder($this->getContainer(), $workspaceId, $userId, $folder1);
        FileHelper::createFile($this->getContainer(), $workspaceId, $userId, $folder1);

        //add 1 child to folder2
        FileHelper::createFolder($this->getContainer(), $workspaceId, $userId, $folder2);

        return array($folder1, $folder2);
    }

    public function testGetFolderChildren()
    {
        list($folder, $folder2) = $this->createFolderTree(1, self::USER_ID);

        $folderService = $this->getContainer()->get('FolderService');
        $paginator = $folderService->getFolderChildrenAvailableForUser($folder, self::USER_ID, 3, 0, null);

        $this->assertCount(3, $paginator->getIterator());
    }

    public function testGetRootFolderChildren()
    {
        list($folder, $folder2) = $this->createFolderTree(1, self::USER_ID);

        $paginator = $this->getFolderService()->getRootFolderChildren(1, 10, 0);

        $this->assertCount(2, $paginator->getIterator());
    }

    public function testGetRootFolderChildrenAvailableForUser()
    {
        list($folder, $folder2) = $this->createFolderTree(1, self::USER_ID);

        $folder3 = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID, $folder);

        $user = $this->getEntityManager()->getReference(User::getEntityName(), self::USER_ID);
        $portal = $this->getEntityManager()->getReference(Portal::getEntityName(), self::PORTAL_ID);

        $this->getPermissionService()->addPermission(
            $folder3,
            $user,
            [PermissionType::FILE_READ],
            self::USER_ID,
            $portal
        );

        $this->getPermissionService()->addPermission(
            $folder,
            $user,
            [PermissionType::FILE_READ],
            self::USER_ID,
            $portal
        );

        $paginator = $this->getFolderService()->getRootFolderChildrenAvailableForUser(1, self::USER_ID, 10, 0);

        $this->assertCount(1, $paginator->getIterator());
        $this->assertContains($folder, $paginator->getIterator());
    }

    public function testGetRootFolderNotRootChildrenAvailableForUser()
    {
        list($folder, $folder2) = $this->createFolderTree(1, self::USER_ID);

        $folder3 = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID, $folder);

        $user = $this->getEntityManager()->getReference(User::getEntityName(), self::USER_ID);
        $portal = $this->getEntityManager()->getReference(Portal::getEntityName(), self::PORTAL_ID);

        $this->getPermissionService()->addPermission(
            $folder3,
            $user,
            [PermissionType::FILE_READ],
            self::USER_ID,
            $portal
        );

        $paginator = $this->getFolderService()->getRootFolderChildrenAvailableForUser(1, self::USER_ID, 10, 0);

        $this->assertCount(0, $paginator->getIterator());
    }

    public function testDeleteFolder()
    {
        list($folder, $folder2) = $this->createFolderTree(1, self::USER_ID);
        $folderId = $folder->getId();
        $children = $folder->getChildren();

        $folderService = $this->getContainer()->get('FolderService');
        $folderService->deleteFolder($folder, self::USER_ID, true);

        $deletedFolder = $folderService->getFolder($folderId);
        $this->assertTrue($deletedFolder->getIsDeleted());

        foreach ($children as $child) {
            $this->assertTrue($child->getIsDeleted(), "Child folder or file is not deleted");
        }
    }

    public function testUpdateFolderName()
    {
        list($folder, $folder2) = $this->createFolderTree(1, self::USER_ID);

        $newName = 'New folder name';

        $folderService = $this->getContainer()->get('FolderService');
        $folderService->updateFolder($folder, array(
           'name' => $newName
        ), self::USER_ID);

        $folder = $folderService->getFolder($folder->getId());

        $this->assertEquals($newName, $folder->getName(), 'Folder name was not updated');
    }

    public function testFolderCopy()
    {
        $folder1 = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID);
        $folder2 = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID);
        $file1 = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, $folder1);

        $folderService = $this->getContainer()->get('FolderService');
        $newName = $folder1->getName() . ' (copy)';
        $folderCopy = $folderService->copyFolder($folder1, array(
            'name' => $newName,
            'parent' => array(
                'id' => $folder2->getId()
            )
        ), self::USER_ID);

        $this->assertNotEquals($folder1->getId(), $folderCopy->getId());
        $this->assertEquals($newName, $folderCopy->getName());
        $this->assertEquals($folder1->getEtag(), $folderCopy->getEtag());
        $this->assertCount(1, $folderService->getFolderChildrenAvailableForUser($folderCopy, self::USER_ID, 100, 0, null));
    }

    public function testFolderCopyWithSameNameAndLocation()
    {
        $folder1 = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID);
        $folder2 = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID, $folder1);
        $file1 = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, $folder2);

        $folderService = $this->getContainer()->get('FolderService');
        try {
            $folderCopy = $folderService->copyFolder($folder2, array(
                'parent' => array(
                    'id' => $folder1->getId()
                )
            ), self::USER_ID);
            $this->assertTrue(false);
        } catch (ConflictException $e) {
            $this->assertTrue(true);
        }
    }

    public function testUpdateFolderParent()
    {
        list($folder1, $folder2) = $this->createFolderTree(1, self::USER_ID);
        $child = $folder1->getChildren()->first();

        $folder1ChildrenCount = $folder1->getChildren()->count();
        $folder2ChildrenCount = $folder2->getChildren()->count();

        $folderService = $this->getContainer()->get('FolderService');
        $folderService->updateFolder($child, array(
            'parent' => array(
                'id' => $folder2->getId()
            ),
        ), self::USER_ID);

        $folder1 = $folderService->getFolder($folder1->getId());
        $folder2 = $folderService->getFolder($folder2->getId());
        $child = $folderService->getFolder($child->getId());

        $this->assertCount(--$folder1ChildrenCount, $folder1->getChildren());
        $this->assertCount(++$folder2ChildrenCount, $folder2->getChildren());
        $this->assertEquals($child->getParent()->getId(), $folder2->getId());
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
            'events' => array()
        ));
    }

    /**
     * @return FolderService
     */
    private function getFolderService()
    {
        return $this->getContainer()->get('FolderService');
    }

    /**
     * @return PermissionService
     */
    private function getPermissionService()
    {
        return $this->getContainer()->get('PermissionService');
    }
}
