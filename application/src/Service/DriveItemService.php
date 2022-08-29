<?php

namespace iCoordinator\Service;

use iCoordinator\Entity\File;
use iCoordinator\Entity\Folder;
use iCoordinator\Entity\Workspace;
use iCoordinator\Permissions\Acl;
use iCoordinator\Permissions\PermissionType;
use iCoordinator\Permissions\Role\UserRole;
use Laminas\Authentication\AuthenticationService;

/**
 * Class DriveItemService
 * @package iCoordinator\Service
 */
abstract class DriveItemService extends AbstractService
{
    /**
     * @var PermissionService
     */
    protected $permissionService;

    /**
     * @var WorkspaceService
     */
    protected $workspaceService;

    /**
     * @var EventService
     */
    protected $eventService;

    /**
     * @var HistoryEventService
     */
    protected $historyEventService;

    /**
     * @var EventNotificationService
     */
    protected $eventNotificationService;

    /**
     * @var SharedLinkService
     */
    protected $sharedLinkService;

    /**
     * @var LockService
     */
    protected $lockService;

    /**
     * @var UserService
     */
    protected $userService;

    /**
     * @var SelectiveSyncService
     */
    protected $selectiveSyncService;

    /**
     * @var FileEmailOptionsService
     */
    protected $fileEmailOptionsService;


    /**
     * @var FileService
     */
    private $fileService;


    public function getSelectiveSyncService()
    {
        if (!$this->selectiveSyncService) {
            $this->selectiveSyncService = $this->getContainer()->get('SelectiveSyncService');
        }

        return $this->selectiveSyncService;
    }

    public function getFileEmailOptionsService()
    {
        if (!$this->fileEmailOptionsService) {
            $this->fileEmailOptionsService = $this->getContainer()->get('FileEmailOptionsService');
        }

        return $this->fileEmailOptionsService;
    }

    public function addHistoryEvents($historyEvents)
    {
        foreach ($historyEvents as $historyEvent) {
            $this->getHistoryEventService()->addEvent(
                $historyEvent[0],
                $historyEvent[1],
                $historyEvent[2],
                $historyEvent[3]
            );
        }
    }
    public function addEvents($events)
    {
        foreach ($events as $event) {
            $this->getEventService()->addEvent(
                $event[0],
                $event[1],
                $event[2],
                $event[3],
                $event[4],
                $event[5]
            );
        }
    }

    /**
     * @return EventService
     */
    public function getEventService()
    {
        if (!$this->eventService) {
            $this->eventService = $this->getContainer()->get('EventService');
        }

        return $this->eventService;
    }

    /**
     * @return HistoryEventService
     */
    public function getHistoryEventService()
    {
        if (!$this->historyEventService) {
            $this->historyEventService = $this->getContainer()->get('HistoryEventService');
        }

        return $this->historyEventService;
    }

    /**
     * @return EventNotificationService
     */
    public function getEventNotificationService()
    {
        if (!$this->eventNotificationService) {
            $this->eventNotificationService = $this->getContainer()->get('EventNotificationService');
        }

        return $this->eventNotificationService;
    }

    //TODO cache results either in Redis or in var

    /**
     * @return SharedLinkService
     */
    public function getSharedLinkService()
    {
        if (!$this->sharedLinkService) {
            $this->sharedLinkService = $this->getContainer()->get('SharedLinkService');
        }
        return $this->sharedLinkService;
    }

    /**
     * @return LockService
     */
    public function getLockService()
    {
        if (!$this->lockService) {
            $this->lockService = $this->getContainer()->get('LockService');
        }
        return $this->lockService;
    }

    /**
     * @param $driveItem
     * @return array
     */
    protected function getDriveItemUsers($driveItem)
    {
        $driveItemUserIds = $this->getDriveItemUserIds($driveItem);

        $users = $this->getUserService()->getUsersByIds($driveItemUserIds);
        return $users;
    }

