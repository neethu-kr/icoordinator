<?php

namespace iCoordinator\Service;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use iCoordinator\Entity\Acl\AclPermission;
use iCoordinator\Entity\Event\WorkspaceEvent;
use iCoordinator\Entity\File;
use iCoordinator\Entity\Invitation\InvitationWorkspace;
use iCoordinator\Entity\Portal;
use iCoordinator\Entity\User;
use iCoordinator\Entity\Workspace;
use iCoordinator\Permissions\PermissionType;
use iCoordinator\Service\CustomerSpecific\Norway\FDVService;
use iCoordinator\Service\Exception\NotFoundException;
use iCoordinator\Service\Exception\ValidationFailedException;
use Laminas\Hydrator\ClassMethodsHydrator;

/**
 * Class WorkspaceService
 * @package iCoordinator\Service
 */
class WorkspaceService extends AbstractService
{
    const WORKSPACES_LIMIT_DEFAULT = 500;

    /**
     * @var PermissionService
     */
    private $permissionService;

    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var PortalService
     */
    private $portalService;

    /**
     * @var EventService
     */
    private $eventService;

    /**
     * @var HistoryEventService
     */
    private $historyEventService;

    /**
     * @var GroupService
     */
    private $groupService;

    /**
     * @var FolderService
     */
    private $folderService;

    /**
 * @var SmartFolderService
 */
    private $smartFolderService;

    /**
     * @var OutboundEmailService
     */
    private $outboundEmailService;

    /**
     * @var FileService
     */
    private $fileService;

    /**
     * @var MetaFieldService
     */
    private $metaFieldService;

    /**
     * @var LockService
     */
    private $lockService;

    /**
     * @var SelectiveSyncService
     */
    private $selectiveSyncService;

    /**
     * @var EventNotificationService
     */
    private $eventNotificationService;

    /**
     * @var FileEmailOptionsService
     */
    private $fileEmailOptionsService;

    /**
     * @var FDVService
     */
    private $fdvService;

    /**
     * @var DownloadZipTokenService
     */
    private $downloadZipTokenService;
    /**
     * @param $portal
     * @param int $limit
     * @param int $offset
     * @return Paginator
     * @throws \Doctrine\ORM\ORMException
     */

    private $storage_paths = [];

