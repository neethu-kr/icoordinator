<?php

namespace iCoordinator\Service\Helper;

use Doctrine\ORM\EntityManager;
use iCoordinator\Entity\File;
use iCoordinator\Entity\FileVersion;
use iCoordinator\Entity\Folder;
use iCoordinator\Entity\User;
use iCoordinator\Permissions\Acl;
use iCoordinator\Permissions\Privilege\WorkspacePrivilege;
use iCoordinator\Permissions\Resource\FileResource;
use iCoordinator\Permissions\Role\UserRole;
use iCoordinator\Service\Exception\LockedException;
use iCoordinator\Service\Exception\NotFoundException;
use iCoordinator\Service\LockService;
use iCoordinator\Service\SharedLinkService;
use iCoordinator\Service\UserService;

class FileServiceHelper
{
    /**
     * @param File $fileOrFolder
     * @param $newParentFolder
     * @param EntityManager $entityManager
     * @return null|Folder
     * @throws NotFoundException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public static function updateParentFolder(
        File $fileOrFolder,
        $newParentFolder,
        EntityManager $entityManager
    ) {
        if (!$newParentFolder instanceof Folder) {
            $parentData = $newParentFolder;

            if (is_array($parentData) && isset($parentData['id'])) {
                $parentFolderId = $parentData['id'];
            } else {
                $parentFolderId = $parentData;
            }

            if (!empty($parentFolderId) && (int)$parentFolderId != 0) {
                $newParentFolder = $entityManager->find(Folder::ENTITY_NAME, $parentFolderId);
                if (!$newParentFolder) {
                    throw new NotFoundException();
                }
            } else {
                $newParentFolder = null;
            }
        }

        if ($fileOrFolder->getParent()) {
            $fileOrFolder->getParent()->removeChild($fileOrFolder);
        }

        if ($newParentFolder) {
            $newParentFolder->addChild($fileOrFolder);
        }

        return $newParentFolder;
    }

    /**
     * @param File $fileOrFolder
     * @param $sharedLinkData
     * @param User $updatedBy
     * @param SharedLinkService $sharedLinkService
     * @throws NotFoundException
     */
    public static function updateSharedLink(
        File $fileOrFolder,
        $sharedLinkData,
        User $updatedBy,
        SharedLinkService $sharedLinkService
    ) {
        if (is_array($sharedLinkData)) {
            if (isset($sharedLinkData['access_type']) && $sharedLinkData['access_type'] !== null) {
                $sharedLink = $fileOrFolder->getSharedLink();
                if ($sharedLink) {
                    $sharedLinkService->updateSharedLink($sharedLink, $sharedLinkData, $updatedBy);
                } else {
                    $sharedLinkService->createSharedLink($fileOrFolder, $sharedLinkData, $updatedBy);
                }
            } else {
                $sharedLinkService->deleteSharedLink($fileOrFolder, $updatedBy);
            }
        } elseif ($sharedLinkData === null) {
            $sharedLinkService->deleteSharedLink($fileOrFolder, $updatedBy);
        }
    }

//    /**
//     * @param File $fileOrFolder
//     * @param PermissionService $permissionService
//     * @throws NotFoundException
//     */
//    public static function deletePermissions(File $fileOrFolder, PermissionService $permissionService)
//    {
//        $permissions = $permissionService->getPermissions(File::RESOURCE_ID, $fileOrFolder->getId());
//        if ($permissions) {
//            foreach ($permissions as $permission) {
//                $permissionService->deletePermission($permission);
//            }
//        }
//    }

    public static function getFileVersionStoragePath(FileVersion $fileVersion)
    {
        $file = $fileVersion->getFile();
        $portal = $file->getWorkspace()->getPortal();
        $portalHash = md5($portal->getId());
        $fileHash = md5($file->getId());
        $versionHash = md5($fileVersion->getId());

        $path =
            substr($portalHash, 0, 2) . DIRECTORY_SEPARATOR . substr($portalHash, 2, 2) . DIRECTORY_SEPARATOR .
            $portal->getId() . DIRECTORY_SEPARATOR .
            substr($fileHash, 0, 2) . DIRECTORY_SEPARATOR . substr($fileHash, 2, 2) . DIRECTORY_SEPARATOR .
            substr($fileHash, 4, 2) . DIRECTORY_SEPARATOR . $file->getId() . DIRECTORY_SEPARATOR .
            substr($versionHash, 0, 2) . DIRECTORY_SEPARATOR . substr($versionHash, 2, 2) . DIRECTORY_SEPARATOR .
            $fileVersion->getId();

        return $path;
    }


    public static function updateFileLock(File $file, $lockData, User $updatedBy, LockService $lockService)
    {
        if ($lockData === null) {
            $lockService->deleteLock($file, $updatedBy);
        } else {
            $lockService->createLock($file, $lockData, $updatedBy);
        }
    }

    public static function checkIfLocked(File $file, User $user, LockService $lockService, UserService $userService)
    {


        if ($lockService->isLocked($file)) {
            if ($file->getLock()->getCreatedBy() != $user) {
                $lockUser = $userService->getUser($file->getLock()->getCreatedBy());
                throw new LockedException($lockUser->getName());
            }
        }
    }
    /**
     * @return Acl
     */
    public function getAcl()
    {
        return $this->getContainer()->get('acl');
    }
    public function checkIfLockedAdmin(
        File $file,
        User $user,
        LockService $lockService,
        UserService $userService,
        Acl $acl
    ) {
        if ($lockService->isLocked($file)) {
            if ($file->getLock()->getCreatedBy() != $user) {
                $role = new UserRole($user);
                $privilege = WorkspacePrivilege::PRIVILEGE_GRAND_ADMIN_PERMISSION;
                if (!$acl->isAllowed($role, $file->getWorkspace(), $privilege)) {
                    $lockUser = $userService->getUser($file->getLock()->getCreatedBy());
                    throw new LockedException($lockUser->getName());
                }
            }
        }
    }
}
