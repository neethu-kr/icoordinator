<?php

namespace iCoordinator\Service;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use iCoordinator\Entity\Acl\AclPermission;
use iCoordinator\Entity\Acl\AclResource\AclFileResource;
use iCoordinator\Entity\Event\FolderEvent;
use iCoordinator\Entity\File;
use iCoordinator\Entity\Folder;
use iCoordinator\Entity\HistoryEvent\FileHistoryEvent;
use iCoordinator\Entity\SmartFolder;
use iCoordinator\Entity\User;
use iCoordinator\Entity\Workspace;
use iCoordinator\Permissions\BitMask;
use iCoordinator\Permissions\PermissionType;
use iCoordinator\Service\Exception\ConflictException;
use iCoordinator\Service\Exception\NotFoundException;
use iCoordinator\Service\Exception\NotTrashedException;
use iCoordinator\Service\Exception\ValidationFailedException;
use iCoordinator\Service\Helper\FileServiceHelper;
use Laminas\Hydrator\ClassMethodsHydrator;

/**
 * Class FolderService
 * @package iCoordinator\Service
 */
class FolderService extends DriveItemService
{
    const FOLDER_CHILDREN_LIMIT_DEFAULT = 100;
    const ROOT_FOLDER_ID = 0;
    const WORKSPACE_CHILDREN_LIMIT_DEFAULT = 20000;
    /**
     * @var FileService
     */
    private $fileService;

    public function getWorkspaceChildrenLimit()
    {
        return self::WORKSPACE_CHILDREN_LIMIT_DEFAULT;
    }
    /**
     * @param $folderId
     * @return null|Folder
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function getFolder($folderId)
    {
        $folder = $this->getEntityManager()->find(Folder::ENTITY_NAME, $folderId);
        return $folder;
    }

    /**
     * @param $folder
     * @return array
     */
    public function getFolderUsers($folder)
    {
        return $this->getDriveItemUsers($folder);
    }

    /**
     * @param $folder
     * @return array
     */
    public function getFolderUserIds($folder)
    {
        return $this->getDriveItemUserIds($folder);
    }

    /**
     * @param $user
     * @param $folder
     * @return boolean
     */
    public function userIsAllowed($user, $folder)
    {
        return $this->isAllowed($user, $folder);
    }

    /**
     * @param array $data
     * @param $workspace
     * @param $user
     * @return Folder
     * @throws ConflictException
     * @throws NotFoundException
     * @throws ValidationFailedException
     * @throws \Doctrine\ORM\ORMException
     */
    public function createFolder(array $data, $workspace, $user)
    {
        $workspace  = $this->getWorkspace($workspace);
        $user       = $this->getUser($user);

        if (!isset($data['name']) || strlen($data['name']) < 1) {
            throw new ValidationFailedException();
        }

        $folder = new Folder();

        $parentFolder = null;
        if (!empty($data['parent'])) {
            if (!empty($data['parent']['id'])) {
                $parentFolder = FileServiceHelper::updateParentFolder(
                    $folder,
                    $data['parent'],
                    $this->getEntityManager()
                );
            }
            unset($data['parent']);
        }

        if ($this->checkNameExists($data['name'], $workspace, $parentFolder)) {
            throw new ConflictException();
        }

        $folder->setCreatedBy($user)
            ->setWorkspace($workspace);

        $data['size'] = 0;

        $hydrator = new ClassMethodsHydrator();
        $hydrator->hydrate($data, $folder);

        $this->getEntityManager()->persist($folder);

        //add permissions
        if ($folder->getParent()) {
            $folder->setOwnedBy($folder->getParent()->getOwnedBy());
        } else {
            $folder->setOwnedBy($user);
        }

        // Copy parent folder meta fields
        if ($folder->getParent()) {
            $parentMetaFieldValues = $folder->getParent()->getMetaFieldsValues();
            foreach ($parentMetaFieldValues as $value) {
                $metaFieldValue = clone $value;
                $metaFieldValue->setResource($folder);
                if (!count($folder->getMetaFieldsValuesFiltered($value->getMetaField(), $value->getValue()))) {
                    $folder->getMetaFieldsValues()->add($metaFieldValue);
                    $this->getEntityManager()->persist($metaFieldValue);
                }
            }
        }

        if ($folder->getParent() == null && !$workspace->getDesktopSync()) {
            // Disable desktop sync if root folder
            $users = $this->getWorkspaceService()->getWorkspaceUsers($workspace);
            foreach ($users as $user) {
                $this->getSelectiveSyncService()->setSelectiveSync($folder, $user);
            }
        }

        //creating event
        $this->getEventService()->addEvent(FolderEvent::TYPE_CREATE, $folder, $user, null, false);
        $this->getHistoryEventService()->addEvent(FolderEvent::TYPE_CREATE, $folder, $user, $folder->getName());

        $this->getEntityManager()->flush();

        return $folder;
    }

