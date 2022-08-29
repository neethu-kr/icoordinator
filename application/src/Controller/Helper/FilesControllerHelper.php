<?php

namespace iCoordinator\Controller\Helper;

use iCoordinator\Controller\AbstractRestController;
use iCoordinator\Entity\File;
use iCoordinator\Entity\Folder;
use iCoordinator\Entity\User;
use iCoordinator\Permissions\PermissionType;
use iCoordinator\Permissions\Privilege\FilePrivilege;
use iCoordinator\Permissions\Resource\FileResource;
use iCoordinator\Permissions\Role\GuestRole;
use iCoordinator\Permissions\Role\UserRole;
use Slim\Http\Request;
use Slim\Http\Response;

class FilesControllerHelper extends AbstractControllerHelper
{
    const HELPER_ID = 'files';

    public function getHelperId()
    {
        return self::HELPER_ID;
    }

    public function checkParentFolderAccess($parent, $role, $privilege, Response $response)
    {
        $acl = $this->getContainer()->get('acl');
        $container = $this->getContainer();

        if (!$parent instanceof Folder) {
            if (is_array($parent) && isset($parent['id'])) {
                $parentId = $parent['id'];
            } else {
                $parentId = $parent;
            }

            if ($parentId && (int)$parentId != 0) {
                $folderService = $container->get('FolderService');
                $parent = $folderService->getFolder($parentId);
                if (!$parent) {
                    return $response->withStatus(AbstractRestController::STATUS_BAD_REQUEST);
                }
            } else {
                $parent = null;
            }
        }

        if ($parent) {
            if ($parent->getIsDeleted() || $parent->getIsTrashed()) {
                return $response->withStatus(AbstractRestController::STATUS_BAD_REQUEST);
            }
            if (!$acl->isAllowed($role, $parent, $privilege)) {
                switch ($privilege) {
                    case FilePrivilege::PRIVILEGE_CREATE_FILES:
                    case FilePrivilege::PRIVILEGE_CREATE_FOLDERS:
                    case FilePrivilege::PRIVILEGE_DELETE:
                    case FilePrivilege::PRIVILEGE_MODIFY:
                        $permission = PermissionType::getPermissionTypeBitMask(
                            File::RESOURCE_ID,
                            PermissionType::FILE_EDIT
                        ) | PermissionType::getPermissionTypeBitMask(
                            File::RESOURCE_ID,
                            PermissionType::FILE_GRANT_EDIT
                        );
                        break;
                    case FilePrivilege::PRIVILEGE_READ:
                        $permission = PermissionType::getPermissionTypeBitMask(
                            File::RESOURCE_ID,
                            PermissionType::FILE_READ
                        ) | PermissionType::getPermissionTypeBitMask(
                            File::RESOURCE_ID,
                            PermissionType::FILE_EDIT
                        ) | PermissionType::getPermissionTypeBitMask(
                            File::RESOURCE_ID,
                            PermissionType::FILE_GRANT_READ
                        ) | PermissionType::getPermissionTypeBitMask(
                            File::RESOURCE_ID,
                            PermissionType::FILE_GRANT_EDIT
                        );
                        break;
                    default:
                        return $response->withStatus(AbstractRestController::STATUS_FORBIDDEN);
                }
                if ($this->getContainer()->get('FileService')->getFileFolderHighestPermission($parent) & $permission) {
                } else {
                    return $response->withStatus(AbstractRestController::STATUS_FORBIDDEN);
                }
            }
        }

        return true;
    }

    /**
     * @param Request $request
     * @param null $token
     * @param null $user
     * @return GuestRole|UserRole
     * @throws \Exception
     */
    public function getRoleWithSharedLinkToken(Request $request, $token = null, $user = null)
    {
        $auth = $this->getContainer()->get('auth');

        if ($user !== null) {
            if ($user instanceof User) {
                $userId = $user->getId();
            } else {
                $userId = null;
            }
        } else {
            $userId = $auth->getIdentity();
        }

        if ($token === null) {
            $token = $request->getHeaderLine(AbstractRestController::HEADER_SHARED_LINK_TOKEN);
        }

        if ($userId) {
            $role = new UserRole($userId);
        } else {
            $role = new GuestRole();
        }

        if ($token !== null) {
            $role->setSharedLinkToken($token);
        }

        return $role;
    }
}