    public function getWorkspaces($portal, $limit = self::WORKSPACES_LIMIT_DEFAULT, $offset = 0)
    {
        if (is_numeric($portal)) {
            $portalId = $portal;
            /** @var Portal $portal */
            $portal = $this->getEntityManager()->getReference(Portal::getEntityName(), $portalId);
        }

        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select(array('w'))
            ->from(Workspace::ENTITY_NAME, 'w')
            ->where('w.is_deleted != 1')
            ->andWhere('w.portal = :portal')
            ->setParameter('portal', $portal);

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
     * @param $portal
     * @param int $limit
     * @param int $offset
     * @return Paginator
     * @throws \Doctrine\ORM\ORMException
     */
    public function getWorkspacesAvailableForUser($user, $portal, $limit = self::WORKSPACES_LIMIT_DEFAULT, $offset = 0)
    {
        if (is_numeric($user)) {
            $userId = $user;
            /** @var User $user */
            $user = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        if (is_numeric($portal)) {
            $portalId = $portal;
            /** @var Portal $portal */
            $portal = $this->getEntityManager()->getReference(Portal::getEntityName(), $portalId);
        }

        $workspacePermissions = $this->getPermissionService()->getWorkspacePermissions($user, $portal);
        $workspaceIds = [];

        /** @var AclPermission $workspacePermission */
        foreach ($workspacePermissions as $workspacePermission) {
            $workspaceIds[] = $workspacePermission->getAclResource()->getWorkspace()->getId();
        }

        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select(array('w'))
            ->from(Workspace::ENTITY_NAME, 'w')
            ->where('w.is_deleted != 1')
            ->andWhere('w.portal = :portal')
            ->setParameter('portal', $portal)
            ->andWhere('w.id IN (:workspace_ids)')
            ->setParameter('workspace_ids', $workspaceIds);

        if ($limit !== null) {
            $qb->setFirstResult($offset)
                ->setMaxResults($limit);
        }

        $query = $qb->getQuery();
        $paginator = new Paginator($query, false);

        return $paginator;
    }

    /**
     * @param $portal
     * @return Paginator
     * @throws \Doctrine\ORM\ORMException
     */
    public function getAllWorkspacesForPortal($portal)
    {
        if (is_numeric($portal)) {
            $portalId = $portal;
            /** @var Portal $portal */
            $portal = $this->getEntityManager()->getReference(Portal::getEntityName(), $portalId);
        }

        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select(array('w'))
            ->from(Workspace::ENTITY_NAME, 'w')
            ->where('w.portal = :portal')
            ->setParameter('portal', $portal);

        $query = $qb->getQuery();
        $paginator = new Paginator($query, false);

        return $paginator;
    }

    public function getWorkspaceUsers($workspace)
    {
        $workspaceUserIds = $this->getWorkspaceUserIds($workspace);
        $users = $this->getUserService()->getUsersByIds($workspaceUserIds);

        return $users;
    }

    //TODO cache results either in Redis or in var
    public function getWorkspaceUserIds($workspace)
    {
        if (is_numeric($workspace)) {
            $workspaceId = $workspace;
            /** @var Workspace $workspace */
            $workspace = $this->getEntityManager()->find(Workspace::getEntityName(), $workspaceId);
        }

        $workspaceUserIds = $this->getPermissionService()->getResourceUserIds($workspace);
        $portalUserIds = $this->getPortalService()->getPortalUserIds($workspace->getPortal());

        $userIds = array_intersect($workspaceUserIds, $portalUserIds);

        return $userIds;
    }
    /**
     * @param $workspace
     * @return int
     */
    public function getUsedStorage($workspace)
    {
        if (is_numeric($workspace)) {
            $workspaceId = $workspace;
            /** @var Workspace $workspace */
            $workspace = $this->getEntityManager()->getReference(Workspace::ENTITY_NAME, $workspaceId);
        }
        $em = $this->getEntityManager();
        $sql = 'select sum(size) as total from workspaces w, files f where' .
            ' w.id=' . $workspace->getId() . ' and w.is_deleted=\'0\'' .
            ' and f.workspace_id=w.id and f.is_trashed=\'0\' and f.is_deleted=\'0\'';

        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();
        return $result[0]["total"];
    }
    /**
     * @return PermissionService
     */
    public function getPermissionService()
    {
        if (!$this->permissionService) {
            $this->permissionService = $this->getContainer()->get('PermissionService');
        }
        return $this->permissionService;
    }

    /**
     * @return PortalService
     */
    public function getPortalService()
    {
        if (!$this->portalService) {
            $this->portalService = $this->getContainer()->get('PortalService');
        }
        return $this->portalService;
    }

    /**
     * @return UserService
     */
    public function getUserService()
    {
        if (!$this->userService) {
            $this->userService = $this->getContainer()->get('UserService');
        }
        return $this->userService;
    }

    /**
     * @return UserService
     */
    public function getMetaFieldService()
    {
        if (!$this->metaFieldService) {
            $this->metaFieldService = $this->getContainer()->get('MetaFieldService');
        }
        return $this->metaFieldService;
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
     * @return DownloadZipTokenService
     */
    public function getDownloadZipTokenService()
    {
        if (!$this->downloadZipTokenService) {
            $this->downloadZipTokenService = $this->getContainer()->get('DownloadZipTokenService');
        }
        return $this->downloadZipTokenService;
    }

    /**
     * @param $workspaceId
     * @return null|Workspace
     */
    public function getWorkspace($workspaceId)
    {
        $workspace = $this->getEntityManager()->find(Workspace::ENTITY_NAME, $workspaceId);
        return $workspace;
    }

    /**
     * @param array $data
     * @param $user
     * @return Workspace
     * @throws Exception\ValidationFailedException
     */
    public function createWorkspace($portal, array $data, $user)
    {
        if (is_numeric($portal)) {
            $portalId = $portal;
            /** @var Portal $portal */
            $portal = $this->getEntityManager()->getReference(Portal::getEntityName(), $portalId);
        }

        if (is_numeric($user)) {
            $userId = $user;
            /** @var User $user */
            $user = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        if (empty($data['name'])) {
            throw new ValidationFailedException();
        }

        $workspace = new Workspace();
        $workspace->setPortal($portal);

        $hydrator = new ClassMethodsHydrator();
        $hydrator->hydrate($data, $workspace);

        $this->getEntityManager()->persist($workspace);

        //creating event
        $this->getEventService()->addEvent(WorkspaceEvent::TYPE_CREATE, $workspace, $user, null, false);
        $this->getHistoryEventService()->addEvent(
            WorkspaceEvent::TYPE_CREATE,
            $workspace,
            $user,
            $workspace->getName()
        );

        $this->getEntityManager()->flush();

        return $workspace;
    }
    public function exists($portal, $data)
    {
        if (is_numeric($portal)) {
            $portalId = $portal;
            /** @var Portal $portal */
            $portal = $this->getEntityManager()->getReference(Portal::getEntityName(), $portalId);
        }
        if (empty($data['name'])) {
            throw new ValidationFailedException();
        }
        $workspace = $this->getEntityManager()->getRepository(Workspace::ENTITY_NAME)->findOneBy(array(
            'portal' => $portal->getId(),
            'name' => $data['name'],
            'is_deleted' => 0
        ));

        return $workspace != null;
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
     * @return EventService
     */
    public function getHistoryEventService()
    {
        if (!$this->historyEventService) {
            $this->historyEventService = $this->getContainer()->get('HistoryEventService');
        }
        return $this->historyEventService;
    }

    /**
     * @return GroupService
     */
    public function getGroupService()
    {
        if (!$this->groupService) {
            $this->groupService = $this->getContainer()->get('GroupService');
        }
        return $this->groupService;
    }

    /**
     * @return FolderService
     */
    public function getFolderService()
    {
        if (!$this->folderService) {
            $this->folderService = $this->getContainer()->get('FolderService');
        }
        return $this->folderService;
    }

    /**
     * @return SmartFolderService
     */
    public function getSmartFolderService()
    {
        if (!$this->smartFolderService) {
            $this->smartFolderService = $this->getContainer()->get('SmartFolderService');
        }
        return $this->smartFolderService;
    }

    /**
     * @return OutboundEmailService
     */
    private function getOutboundEmailService()
    {
        if (!$this->outboundEmailService) {
            $this->outboundEmailService = $this->getContainer()->get('OutboundEmailService');
        }

        return $this->outboundEmailService;
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
     * @return EventNotificationService
     */
    private function getEventNotificationService()
    {
        if (!$this->eventNotificationService) {
            $this->eventNotificationService = $this->getContainer()->get('EventNotificationService');
        }

        return $this->eventNotificationService;
    }

    /**
     * @return FileEmailOptionsService
     */
    private function getFileEmailOptionsService()
    {
        if (!$this->fileEmailOptionsService) {
            $this->fileEmailOptionsService = $this->getContainer()->get('FileEmailOptionsService');
        }

        return $this->fileEmailOptionsService;
    }

    /**
     * @return FDVService
     */
    private function getFDVService()
    {
        if (!$this->fdvService) {
            $this->fdvService = $this->getContainer()->get('CustomerSpecific\Norway\FDVService');
        }

        return $this->fdvService;
    }
    /**
     * @param int|Workspace $workspace
     * @throws Exception\NotFoundException
     */
    public function deleteWorkspace($workspace, $user)
    {
        if (is_numeric($user)) {
            $userId = $user;
            /** @var User $user */
            $user = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        if (is_numeric($workspace)) {
            $workspaceId = $workspace;
            $workspace = $this->getEntityManager()->getReference(Workspace::ENTITY_NAME, $workspaceId);
        }

        try {
            $workspace->setIsDeleted(true);
            $this->getEntityManager()->merge($workspace);
        } catch (EntityNotFoundException $e) {
            throw new NotFoundException();
        }

        $this->getEventService()->addEvent(WorkspaceEvent::TYPE_DELETE, $workspace, $user);

        $this->getEntityManager()->flush();
    }

    private function getStoragePaths($workspace)
    {
        $em = $this->getEntityManager();
        $sql = "SELECT 
    storage_path, 
    COUNT(storage_path) as cnt
FROM
    file_versions fv, files f
WHERE fv.file_id = f.id and f.workspace_id = ?
GROUP BY storage_path
HAVING COUNT(storage_path) > 0";

        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute([$workspace->getId()]);
        $hits = $stmt->fetchAll();
        foreach ($hits as $hit) {
            $this->storage_paths[$hit["storage_path"]] = $hit["cnt"];
        }
    }

    private function permanentRemoveWorkspaceObjects($workspace)
    {
        $this->storage_paths = [];
        $this->getStoragePaths($workspace);
        do {
            $childCount = 0;
            $workspaceChildren = $this->getFolderService()->getExistingWorkspaceChildren($workspace);
            if (is_array($workspaceChildren)) {
                $childCount = count($workspaceChildren);
                $this->getFolderService()->clearParentForExistingWorkspaceChildren($workspace);
                $this->getHistoryEventService()->removeHistoryForWorkspace($workspace);
                $this->getEventNotificationService()->removeNotificationsForWorkspace($workspace);
                $this->getFDVService()->removeEntriesForWorkspace($workspace);
                $workspaceInvitations = $this->getEntityManager()
                    ->getRepository(InvitationWorkspace::ENTITY_NAME)->findBy(
                        [
                            'workspace' => $workspace
                        ]
                    );
                if (is_array($workspaceInvitations)) {
                    foreach ($workspaceInvitations as $workspaceInvitation) {
                        try {
                            $this->getEntityManager()->remove($workspaceInvitation);
                        } catch (EntityNotFoundException $e) {
                        }
                    }
                    $this->getEntityManager()->flush();
                }
                foreach ($workspaceChildren as $workspaceChild) {
                    // files, file_versions(file_id), meta_field_values(file_id),
                    // locks(file_id), shared links(file_id), selective sync(file_id), events(source_id,source_type)
                    $file = $this->getEntityManager()->getReference(File::ENTITY_NAME, $workspaceChild);
                    $this->getEntityManager()->flush();
                    if ($file) {
                        echo "Processing ".$workspace->getId().":".$file->getId().":".$file->getName()."\n";
                        $file->setVersion(null);
                        //if ($file->getType() == "file") {
                        // Removing file versions
                        $fileVersions = $this->getFileService()->getAllFileVersions($file);
                        if (is_array($fileVersions)) {
                            foreach ($fileVersions as $fileVersion) {
                                if (isset($this->storage_paths[$fileVersion->getStoragePath()])) {
                                    $cnt = $this->storage_paths[$fileVersion->getStoragePath()];
                                } else {
                                    $cnt = 0;
                                }
                                if ($cnt > 1) {
                                    echo($fileVersion->getStoragePath() . " : Count = " . $cnt . "\n");
                                    $this->storage_paths[$fileVersion->getStoragePath()] = $cnt - 1;
                                } else {
                                    $this->getFileService()->removeFileVersionPermanent($fileVersion);
                                }
                                $fileVersion->setFile(null);
                            }
                            foreach ($fileVersions as $fileVersion) {
                                $this->getEntityManager()->remove($fileVersion);
                            }
                            $this->getEntityManager()->flush();
                        }
                        //}
// Removing download zip files
                        $downloadZipTokenFiles = $this->getDownloadZipTokenService()->getZipItemsForFile($file);
                        if (is_array($downloadZipTokenFiles)) {
                            foreach ($downloadZipTokenFiles as $downloadZipTokenFile) {
                                try {
                                    $this->getEntityManager()->remove($downloadZipTokenFile);
                                } catch (EntityNotFoundException $e) {
                                    throw new NotFoundException();
                                }
                            }
                            $this->getEntityManager()->flush();
                        }
                        // Remove meta field values
                        $metaFieldValues = $file->getMetaFieldsValues();
                        if (is_array($metaFieldValues)) {
                            foreach ($metaFieldValues as $metaFieldValue) {
                                $this->getEntityManager()->remove($metaFieldValue);
                            }
                            $this->getEntityManager()->flush();
                        }
                        // Removing lock
                        $this->getLockService()->deleteLockForced($file);
                        $sharedLink = $file->getSharedLink();
                        // Removing shared link
                        $file->setSharedLink(null);
                        if ($sharedLink) {
                            try {
                                $this->getEntityManager()->remove($sharedLink);
                                $this->getEntityManager()->flush();
                            } catch (EntityNotFoundException $e) {
                            }
                        }
                        // Removing selective sync
                        $allSelectiveSync = $this->getSelectiveSyncService()->getAllSelectiveSync($file);
                        if (is_array($allSelectiveSync)) {
                            foreach ($allSelectiveSync as $selectiveSync) {
                                $selectiveSync->setFile(null);
                                try {
                                    $this->getEntityManager()->remove($selectiveSync);
                                } catch (EntityNotFoundException $e) {
                                }
                            }
                            $this->getEntityManager()->flush();
                        }
                        // Remove File email options
                        $allFileEmailOptions = $this->getFileEmailOptionsService()->getAllFileEmailOptions($file);
                        if (is_array($allFileEmailOptions)) {
                            foreach ($allFileEmailOptions as $fileEmailOption) {
                                $fileEmailOption->setFile(null);
                                try {
                                    $this->getEntityManager()->remove($fileEmailOption);
                                } catch (EntityNotFoundException $e) {
                                }
                            }
                            $this->getEntityManager()->flush();
                        }
                        if ($file->getType() == "smart_folder") {
                            $file = $this->getEntityManager()->getReference(File::ENTITY_NAME, $workspaceChild);
                            // Remove meta field criteria
                            $metaFieldCriteria = $file->getMetaFieldsCriteria();
                            if (is_array($metaFieldCriteria)) {
                                foreach ($metaFieldCriteria as $metaFieldCriterion) {
                                    $metaFieldCriterion->setSmartFolder(null);
                                    $this->getEntityManager()->remove($metaFieldCriterion);
                                }
                                $this->getEntityManager()->flush();
                            }
                        }
                    }
                    //}
                    //foreach ($workspaceChildren as $workspaceChild) {
                    //$file = $this->getEntityManager()->getReference(File::ENTITY_NAME, $workspaceChild);
                    if ($file) {
                        // Removing file permissions
                        $filePermissions = $this->getPermissionService()->getAllPermissions($file);
                        if (is_array($filePermissions)) {
                            foreach ($filePermissions as $filePermission) {
                                //$filePermission->setAclResource(null);
                                //$filePermission->setAclRole(null);
                                try {
                                    $this->getEntityManager()->remove($filePermission);
                                } catch (EntityNotFoundException $e) {
                                }
                            }
                        }
                    }
                    //}
                    //foreach ($workspaceChildren as $workspaceChild) {
                    //$file = $this->getEntityManager()->getReference(File::ENTITY_NAME, $workspaceChild);
                    if ($file) {
                        // Removing resource
                        try {
                            $resource = $file->getAclResource();
                            if ($resource) {
                                $resource->setFile(null);
                                $this->getEntityManager()->remove($resource);
                            }
                        } catch (EntityNotFoundException $e) {
                        }
                        // Set parent to null for any files/folders with this object as parent
                        $em = $this->getEntityManager();
                        $sql = "update files set parent=NULL, workspace_id = ? where parent = ?";

                        $stmt = $em->getConnection()->prepare($sql);
                        $stmt->execute([$workspace->getId(), $file->getId()]);
                        /*$files = $this->getChildrenForFile($file);
                        foreach ($files as $childFile) {
                            if ($childFile->getWorkspace() != $workspace) {
                                echo("Workspace differs for " . $childFile->getId() . "\n");
                                echo($childFile->getWorkspace()->getId() ."!=". $workspace->getId(). "\n");

                                $childFile->setWorkspace($workspace);
                            }
                            $childFile->setParent(null);
                        }*/
                        try {
                            $this->getEntityManager()->remove($file);
                            $this->getEntityManager()->flush();
                        } catch (EntityNotFoundException $e) {
                            echo $e;
                        }
                    }
                }
            }
        } while ($childCount > 0);
    }
    /**
     * @param int|Workspace $workspace
     * $param Boolean $flush
     * @throws Exception\NotFoundException
     */
    public function permanentRemoveWorkspace($workspace, $flush = true)
    {

        if (is_numeric($workspace)) {
            $workspaceId = $workspace;
            $workspace = $this->getEntityManager()->getReference(Workspace::ENTITY_NAME, $workspaceId);
        }
        if ($workspace) {
            // Removing workspace permissions
            $workspacePermissions = $this->getPermissionService()->getAllPermissions($workspace);
            if (is_array($workspacePermissions)) {
                foreach ($workspacePermissions as $workspacePermission) {
                    try {
                        $this->getEntityManager()->remove($workspacePermission);
                    } catch (EntityNotFoundException $e) {
                    }
                }
            }
            // Remove workspace object
            $this->permanentRemoveWorkspaceObjects($workspace);
            // Removing resource
            try {
                $workspaceResource = $workspace->getAclResource();
                if ($workspaceResource) {
                    //$workspaceResource->setWorkspace(null);
                    $this->getEntityManager()->remove($workspaceResource);
                }
            } catch (EntityNotFoundException $e) {
            }
            $workspace->setPortal(null);
            try {
                $this->getEntityManager()->remove($workspace);
            } catch (EntityNotFoundException $e) {
            }

            if ($flush) {
                $this->getEntityManager()->flush();
            }
        }
    }

    private function getChildrenForFile($file)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('f')
            ->from(File::ENTITY_NAME, 'f')
            ->where('f.parent = :parent')
            ->setParameter('parent', $file);

        $query = $qb->getQuery();
        return $query->getResult();
    }

    /**
     * @param $workspace
     * @param array $data
     * @param $user
     * @return bool|\Doctrine\Common\Proxy\Proxy|null|object
     * @throws Exception\NotFoundException
     */
    public function updateWorkspace($workspace, array $data, $user)
    {
        if (is_numeric($user)) {
            $userId = $user;
            /** @var User $user */
            $user = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        if (is_numeric($workspace)) {
            $workspaceId = $workspace;
            $workspace = $this->getEntityManager()->getReference(Workspace::ENTITY_NAME, $workspaceId);
        }

        $hydrator = new ClassMethodsHydrator();

        try {
            if (!empty($data['name'])) {
                // Using name temporary to pass name change information to history events
                // will be hydrated to new name down below
                $this->getEventService()->addEvent(
                    WorkspaceEvent::TYPE_RENAME,
                    $workspace,
                    $user,
                    null,
                    true,
                    $data['name']
                );
            }
            $hydrator->hydrate($data, $workspace);
            $this->getEntityManager()->merge($workspace);
            $this->getEntityManager()->flush();
        } catch (EntityNotFoundException $e) {
            throw new NotFoundException();
        }

        return $workspace;
    }

    public function workspaceFileFolderCount($workspace, $countFiles = true)
    {
        return $this->getFolderService()->getWorkspaceFileFolderCount($workspace, $countFiles);
    }
    public function copyWorkspace($workspace, array $data, $createdBy)
    {
        if (is_numeric($createdBy)) {
            $userId = $createdBy;
            /** @var User $user */
            $createdBy = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        if (is_numeric($workspace)) {
            $workspaceId = $workspace;
            $workspace = $this->getEntityManager()->getReference(Workspace::ENTITY_NAME, $workspaceId);
        }

        if (!isset($data['users'])) {
            $data['users'] = false;
        }
        if (!isset($data['groups'])) {
            $data['groups'] = false;
        }
        if (!isset($data['folders'])) {
            $data['folders'] = false;
        }
        if (!isset($data['files'])) {
            $data['files'] = false;
        }
        if (!isset($data['labels'])) {
            $data['labels'] = false;
        }
        if (!isset($data['permissions'])) {
            $data['permissions'] = false;
        }
        if (!isset($data['smartfolders'])) {
            $data['smartfolders'] = false;
        }
        $workspaceData = array('name' => $data['name'], 'desktop_sync' => $data['desktop_sync']);
        $newWorkspace = $this->createWorkspace($workspace->getPortal(), $workspaceData, $createdBy);

        if ($data['users']) {
            $users = $this->getWorkspaceUsers($workspace);
            foreach ($users as $user) {
                $permission = $this->getPermissionService()->addPermission(
                    $newWorkspace,
                    $user,
                    $this->getPermissionService()->isWorkspaceAdmin(
                        $user,
                        $workspace
                    ) ? PermissionType::WORKSPACE_ADMIN:PermissionType::WORKSPACE_ACCESS,
                    $createdBy,
                    $workspace->getPortal()
                );
            }
        }
        $groupMap = array();
        if ($data['groups']) {
            $groups = $this->getGroupService()->getWorkspaceGroups($workspace)->getIterator()->getArrayCopy();
            foreach ($groups as $group) {
                $groupData = array('name' => $group->getName());
                $groupUsers = $this->getGroupService()->getGroupUsers($group);
                $newGroup = $this->getGroupService()->createWorkspaceGroup($newWorkspace, $groupData, $createdBy);
                if ($data["users"]) {
                    foreach ($groupUsers as $groupUser) {
                        $this->getGroupService()->createGroupMembership($newGroup, $groupUser, $createdBy, true);
                    }
                }
                $groupMap[$group->getId()] = $newGroup;
            }
        }

        if ($data['folders']) {
            $this->getFolderService()->copyWorkspaceFolderStructure(
                $workspace,
                $newWorkspace,
                $createdBy,
                $data,
                $groupMap,
                $users
            );
        }

        if ($data['smartfolders']) {
            $smartFolders = $this->getFolderService()->getRootFolderChildren(
                $workspace,
                10000,
                0,
                array('smart_folder')
            )->getIterator()->getArrayCopy();
            foreach ($smartFolders as $smartFolder) {
                $newSmartFolder = clone $smartFolder;
                $newSmartFolder->setWorkspace($newWorkspace);
                $this->getEntityManager()->persist($newSmartFolder);
                $this->getEntityManager()->flush();
                $smartFolderCriteria = $smartFolder->getMetaFieldsCriteria();
                $newSmartFolderCriteria = array();
                foreach ($smartFolderCriteria as $smartFolderCriterion) {
                    $newSmartFolderCriterion = clone $smartFolderCriterion;
                    $newSmartFolderCriterion->setSmartFolder($newSmartFolder);
                    $newSmartFolderCriteria[] = $newSmartFolderCriterion;
                    $this->getEntityManager()->persist($newSmartFolderCriterion);
                }
                $this->getEntityManager()->flush();
                $newSmartFolder->setMetaFieldsCriteria($newSmartFolderCriteria);
            }
        }
        $this->getEntityManager()->flush();
        //send completion email
        $this->getOutboundEmailService()
            ->setTo($createdBy->getEmail())->setLang($createdBy->getLocale()->getLang())
            ->sendCopyWorkspaceCompleted($createdBy, $newWorkspace, getenv('BRAND'));
    }
}