    /**
     * @param $user
     * @param $driveItem
     * @return boolean
     */
    protected function isAllowed($user, $driveItem)
    {
        if (!is_numeric($user)) {
            $userId = $user->getId();
            /** @var User $user */
            $user = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        } else {
            $userId = $user;
        }
        return $this->getAcl()->isAllowed(new UserRole($userId), $driveItem);
    }
        /**
     * @param $driveItem
     * @return array
     */
    protected function getDriveItemUserIds($driveItem)
    {
        if (is_numeric($driveItem)) {
            $driveItemId = $driveItem;
            /** @var File $driveItem */
            $driveItem = $this->getEntityManager()->find(File::getEntityName(), $driveItemId);
        }

        $workspaceUserIds = $this->getWorkspaceService()->getWorkspaceUserIds($driveItem->getWorkspace());
        $driveItemUserIds = [];
        foreach ($workspaceUserIds as $userId) {
            if ($this->getAcl()->isAllowed(new UserRole($userId), $driveItem)) {
                $driveItemUserIds[] = $userId;
            } else {
                $permission = PermissionType::getPermissionTypeBitMask(File::RESOURCE_ID, PermissionType::FILE_READ);
                if ($this->getFileService()->getFileFolderHighestPermission($driveItem, $userId) >= $permission) {
                    $driveItemUserIds[] = $userId;
                }
            }
        }

        return $driveItemUserIds;
    }

    /**
     * @param $name
     * @param Workspace $workspace
     * @param Folder|null $folder
     * @param null $excludedId
     * @return mixed|null
     */
    public function getByName($name, Workspace $workspace, Folder $folder = null, $excludedId = null)
    {
        $em = $this->getEntityManager();
        $sql = "SELECT id, parent, type, name, hash, etag, size,".
            " DATE_FORMAT(modified_at, '%Y-%m-%dT%TZ') as modified_at,".
            " DATE_FORMAT(content_modified_at, '%Y-%m-%dT%TZ') as content_modified_at" .
            " FROM files where LOWER(name) = LOWER(?) COLLATE utf8_bin" .
            " AND is_deleted != 1 AND is_trashed != 1 AND type != 'smart_folder'";
        if ($workspace != null) {
            $sql .= ' AND workspace_id = ' . $workspace->getId();
        }
        if ($excludedId !== null) {
            $sql .= ' AND id != ' . $excludedId;
        }

        if (empty($folder) || $folder->getId() === FolderService::ROOT_FOLDER_ID) {
            $sql .= ' AND parent IS NULL';
        } else {
            $sql .= ' AND parent = '.$folder->getId();
        }

        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute(array($name));

        $result = $stmt->fetch();
        if ($result) {
            $file = $this->getEntityManager()->getReference(File::ENTITY_NAME, $result['id']);
        } else {
            $file = null;
        }
        return $file;
    }

    /**
     * @param $name
     * @param Workspace $workspace
     * @param Folder|null $folder
     * @param null $excludedId
     * @return bool
     */
    public function checkNameExists($name, Workspace $workspace, Folder $folder = null, $excludedId = null)
    {
        $file = $this->getByName($name, $workspace, $folder, $excludedId);

        if ($file) {
            return true;
        }

        return false;
    }

    /**
     * @return PermissionService
     */
    protected function getPermissionService()
    {
        if (!$this->permissionService) {
            $this->permissionService = $this->getContainer()->get('PermissionService');
        }
        return $this->permissionService;
    }

    /**
     * @return WorkspaceService
     */
    protected function getWorkspaceService()
    {
        if (!$this->workspaceService) {
            $this->workspaceService = $this->getContainer()->get('WorkspaceService');
        }
        return $this->workspaceService;
    }

    /**
     * @return UserService
     */
    protected function getUserService()
    {
        if (!$this->userService) {
            $this->userService = $this->getContainer()->get('UserService');
        }
        return $this->userService;
    }

    /**
     * @return FileService
     */
    private function getFileService()
    {
        if (!$this->fileService) {
            $this->fileService = $this->getContainer()->get('FileService');
        }
        return $this->fileService;
    }

    /**
     * @return Acl
     */
    protected function getAcl()
    {
        return $this->getContainer()->get('acl');
    }

    /**
     * @return AuthenticationService
     */
    protected function getAuth()
    {
        return $this->getContainer()->get('auth');
    }
}