    /**
     * @param $workspace
     * @param $limit
     * @param $offset
     * @param null $types
     * @return Paginator
     * @throws NotFoundException
     */
    public function getRootFolderChildren($workspace, $limit, $offset, $types = null)
    {
        $workspace = $this->getWorkspace($workspace);

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select(array('f'))
            ->from(File::ENTITY_NAME, 'f')
            ->andwhere('f.is_trashed != 1')
            ->andWhere('f.is_deleted != 1')
            ->andWhere('f.workspace = :workspace')
            ->andWhere('f.parent IS NULL')
            ->setParameter('workspace', $workspace);

        return $this->finalizeAndGetPaginator($qb, $limit, $offset, $types);
    }


    /**
     * @param $workspace
     * @param $user
     * @param $limit
     * @param $offset
     * @param null $types
     * @return Paginator
     * @throws NotFoundException
     */
    public function getRootFolderChildrenAvailableForUser($workspace, $user, $limit, $offset, $types = null)
    {
        $workspace  = $this->getWorkspace($workspace);
        $user       = $this->getUser($user);

        $fileIds    = $this->getRootLevelFileIdsAvailableForUser($user, $workspace, false);

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select(array('f'))
            ->from(File::ENTITY_NAME, 'f')
            ->andWhere('f.id IN (:file_ids)')
            ->setParameter('file_ids', $fileIds);

        return $this->finalizeAndGetPaginator($qb, $limit, $offset, $types);
    }

    /**
     * @param $workspace
     * @return Paginator
     * @throws NotFoundException
     */
    public function getExistingWorkspaceChildren($workspace)
    {
        $em = $this->getEntityManager();
        $pdo = $em->getConnection();
        $sql = 'SELECT id from files where workspace_id='.$workspace->getId();

        $stmt = $pdo->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * @param $workspace
     * @throws NotFoundException
     */
    public function clearParentForExistingWorkspaceChildren($workspace)
    {
        $em = $this->getEntityManager();
        $pdo = $em->getConnection();
        $sql = 'UPDATE files set parent=null where workspace_id='.$workspace->getId();

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    }
    /**
     * @param $folder
     * @param $user
     * @param $limit
     * @param $offset
     * @param null $types
     * @return Paginator
     */
    public function getFolderChildrenAvailableForUser($folder, $user, $limit, $offset, $types = null)
    {
        if (!$folder instanceof Folder) {
            $folderId = $folder;
            $folder = $this->getFolder($folderId);
        }
        if (!$user instanceof User) {
            $userId = $user;
            $user = $this->getUser($userId);
        }
        if (!$this->getPermissionService()->isWorkspaceAdmin($user, $folder->getWorkspace())) {
            $fileIds = $this->getFolderFileIdsNotAvailableForUser($user, $folder, false);
        } else {
            $fileIds = 0;
        }
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select(array('f'))
            ->from(File::ENTITY_NAME, 'f')
            ->andWhere('f.is_deleted != 1')
            ->andWhere('f.parent = :parent')
            ->setParameter('parent', $folder);

        if ($fileIds) {
            $qb->andWhere('f.id NOT IN (:file_ids)');
            $qb->setParameter('file_ids', $fileIds);
        }
        if (!$folder->getIsTrashed()) {
            $qb->andWhere('f.is_trashed != 1');
        }

        return $this->finalizeAndGetPaginator($qb, $limit, $offset, $types);
    }

    /**
     * @param $workspace
     * @param $user
     * @param $limit
     * @param $offset
     * @param null $types
     * @return Paginator
     */
    public function getAllWorkspaceChildren($workspace)
    {
        if (!$workspace instanceof Workspace) {
            $workspaceId = $workspace;
            $workspace = $this->getWorkspace($workspaceId);
        }
        $lastRowId = 0;
        $result = array();
        $em = $this->getEntityManager();
        $pdo = $em->getConnection();
        do {
            $sql = "select CONCAT('{\"id\":',id,',\"parent\":',IF(parent IS NULL,0,parent)".
            ",',\"type\":\"',type,'\",\"name\":\"',name,'\"".
            ",\"hash\":',IF(hash IS NULL,'null',CONCAT('\"',hash,'\"'))".
            ",',\"version\":',etag,',\"size\":',size,'".
            ",\"modified_at\":',IF(modified_at IS NULL, 'null',".
            "CONCAT('\"',DATE_FORMAT(modified_at, '%Y-%m-%dT%TZ'),'\"'))".
            ",',\"content_modified_at\":',IF(content_modified_at IS NULL,'null',".
            "CONCAT('\"',DATE_FORMAT(content_modified_at, '%Y-%m-%dT%TZ')".
            ",'\"')),'}') as json".
            " FROM files where is_deleted != 1 AND is_trashed != 1 AND is_uploading!= 1 AND type != 'smart_folder'";
            if ($workspace != null) {
                $sql .= ' AND workspace_id = ' . $workspace->getId();
            }
            $sql .= ' AND id > ' . $lastRowId;
            $sql .= ' ORDER BY id ASC LIMIT ' . self::WORKSPACE_CHILDREN_LIMIT_DEFAULT;

            $stmt = $pdo->prepare($sql);
            $stmt->execute();

            $data = $stmt->fetchAll();
            $cnt = count($data);
            $result = array_merge($result, $data);
            if ($cnt == self::WORKSPACE_CHILDREN_LIMIT_DEFAULT) {
                $node = json_decode($data[self::WORKSPACE_CHILDREN_LIMIT_DEFAULT-1]['json']);
                $lastRowId = $node->id;
            }
            unset($data);
        } while ($cnt == self::WORKSPACE_CHILDREN_LIMIT_DEFAULT);
        return $result;
    }

    /**
     * @param $workspace
     * @param $limit
     * @param int $offset
     * @param null $types
     * @return Paginator
     * @throws NotFoundException
     * @throws \Doctrine\ORM\ORMException
     */
    public function getTrashFolderChildren($workspace, $limit, $offset = 0, $types = null)
    {
        $workspace = $this->getWorkspace($workspace);

        //select only highest levels of trashed folder trees
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select(array('f'))
            ->from(File::ENTITY_NAME, 'f')
            ->leftJoin('f.parent', 'p')
            ->where('p.is_trashed != 1')
            ->orWhere('p.is_trashed IS NULL')
            ->andWhere('f.is_trashed = 1')
            ->andWhere('f.is_deleted != 1')
            ->andWhere('f.workspace = :workspace')
            ->setParameter('workspace', $workspace);

        return $this->finalizeAndGetPaginator($qb, $limit, $offset, $types);
    }

    /**
     * @param $user
     * @param $workspace
     * @param $limit
     * @param int $offset
     * @param null $types
     * @return Paginator
     * @throws NotFoundException
     * @throws \Doctrine\ORM\ORMException
     */
    public function getTrashFolderChildrenAvailableForUser($user, $workspace, $limit, $offset = 0, $types = null)
    {
        $workspace  = $this->getWorkspace($workspace);
        $user       = $this->getUser($user);


        $fileIds    = $this->getLowerLevelFileIdsAvailableForUser($user, $workspace, true);

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select(array('f'))
            ->from(File::ENTITY_NAME, 'f')
            ->andWhere('f.id IN (:file_ids)')
            ->setParameter('file_ids', $fileIds);

        return $this->finalizeAndGetPaginator($qb, $limit, $offset, $types);
    }

    /**
     * @param int|Folder $folder
     * @param $user
     * @param bool $permanently
     * @throws NotFoundException
     * @throws \Doctrine\ORM\ORMException
     */
    public function deleteFolder($folder, $user, $permanently = false)
    {
        if (is_numeric($user)) {
            $userId = $user;
            /** @var User $user */
            $user = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        if (is_numeric($folder)) {
            $folderId = $folder;
            $folder = $this->getEntityManager()->getReference(Folder::ENTITY_NAME, $folderId);
        }
        FileServiceHelper::checkIfLocked($folder, $user, $this->getLockService(), $this->getUserService());

        $this->deleteFolderRecursive($folder, $user, $permanently);

        //creating event
        $this->getEventService()->addEvent(FolderEvent::TYPE_DELETE, $folder, $user, null, false);
        $this->getHistoryEventService()->addEvent(FolderEvent::TYPE_DELETE, $folder, $user, $folder->getName());

        if ($this->getAutoCommitChanges()) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param Folder $folder
     * @param $user
     * @param bool $permanently
     * @throws NotFoundException
     */
    private function deleteFolderRecursive(Folder $folder, $user, $permanently = false)
    {
        foreach ($folder->getChildren() as $child) {
            if ($child instanceof Folder) {
                $this->deleteFolderRecursive($child, $user, $permanently);
            } elseif ($child instanceof File) {
                $this->getFileService()->deleteFile($child, $user, $permanently, false, true);
            }
        }

        if ($permanently) {
            $folder->setIsDeleted(true);
        } else {
            $folder->setIsTrashed(true);
        }

        try {
            $this->getEntityManager()->merge($folder);
        } catch (EntityNotFoundException $e) {
            throw new NotFoundException();
        }
    }

    /**
     * @return FileService
     */
    public function getFileService()
    {
        if (!$this->fileService) {
            $this->fileService = $this->getContainer()->get('FileService');
        }
        return $this->fileService;
    }

    /**
     * @return SelectiveSyncService
     */
    public function getSelectiveSyncService()
    {
        if (!$this->selectiveSyncService) {
            $this->selectiveSyncService = $this->getContainer()->get('SelectiveSyncService');
        }
        return $this->selectiveSyncService;
    }

    /**
     * @param $folder
     * @param array $data
     * @param $user
     * @return null|object
     * @throws ConflictException
     * @throws NotFoundException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     * @TODO: should not be possible to change workspace
     * @TODO: should not be possible to move parent folder into the child folder
     */
    public function updateFolder($folder, array $data, $user)
    {
        $events = array();
        $historyEvents = array();
        $user = $this->getUser($user);

        if (is_numeric($folder)) {
            $folder = $this->getFolder($folder);
        }

        if (array_key_exists('lock', $data)) {
            $fileServiceHelper = new FileServiceHelper();
            $fileServiceHelper->checkIfLockedAdmin(
                $folder,
                $user,
                $this->getLockService(),
                $this->getUserService(),
                $this->getAcl()
            );
            FileServiceHelper::updateFileLock($folder, $data['lock'], $user, $this->getLockService());
            if ($data['lock']) {
                $historyEvents[] = array(FileHistoryEvent::TYPE_FOLDER_LOCK, $folder, $user, $folder->getName());
            } else {
                $historyEvents[] = array(FileHistoryEvent::TYPE_FOLDER_UNLOCK, $folder, $user, $folder->getName());
            }
            unset($data['lock']);
        } else {
            FileServiceHelper::checkIfLocked($folder, $user, $this->getLockService(), $this->getUserService());
        }
        if (isset($data['parent'])) {
            $newParentId = $data['parent']['id'];
            if ($newParentId != 0) {
                $newParentFolder = $this->getEntityManager()->find(Folder::ENTITY_NAME, $newParentId);
                if (!$newParentFolder) {
                    throw new NotFoundException();
                }
            } else {
                $newParentFolder = null;
            }
            $parentId = ($folder->getParent() != null ? $folder->getParent()->getId() : null);

            if ($newParentId != $parentId) {
                if ($this->checkNameExists(
                    (!isset($data['name']) || strlen($data['name']) < 1) ?  $folder->getName() : $data['name'],
                    $folder->getWorkspace(),
                    $newParentFolder,
                    $folder->getId()
                )) {
                    throw new ConflictException();
                }
                $parentFolder = FileServiceHelper::updateParentFolder(
                    $folder,
                    $data['parent'],
                    $this->getEntityManager()
                );
                // Only create event if parent has actually changed for folder
                $events[] = array(FolderEvent::TYPE_MOVE, $folder, $user, null, false, null);
                $description = $parentId . ':' . $newParentId;
                $historyEvents[] = array(FolderEvent::TYPE_MOVE, $folder, $user, $description);
            } else {
                $parentFolder = $folder->getParent();
            }
            if ($parentFolder) {
                unset($data['parent']);
            } else {
                $data['parent'] = null;
            }
        } else {
            $parentFolder = $folder->getParent();
        }

        if ($parentFolder) {
            if ($folder->getWorkspace()->getId() != $parentFolder->getWorkspace()->getId()) {
                $folder->setWorkspace($parentFolder->getWorkspace());
            }
        }
        if (isset($data['shared_link'])) {
            $sharedLinkService = $this->getSharedLinkService();
            FileServiceHelper::updateSharedLink($folder, $data['shared_link'], $user, $sharedLinkService);
            unset($data['shared_link']);
        }

        if (isset($data['name']) && strlen($data['name']) > 0) {
            if ($this->checkNameExists($data['name'], $folder->getWorkspace(), $parentFolder, $folder->getId())) {
                throw new ConflictException();
            }
            // Only create event if name has actually changed
            if ($data['name'] != $folder->getName()) {
                // Using name temporary to pass name change information to history events
                // will be hydrated to new name down below
                $events[] = array(FolderEvent::TYPE_RENAME, $folder, $user, null, true, $folder->getName());
            }
        }

        if (!empty($data)) {
            $hydrator = new ClassMethodsHydrator();
            $hydrator->hydrate($data, $folder);

            $folder->setEtag($folder->getEtag() + 1);
        }

        if ($this->getAutoCommitChanges()) {
            $this->getEntityManager()->flush();
        }
        $this->addHistoryEvents($historyEvents);
        $this->addEvents($events);
        $this->getEntityManager()->flush();
        return $folder;
    }

    public function restoreFolder($folder, $user, array $data = null)
    {
        $user = $this->getUser($user);

        if (is_numeric($folder)) {
            $folder = $this->getFolder($folder);
        }

        if (!$folder->getIsTrashed()) {
            throw new NotTrashedException();
        }

        if (isset($data['parent'])) {
            $parentFolder = FileServiceHelper::updateParentFolder(
                $folder,
                $data['parent'],
                $this->getEntityManager()
            );
            $this->getEventService()->addEvent(FolderEvent::TYPE_MOVE, $folder, $user);
            unset($data['parent']);
        } else {
            $parentFolder = $folder->getParent();
            if ($parentFolder->getIsTrashed() || $parentFolder->getIsDeleted()) {
                throw new ConflictException();
            }
        }

        if (isset($data['name']) && strlen($data['name']) > 0) {
            $folder->setName($data['name']);
        }

        if ($this->checkNameExists($folder->getName(), $folder->getWorkspace(), $parentFolder)) {
            throw new ConflictException();
        }

        $this->restoreFolderRecursive($folder, $user);

        if ($this->getAutoCommitChanges()) {
            $this->getEntityManager()->flush();
        }

        return $folder;
    }

    private function restoreFolderRecursive(Folder $folder, $user)
    {
        $this->getEventService()->addEvent(FolderEvent::TYPE_CREATE, $folder, $user);

        $children = $folder->getChildren();
        $keys = array_reverse($children->getKeys());
        $folder->setIsTrashed(false);
        foreach ($keys as $key) {
            $child = $children->get($key);
            if ($child != null) {
                if ($this->checkNameExists($child->getName(), $child->getWorkspace(), $folder)) {
                    // Latest copy already created
                } else {
                    if ($child instanceof Folder) {
                        $this->restoreFolderRecursive($child, $user);
                    } elseif ($child instanceof File) {
                        if ($child->getIsTrashed()) {
                            $this->getFileService()->restoreFile($child, $user, null, true);
                        }
                    }
                }
            }
        }

        try {
            $this->getEntityManager()->merge($folder);
            $this->getEntityManager()->flush();
        } catch (EntityNotFoundException $e) {
            throw new NotFoundException();
        }
    }

    public function copyFolder($folder, array $data, $user)
    {
        if (is_numeric($user)) {
            $userId = $user;
            /** @var User $user */
            $user = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        if (is_numeric($folder)) {
            $folderId = $folder;
            $folder = $this->getEntityManager()->getReference(Folder::ENTITY_NAME, $folderId);
        }

        $copyFolder = $this->copyFolderRecursive($folder, $data, $user);

        $this->getEntityManager()->flush();

        return $copyFolder;
    }

    private function copyFolderRecursive($folder, array $data, $user)
    {
        $copyFolder = clone $folder;

        if (isset($data['parent'])) {
            $parentFolder = FileServiceHelper::updateParentFolder(
                $copyFolder,
                $data['parent'],
                $this->getEntityManager()
            );
            unset($data['parent']);
        } else {
            $parentFolder = $folder->getParent();
        }

        if (isset($data['name']) && strlen($data['name']) > 0) {
            $newName = $data['name'];
        } else {
            $newName = $copyFolder->getName();
        }
        if ($this->checkNameExists($newName, $folder->getWorkspace(), $parentFolder)) {
            throw new ConflictException();
        }
        $copyFolder->setName($newName);

        try {
            $this->getEntityManager()->persist($copyFolder);
        } catch (EntityNotFoundException $e) {
            throw new NotFoundException();
        }

        //add permissions
        if ($copyFolder->getParent()) {
            $copyFolder->setOwnedBy($copyFolder->getParent()->getOwnedBy());
        } else {
            $copyFolder->setOwnedBy($user);
        }

        // copy meta fields
        $copyFolderMetaFieldsValues = array();
        $folderMetaFieldsValues =  $folder->getMetaFieldsValues();
        foreach ($folderMetaFieldsValues as $value) {
            $metaFieldValue = clone $value;
            $metaFieldValue->setResource($copyFolder);
            $this->getEntityManager()->persist($metaFieldValue);
            $copyFolderMetaFieldsValues[] = $metaFieldValue;
        }
        $copyFolder->setMetaFieldsValues($copyFolderMetaFieldsValues);

        // Copy parent folder meta fields
        if ($copyFolder->getParent()) {
            $parentMetaFieldValues = $copyFolder->getParent()->getMetaFieldsValues();
            foreach ($parentMetaFieldValues as $value) {
                $metaFieldValue = clone $value;
                $metaFieldValue->setResource($copyFolder);
                if (!count($copyFolder->getMetaFieldsValuesFiltered($value->getMetaField(), $value->getValue()))) {
                    $copyFolder->getMetaFieldsValues()->add($metaFieldValue);
                    $this->getEntityManager()->persist($metaFieldValue);
                }
            }
        }

        if ($copyFolder->getParent() == null) {
            $workspace = $copyFolder->getWorkspace();
            if (!$workspace->getDesktopSync()) {
                // Disable desktop sync if root folder
                $users = $this->getWorkspaceService()->getWorkspaceUsers($workspace);
                foreach ($users as $user) {
                    $this->getSelectiveSyncService()->setSelectiveSync($copyFolder, $user);
                }
            }
        }

        $this->getEventService()->addEvent(FolderEvent::TYPE_CREATE, $copyFolder, $user);

        foreach ($folder->getChildren() as $child) {
            if ($child instanceof Folder) {
                $this->copyFolderRecursive($child, array(
                    'parent' => $copyFolder
                ), $user, false);
            } elseif ($child instanceof File) {
                $this->getFileService()->copyFile($child, $user, array(
                    'parent' => $copyFolder
                ));
            }
        }

        return $copyFolder;
    }

    /**
     * @param $workspace
     * @param $limit
     * @param $offset
     * @param null $types
     * @return Paginator
     * @throws NotFoundException
     */
    public function getWorkspaceFileFolderCount($workspace, $countFiles = true)
    {
        $em = $this->getEntityManager();
        $sql = 'SELECT count(*) as cnt FROM files where is_trashed != 1';
        if ($countFiles) {
            $sql .= ' AND type!="smart_folder"';
        } else {
            $sql .= ' AND type="folder"';
        }
        $sql .= ' AND is_deleted != 1 AND workspace_id = '. $workspace->getId();
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();
        return $result[0]["cnt"];
    }

    public function copyWorkspaceFolderStructure(
        $workspace,
        $newWorkspace,
        $user,
        $copySettings,
        $groupMap,
        $users
    ) {
        $permissionsArray = $this->getPermissionService()->getFilePermissionsForWorkspace($workspace);
        $rootEntries = $this->getRootFolderChildren(
            $workspace,
            10000,
            0,
            $copySettings["files"] ? null : array('folder')
        )->getIterator()->getArrayCopy();
        foreach ($rootEntries as $rootEntry) {
            if ($rootEntry instanceof Folder) {
                $this->copyWorkspaceFolderRecursive(
                    $rootEntry,
                    array('workspace' => $newWorkspace,'parent' => null),
                    $user,
                    $copySettings,
                    $groupMap,
                    $permissionsArray,
                    $users
                );
            } elseif ($rootEntry instanceof SmartFolder) {
                // Ugly fix to ignore smart folders since comma separated $types
                // array does not work in getRootFolderChildren
            } elseif ($copySettings["files"] && $rootEntry instanceof File) {
                $this->getFileService()->copyWorkspaceFile(
                    $rootEntry,
                    array(
                        'parent' => null,
                        'workspace' => $newWorkspace
                    ),
                    $user,
                    $copySettings,
                    $groupMap,
                    $permissionsArray,
                    $users
                );
            }
        }
    }
    private function copyWorkspaceFolderRecursive(
        $folder,
        $data,
        $user,
        $copySettings,
        $groupMap,
        $permissionsArray,
        $users
    ) {
        if ($folder->getIsTrashed() || $folder->getIsDeleted()) {
            return null;
        }
        $copyFolder = clone $folder;
        $copyFolder->setParent($data['parent']);
        $copyFolder->setWorkspace($data['workspace']);
        try {
            $this->getEntityManager()->persist($copyFolder);
        } catch (EntityNotFoundException $e) {
            throw new NotFoundException();
        }
        $this->getEntityManager()->flush();
        //add permissions
        if ($copyFolder->getParent()) {
            $copyFolder->setOwnedBy($copyFolder->getParent()->getOwnedBy());
        } else {
            $copyFolder->setOwnedBy($user);
        }
        $this->getEventService()->addEvent(FolderEvent::TYPE_CREATE, $copyFolder, $user);
        if ($copySettings["permissions"]) {
            if (isset($permissionsArray[$folder->getId()])) {
                $permissions = $permissionsArray[$folder->getId()];
                $newResource = new AclFileResource();
                $newResource->setFile($copyFolder);
                $copyFolder->setAclResource($newResource);
                try {
                    $this->getEntityManager()->persist($newResource);
                    $this->getEntityManager()->persist($copyFolder);
                } catch (EntityNotFoundException $e) {
                    throw new NotFoundException();
                }
                $this->getEntityManager()->flush();
                foreach ($permissions as $permission) {
                    $bitMask = new BitMask('file');
                    $actions = $bitMask->getPermissions($permission['bit_mask']);
                    if ($permission['role_entity_type'] == "group") {
                        if (array_key_exists($permission['role_entity_id'], $groupMap)) {
                            $newGroup = $groupMap[$permission['role_entity_id']];
                            $this->getPermissionService()->addPermission(
                                $copyFolder,
                                $newGroup,
                                $actions,
                                $user,
                                $copyFolder->getWorkspace()->getPortal()
                            );
                        }
                    } else {
                        $this->getPermissionService()->addPermission(
                            $copyFolder,
                            $this->getUserService()->getUser($permission['role_entity_id']),
                            $actions,
                            $user,
                            $copyFolder->getWorkspace()->getPortal()
                        );
                    }
                }
            }
        }

        if ($copySettings["labels"]) {
            // copy meta fields
            $copyFolderMetaFieldsValues = array();
            $folderMetaFieldsValues = $folder->getMetaFieldsValues();
            foreach ($folderMetaFieldsValues as $value) {
                $metaFieldValue = clone $value;
                $metaFieldValue->setResource($copyFolder);
                $this->getEntityManager()->persist($metaFieldValue);
                $copyFolderMetaFieldsValues[] = $metaFieldValue;
            }
            $copyFolder->setMetaFieldsValues($copyFolderMetaFieldsValues);
        }

        if ($copySettings['desktop_sync'] == 0 && $users != null) {
            foreach ($users as $user) {
                $this->getSelectiveSyncService()->setSelectiveSync($copyFolder, $user->getId());
            }
        }
        foreach ($folder->getChildren() as $count => $child) {
            if ($child instanceof Folder) {
                $this->copyWorkspaceFolderRecursive(
                    $child,
                    array(
                        'parent' => $copyFolder,
                        'workspace' => $data['workspace']
                    ),
                    $user,
                    $copySettings,
                    $groupMap,
                    $permissionsArray,
                    null
                );
            } elseif ($copySettings["files"] && $child instanceof File) {
                $this->getFileService()->copyWorkspaceFile(
                    $child,
                    array(
                        'parent' => $copyFolder,
                        'workspace' => $data['workspace']
                    ),
                    $user,
                    $copySettings,
                    $groupMap,
                    $permissionsArray,
                    null
                );
            }
            /*if ($folder->getParent() == NULL) {
                $folderId = $folder->getId();
                $copyFolderId = $copyFolder->getId();
                $this->getEntityManager()->clear("iCoordinator\Entity\File");
                $copyFolder = $this->getFolder($copyFolderId);
                $folder = $this->getFolder($folderId);
            }*/
        }
        return $copyFolder;
    }

    /**
     * @param QueryBuilder $qb
     * @param $limit
     * @param $offset
     * @param null $types
     * @return Paginator
     */
    private function finalizeAndGetPaginator(QueryBuilder $qb, $limit, $offset, $types = null)
    {
        if ($types) {
            array_filter($types, function ($type) {
                return in_array($type, File::$types);
            });
            $qb->andWhere("f INSTANCE OF :file_type")
                ->setParameter('file_type', $types);
        }

        if ($limit !== null) {
            $qb->setFirstResult($offset)
                ->setMaxResults($limit);
        }

        $query = $qb->getQuery();
        $paginator = new Paginator($query, false);

        return $paginator;
    }

    /**
     * @param $user
     * @param $workspace
     * @param bool|false $trashed
     * @return array
     */
    private function getRootLevelFileIdsAvailableForUser($user, $workspace, $trashed = false)
    {
        $filePermissions = $this->getPermissionService()->getRootFilePermissions($user, $workspace);
        $allFileIds = [];
        $selectedFileIds = [];
        /** @var AclPermission $aclPermission */
        foreach ($filePermissions as $aclPermission) {
            /** @var File $file */
            $file = $aclPermission->getAclResource()->getFile();
            if ($file->getIsTrashed() != $trashed) {
                continue;
            }
            $allFileIds[] = $file->getId();
            if ($file->getParent()) {
            } else {
                $selectedFileIds[] = $file->getId();
            }
        }

        return $selectedFileIds;
    }
    /**
     * @param $user
     * @param $workspace
     * @param bool|false $trashed
     * @return array
     */
    private function getLowerLevelFileIdsAvailableForUser($user, $workspace, $trashed = false)
    {
        $filePermissions = $this->getPermissionService()->getFilePermissions($user, $workspace);
        $allFileIds = [];
        $selectedFileIds = [];
        $filesWithParents = [];
        /** @var AclPermission $aclPermission */
        foreach ($filePermissions as $aclPermission) {
            /** @var File $file */
            $file = $aclPermission->getAclResource()->getFile();
            if ($file->getIsTrashed() != $trashed) {
                continue;
            }
            $allFileIds[] = $file->getId();
            if ($file->getParent()) {
                $filesWithParents[] = $file;
            } else {
                $selectedFileIds[] = $file->getId();
            }
        }
        /** @var File $fileWithParent */
        foreach ($filesWithParents as $fileWithParent) {
            if (!in_array($fileWithParent->getParent()->getId(), $allFileIds)) {
                $selectedFileIds[] = $fileWithParent->getId();
            }
        }

        return $selectedFileIds;
    }

    public function getSelectedFileIds($filePermissions, $user, $trashed)
    {
        $selectedFileIds = [];
        $accessFileIds = [];
        $noPermission = PermissionType::getPermissionTypeBitMask(File::RESOURCE_ID, PermissionType::FILE_NONE);

        /** @var AclPermission $aclPermission */
        foreach ($filePermissions as $aclPermission) {
            /** @var File $file */
            $file = $aclPermission->getAclResource()->getFile();
            if ($file->getIsTrashed() != $trashed) {
                continue;
            }
            if (isset($accessFileIds[$file->getId()])) {
                if ($accessFileIds[$file->getId()]->getBitMask() < $noPermission) {
                    if ($aclPermission->getBitMask() > $accessFileIds[$file->getId()]->getBitMask() &&
                        $aclPermission->getBitMask() < $noPermission
                    ) {
                        $accessFileIds[$file->getId()] = $aclPermission;
                    }
                } elseif ($aclPermission->getBitMask() < $noPermission) {
                    $accessFileIds[$file->getId()] = $aclPermission;
                }
            } else {
                $accessFileIds[$file->getId()] = $aclPermission;
            }
        }
        foreach ($accessFileIds as $key => $access) {
            if ($access->getBitMask() == $noPermission) {
                if ($access->getAclRole()->getEntityType() == "group") {
                    $inheritedPermissions = $this->getPermissionService()->getFirstFoundPermissions(
                        $access->getAclResource()->getFile(),
                        $user
                    );
                    $highestPermission = 0;
                    foreach ($inheritedPermissions as $inheritedPermission) {
                        $thePermission = $inheritedPermission->getBitMask();
                        if ($thePermission < $noPermission && $thePermission > $highestPermission) {
                            $highestPermission = $thePermission;
                        }
                    }
                    if (!$highestPermission) {
                        $selectedFileIds[] = $key;
                    }
                } else {
                    $selectedFileIds[] = $key;
                }
            }
        }
        return $selectedFileIds;
    }
    /**
     * @param $user
     * @param $folder
     * @param bool|false $trashed
     * @return array
     */
    public function getFolderFileIdsNotAvailableForUser($user, $folder, $trashed = false)
    {
        $filePermissions = $this->getPermissionService()->getFolderFilePermissions($user, $folder);
        return $this->getSelectedFileIds($filePermissions, $user, $trashed);
    }

    /**
     * @param $user
     * @param $workspace
     * @param bool|false $trashed
     * @return array
     */
    public function getWorkspaceFileIdsAvailableForUser($user, $workspace, $trashed = false)
    {
        $fileIds = array();
        $user = $this->getUser($user);
        $filePermissions = $this->getPermissionService()->getFilePermissions($user, $workspace, false);
        $selectedFileIds = $this->getSelectedFileIds($filePermissions, $user, $trashed);
        foreach ($filePermissions as $filePermission) {
            $fileId = $filePermission->getAclResource()->getFile()->getId();
            if (in_array($fileId, $selectedFileIds)) {
                $fileIds[$fileId] = 0;
            } else {
                $fileIds[$fileId] = 1;
            }
        }
        return $fileIds;
    }

    private function getWorkspace($workspace)
    {
        if (!$workspace instanceof Workspace) {
            $workspaceId = $workspace;
            $workspace = $this->getEntityManager()->getReference(Workspace::ENTITY_NAME, $workspaceId);
            if (!$workspace) {
                throw new NotFoundException("Workspace with id {$workspaceId} not found");
            }
        }
        return $workspace;
    }

    private function getUser($user)
    {
        if (is_numeric($user)) {
            $userId = $user;
            /** @var User $user */
            $user = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
            if (!$user) {
                throw new NotFoundException("User with id {$userId} not found");
            }
        }
        return $user;
    }
}
