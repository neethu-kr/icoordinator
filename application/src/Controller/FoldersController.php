<?php

namespace iCoordinator\Controller;

use iCoordinator\Config\Route\FoldersRouteConfig;
use iCoordinator\Controller\Helper\FilesControllerHelper;
use iCoordinator\Entity\Error;
use iCoordinator\Entity\File;
use iCoordinator\Permissions\PermissionType;
use iCoordinator\Permissions\Privilege\FilePrivilege;
use iCoordinator\Permissions\Privilege\WorkspacePrivilege;
use iCoordinator\Permissions\Role\GuestRole;
use iCoordinator\Permissions\Role\UserRole;
use iCoordinator\Service\Exception\NotTrashedException;
use iCoordinator\Service\FolderService;
use iCoordinator\Service\PermissionService;
use iCoordinator\Service\UserService;
use iCoordinator\Service\WorkspaceService;
use Slim\Http\Request;
use Slim\Http\Response;

class FoldersController extends AbstractRestController
{
    public function init()
    {
        $this->addHelper(new FilesControllerHelper());
    }

    /**
     * GET /folders/{folder_id}
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function getFolderAction(Request $request, Response $response, $args)
    {
        $folder = $request->getAttribute('folder');

        return $response->withJson($folder);
    }

    public function getVisibleFolderChildren($userId, $entries, $isDesktopClient)
    {
        $visibleEntries = array();
        $selSyncinherited = false;
        $selSyncInheritanceTested = false;
        $selectiveSync = null;

        $selectiveSyncService = $this->getSelectiveSyncService();

        $selSyncData = $selectiveSyncService->getSelectiveSyncFileIds($userId);

        foreach ($entries as $index => $entry) {
            $type = $entry->getType();
            if ($type == 'file' || $type == 'folder') {
                if ($selSyncData != null && !$selSyncinherited) {
                    $selectiveSync = $selectiveSyncService->getSelectiveSync(
                        $entry,
                        $userId,
                        $selSyncInheritanceTested,
                        $selSyncData
                    );
                    if ($selectiveSync && $selectiveSync->getInherited()) {
                        $selSyncinherited = true;
                    } else {
                        $selSyncInheritanceTested = true;
                    }
                }
                if ($isDesktopClient) {
                    if ($selectiveSync) {
                    } else {
                        array_push($visibleEntries, $entry);
                    }
                } else {
                    array_push($visibleEntries, $entry);
                }
            } else {
                array_push($visibleEntries, $entry);
            }
        }
        return $visibleEntries;
    }
    /**
     * GET /folders/{folder_id}/children
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function getFolderChildrenAction(Request $request, Response $response, $args)
    {
        $folder = $request->getAttribute('folder');
        $types  = $this->getTypesFilterValue($request);

        $acl        = $this->getAcl();
        $userId     = $this->getAuth()->getIdentity();
        $role       = $this->getRole($request);

        $offset = $request->getParam('offset', 0);
        $limit = $request->getParam('limit', FolderService::FOLDER_CHILDREN_LIMIT_DEFAULT);
        $nextOffset = $offset + $limit;

        $paginator = $this->getFolderService()->getFolderChildrenAvailableForUser(
            $folder,
            $userId,
            $limit,
            $offset,
            $types
        );
        $hasMore = ($paginator->count() > $nextOffset);
        $entries = $paginator->getIterator()->getArrayCopy();

        $visibleEntries = array();
        $emailOptionsInterited = false;
        $emailOptionsInheritanceTested = false;
        $selSyncinherited = false;
        $selSyncInheritanceTested = false;
        $selectiveSync = null;
        $fileEmailOption = null;

        $selectiveSyncService = $this->getSelectiveSyncService();
        $fileEmailOptionsService = $this->getFileEmailOptionsService();
        $isDesktopClient = $this->isDesktopClient($request);

        $selSyncData = $selectiveSyncService->getSelectiveSyncFileIds($userId);
        $fileEmailOptionData = $fileEmailOptionsService->getFileEmailOptionsFileIds($userId);
        foreach ($entries as $index => $entry) {
            $type = $entry->getType();
            if ($type == 'file' || $type == 'folder') {
                if ($selSyncData != null && !$selSyncinherited) {
                    $selectiveSync = $selectiveSyncService->getSelectiveSync(
                        $entry,
                        $userId,
                        $selSyncInheritanceTested,
                        $selSyncData
                    );
                    if ($selectiveSync && $selectiveSync->getInherited()) {
                        $selSyncinherited = true;
                    } else {
                        $selSyncInheritanceTested = true;
                    }
                }
                if ($isDesktopClient) {
                    if ($selectiveSync) {
                    } else {
                        if ($type == 'file') {
                            $fileVersion = $this->getFileService()->getLatestFileVersion($entry->getId());
                            if ($fileVersion) {
                                $entry->setVersionComment($fileVersion['comment']);
                                $modifiedBy = $this->getUserService()->getUser($fileVersion['modified_by']);
                                $entry->setModifiedBy($modifiedBy);
                                $entry->setVersionCreatedAt($fileVersion['created_at']);
                            }
                        }
                        array_push($visibleEntries, $entry);
                    }
                } else {
                    if ($fileEmailOptionData != null) {
                        if (!$emailOptionsInterited) {
                            $fileEmailOption = $fileEmailOptionsService->getFileEmailOptions(
                                $entry,
                                $userId,
                                $emailOptionsInheritanceTested,
                                $fileEmailOptionData
                            );
                            $entry->setFileEmailOptions($fileEmailOption);
                            if ($fileEmailOption && $fileEmailOption->isInherited()) {
                                $emailOptionsInterited = true;
                            } else {
                                $emailOptionsInheritanceTested = true;
                            }
                        } else {
                            $entry->setFileEmailOptions($fileEmailOption);
                        }
                    }
                    if ($selectiveSync) {
                        $entry->setSelectiveSync(
                            array(
                                'created_at' => $selectiveSync->getCreatedAt(),
                                'inherited' => $selSyncinherited
                            )
                        );
                    }
                    if ($type == 'file') {
                        $fileVersion = $this->getFileService()->getLatestFileVersion($entry->getId());
                        if ($fileVersion) {
                            $entry->setVersionComment($fileVersion['comment']);
                            $modifiedBy = $this->getUserService()->getUser($fileVersion['modified_by']);
                            $entry->setModifiedBy($modifiedBy);
                            $entry->setVersionCreatedAt($fileVersion['created_at']);
                        }
                    }
                    array_push($visibleEntries, $entry);
                }
            } else {
                array_push($visibleEntries, $entry);
            }
        }

        $result = array(
            'has_more' => $hasMore,
            'next_offset' => ($hasMore) ? $nextOffset : null,
            'entries' => $visibleEntries
        );

        return $response->withJson($result);
    }

    /**
     * GET /workspaces/{workspace_id}/root-folder/children
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function getRootFolderChildrenAction(Request $request, Response $response, $args)
    {
        $workspace  = $request->getAttribute('workspace');
        $types      = $this->getTypesFilterValue($request);
        if ($types == null) {
            $types = array();
        }
        $acl        = $this->getAcl();
        $userId     = $this->getAuth()->getIdentity();
        $role       = $this->getRole($request);

        $offset = $request->getParam('offset', 0);
        $limit = $request->getParam('limit', FolderService::FOLDER_CHILDREN_LIMIT_DEFAULT);
        $nextOffset = $offset + $limit;

        $folderService = $this->getFolderService();
        if ($acl->isAllowed($role, $workspace, WorkspacePrivilege::PRIVILEGE_READ_ALL_FILES) ||
            ($acl->isAllowed($role, $workspace, WorkspacePrivilege::PRIVILEGE_READ) &&
            count($types) == 1 && $types[0] == 'smart_folder')) {
            $paginator = $folderService->getRootFolderChildren($workspace, $limit, $offset, $types);
        } else {
            $paginator = $folderService->getRootFolderChildrenAvailableForUser(
                $workspace,
                $userId,
                $limit,
                $offset,
                $types
            );
        }

        $hasMore = ($paginator->count() > $nextOffset);
        $entries = $paginator->getIterator()->getArrayCopy();

        $visibleEntries = array();
        $inherited = false;
        $selectiveSync = null;
        $isDesktopClient = $this->isDesktopClient($request);
        $selSyncData = $this->getSelectiveSyncService()->getSelectiveSyncFileIds($userId);
        $fileEmailOptionsService = $this->getFileEmailOptionsService();
        $fileEmailOptionData = $fileEmailOptionsService->getFileEmailOptionsFileIds($userId);

        foreach ($entries as $index => $entry) {
            $type = $entry->getType();
            if ($type == 'file' || $type == 'folder') {
                if ($selSyncData != null && !$inherited) {
                    $selectiveSync = $this->getSelectiveSyncService()->getSelectiveSync(
                        $entry,
                        $userId,
                        false,
                        $selSyncData
                    );
                    if ($selectiveSync && $selectiveSync->getInherited()) {
                        $inherited = true;
                    }
                }
                if ($isDesktopClient) {
                    if ($selectiveSync) {
                    } else {
                        if ($type == 'file') {
                            $fileVersion = $this->getFileService()->getLatestFileVersion($entry->getId());
                            if ($fileVersion) {
                                $entry->setVersionComment($fileVersion['comment']);
                                $modifiedBy = $this->getUserService()->getUser($fileVersion['modified_by']);
                                $entry->setModifiedBy($modifiedBy);
                                $entry->setVersionCreatedAt($fileVersion['created_at']);
                            }
                        }
                        array_push($visibleEntries, $entry);
                    }
                } else {
                    if ($fileEmailOptionData != null) {
                        $entry->setFileEmailOptions(
                            $fileEmailOptionsService->getFileEmailOptions(
                                $entry,
                                $userId,
                                false,
                                $fileEmailOptionData
                            )
                        );
                    }
                    if ($selectiveSync) {
                        $entry->setSelectiveSync(
                            array(
                                'created_at' => $selectiveSync->getCreatedAt(),
                                'inherited' => $inherited
                            )
                        );
                    }
                    if ($type == 'file') {
                        $fileVersion = $this->getFileService()->getLatestFileVersion($entry->getId());
                        if ($fileVersion) {
                            $entry->setVersionComment($fileVersion['comment']);
                            $modifiedBy = $this->getUserService()->getUser($fileVersion['modified_by']);
                            $entry->setModifiedBy($modifiedBy);
                            $entry->setVersionCreatedAt($fileVersion['created_at']);
                        }
                    }
                    array_push($visibleEntries, $entry);
                }
            } else {
                array_push($visibleEntries, $entry);
            }
        }

        $result = array(
            'has_more' => $hasMore,
            'next_offset' => ($hasMore) ? $nextOffset : null,
            'entries' => $visibleEntries
        );

        return $response->withJson($result);
    }

    /**
     * GET /workspaces/{workspace_id}/trash/children
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function getTrashFolderChildrenAction(Request $request, Response $response, $args)
    {
        $workspace  = $request->getAttribute('workspace');
        $types      = $this->getTypesFilterValue($request);

        $acl        = $this->getAcl();
        $userId     = $this->getAuth()->getIdentity();
        $role       = $this->getRole($request);

        $offset = $request->getParam('offset', 0);
        $limit = $request->getParam('limit', FolderService::FOLDER_CHILDREN_LIMIT_DEFAULT);
        $nextOffset = $offset + $limit;

        $folderService = $this->getFolderService();
        if ($acl->isAllowed($role, $workspace, WorkspacePrivilege::PRIVILEGE_READ_ALL_FILES)) {
            $paginator = $folderService->getTrashFolderChildren($workspace, $limit, $offset, $types);
        } else {
            $paginator = $folderService->getTrashFolderChildrenAvailableForUser(
                $userId,
                $workspace,
                $limit,
                $offset,
                $types
            );
        }

        $hasMore = ($paginator->count() > $nextOffset);

        $result = array(
            'has_more' => $hasMore,
            'next_offset' => ($hasMore) ? $nextOffset : null,
            'entries' => $paginator->getIterator()->getArrayCopy()
        );

        return $response->withJson($result);
    }

    /**
     * GET /folders/<ID>/permission
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function getFolderPermissionAction(Request $request, Response $response, $args)
    {
        $folderId = $args['folder_id'];

        $acl = $this->getAcl();
        $auth = $this->getAuth();

        if (!$folderId) {
            return $response->withStatus(self::STATUS_BAD_REQUEST);
        }

        $folderService = $this->getContainer()->get('FolderService');
        $folder = $folderService->getFolder($folderId);
        if (!$folder) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $role = new UserRole($auth->getIdentity());
        if ($acl->isAllowed($role, $folder, FilePrivilege::PRIVILEGE_CREATE_FILES)) {
            $result = array(
                'actions' => "edit"
            );
        } elseif ($acl->isAllowed($role, $folder, FilePrivilege::PRIVILEGE_READ)) {
            $result = array(
                'actions' => "read"
            );
        } else {
            $highest = $this->getFileService()->getFileFolderHighestPermission($folder);
            $editPermission = PermissionType::getPermissionTypeBitMask(File::RESOURCE_ID, PermissionType::FILE_EDIT);
            $readPermission = PermissionType::getPermissionTypeBitMask(File::RESOURCE_ID, PermissionType::FILE_READ);
            $grantEditPermission = PermissionType::getPermissionTypeBitMask(
                File::RESOURCE_ID,
                PermissionType::FILE_GRANT_EDIT
            );
            $grantReadPermission = PermissionType::getPermissionTypeBitMask(
                File::RESOURCE_ID,
                PermissionType::FILE_GRANT_READ
            );

            if ($highest & ($editPermission | $grantEditPermission)) {
                $result = array(
                    'actions' => "edit"
                );
            } elseif ($highest & ($readPermission | $grantReadPermission)) {
                $result = array(
                    'actions' => "read"
                );
            } else {
                $result = array(
                    'actions' => "none"
                );
            }
        }
        if ($this->isDesktopClient($request)) {
            if ($this->getSelectiveSyncService()->getSelectiveSync($folder, $auth->getIdentity())) {
                $result = array(
                    'actions' => "none"
                );
            }
        }
        return $response->withJson($result);
    }

    /**
     * DELETE /folders/{folder_id}
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function deleteFolderAction(Request $request, Response $response, $args)
    {
        ini_set('max_execution_time', 0);
        return $this->deleteFolder($request, $response, true);
    }

    /**
     * DELETE /folders/{folder_id}/trash
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function trashFolderAction(Request $request, Response $response, $args)
    {
        ini_set('max_execution_time', 0);
        return $this->deleteFolder($request, $response, false);
    }

    /**
     * PUT /folders/{folder_id}
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     * @throws \Exception
     */
    public function updateFolderAction(Request $request, Response $response, $args)
    {
        $folder = $request->getAttribute('folder');
        $data   = $request->getParsedBody();

        $userId = $this->getAuth()->getIdentity();
        $role   = $this->getRole($request);
        $acl    = $this->getAcl();

        if (!$folder) {
            return $response->withStatus(self::STATUS_BAD_REQUEST);
        }

        if ($folder->getIsDeleted() || $folder->getIsTrashed()) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $eTag = $request->getHeaderLine('If-Match');
        if ($eTag && $eTag != $folder->getEtag()) {
            return $response->withStatus(self::STATUS_PRECONDITION_FAILED);
        }

        //check if can move to other folder
        if (!empty($data['parent'])) {
            $newParentId = $data['parent']['id'];
            $parentId = ($folder->getParent() != null ? $folder->getParent()->getId() : null);
            if ($newParentId != $parentId) {
                if ((int)$newParentId == 0) {
                    $privilege = WorkspacePrivilege::PRIVILEGE_CREATE_FOLDERS;
                    if (!$this->getAcl()->isAllowed($role, $folder->getWorkspace(), $privilege)) {
                        return $response->withStatus(self::STATUS_FORBIDDEN);
                    }
                } else {
                    $privilege = FilePrivilege::PRIVILEGE_CREATE_FOLDERS;
                    $helperResponse = $this->getHelper(FilesControllerHelper::HELPER_ID)
                        ->checkParentFolderAccess($data['parent'], $role, $privilege, $response);
                    if ($helperResponse instanceof Response) {
                        return $helperResponse;
                    }
                }
                $privilege = FilePrivilege::PRIVILEGE_MODIFY;
                if (!$acl->isAllowed($role, $folder, $privilege)) {
                    $editPermission = PermissionType::getPermissionTypeBitMask(
                        File::RESOURCE_ID,
                        PermissionType::FILE_EDIT
                    );
                    $grantEditPermission = PermissionType::getPermissionTypeBitMask(
                        File::RESOURCE_ID,
                        PermissionType::FILE_GRANT_EDIT
                    );
                    $highestPermission = $this->getFileService()->getFileFolderHighestPermission($folder);
                    if (!($highestPermission & ($editPermission | $grantEditPermission))) {
                        return $response->withStatus(AbstractRestController::STATUS_FORBIDDEN);
                    }
                }
            }
            if ($this->isDesktopClient($request)) {
                if ($this->getSelectiveSyncService()->getSelectiveSync($newParentId, $userId)) {
                    $error = new Error(Error::ITEM_SYNC_DISABLED);
                    return $response->withJson($error, self::STATUS_FORBIDDEN);
                }
            }
        }

        if (!empty($data['shared_link'])) {
            $privilege = FilePrivilege::PRIVILEGE_CREATE_SHARED_LINK;
            if (!$acl->isAllowed($role, $folder, $privilege)) {
                $editPermission = PermissionType::getPermissionTypeBitMask(
                    File::RESOURCE_ID,
                    PermissionType::FILE_EDIT
                );
                $grantEditPermission = PermissionType::getPermissionTypeBitMask(
                    File::RESOURCE_ID,
                    PermissionType::FILE_GRANT_EDIT
                );
                $highestPermission = $this->getFileService()->getFileFolderHighestPermission($folder);
                if (!($highestPermission & ($editPermission | $grantEditPermission))) {
                    return $response->withStatus(AbstractRestController::STATUS_FORBIDDEN);
                }
            }
        }
        if ($this->isDesktopClient($request)) {
            if ($this->getSelectiveSyncService()->getSelectiveSync($folder, $userId)) {
                $error = new Error(Error::ITEM_SYNC_DISABLED);
                return $response->withJson($error, self::STATUS_FORBIDDEN);
            }
        }
        if (!empty($data['name'])) {
            $privilege = FilePrivilege::PRIVILEGE_MODIFY;
            if (!$acl->isAllowed($role, $folder, $privilege)) {
                $editPermission = PermissionType::getPermissionTypeBitMask(
                    File::RESOURCE_ID,
                    PermissionType::FILE_EDIT
                );
                $grantEditPermission = PermissionType::getPermissionTypeBitMask(
                    File::RESOURCE_ID,
                    PermissionType::FILE_GRANT_EDIT
                );
                $highestPermission = $this->getFileService()->getFileFolderHighestPermission($folder);
                if (!($highestPermission & ($editPermission | $grantEditPermission))) {
                    return $response->withStatus(AbstractRestController::STATUS_FORBIDDEN);
                }
            }
            if ($this->hasInvalidChars($data['name'])) {
                $error = new Error(Error::INVALID_CHARACTERS);
                return $response->withJson($error, self::STATUS_BAD_REQUEST);
            }
        }
        $folder = $this->getFolderService()->updateFolder($folder, $data, $userId);

        return $response->withJson($folder);
    }

    /**
     * POST /folders/{folder_id}
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     * @throws \Exception
     */
    public function restoreFolderAction(Request $request, Response $response, $args)
    {
        ini_set('max_execution_time', 0);
        $folder = $request->getAttribute('folder');
        $data   = $request->getParsedBody();

        $userId = $this->getAuth()->getIdentity();
        $role   = $this->getRole($request);

        //check if can move to other folder
        if (!empty($data['parent'])) {
            $privilege = FilePrivilege::PRIVILEGE_CREATE_FOLDERS;
            $helperResponse = $this->getHelper(FilesControllerHelper::HELPER_ID)
                ->checkParentFolderAccess($data['parent'], $role, $privilege, $response);
            if ($helperResponse instanceof Response) {
                return $helperResponse;
            }
        }

        try {
            $folder = $this->getFolderService()->restoreFolder($folder, $userId, $data);
        } catch (NotTrashedException $e) {
            return $response->withStatus(self::STATUS_METHOD_NOT_ALLOWED);
        }

        return $response->withJson($folder, self::STATUS_CREATED);
    }

    /**
     * POST /folders/{folder_id}/copy
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     * @throws \Exception
     */
    public function copyFolderAction(Request $request, Response $response, $args)
    {
        ini_set('max_execution_time', 0);
        $folder = $request->getAttribute('folder');
        $data   = $request->getParsedBody();

        $role   = $this->getRole($request);
        $userId = $this->getAuth()->getIdentity();

        //check if can move to other folder
        $parentId = 0;
        if (empty($data['parent'])) {
            $parent = $folder->getParent();
            if ($parent) {
                $parentId = $parent->getId();
            }
        } else {
            $parent = $data['parent'];
            $parentId = $parent['id'];
        }
        if ((int)$parentId == 0) {
            $privilege = WorkspacePrivilege::PRIVILEGE_CREATE_FOLDERS;
            if (!$this->getAcl()->isAllowed($role, $folder->getWorkspace(), $privilege)) {
                return $response->withStatus(self::STATUS_FORBIDDEN);
            }
        } else {
            $privilege = FilePrivilege::PRIVILEGE_CREATE_FILES;
            $helperResponse = $this->getHelper(FilesControllerHelper::HELPER_ID)
                ->checkParentFolderAccess($parent, $role, $privilege, $response);
            if ($helperResponse instanceof Response) {
                return $helperResponse;
            }
        }
        if ($this->isDesktopClient($request)) {
            if ($parentId > 0) {
                if ($this->getSelectiveSyncService()->getSelectiveSync($parentId, $userId)) {
                    $msg = new Error(Error::ITEM_SYNC_DISABLED);
                    return $response->withJson($msg, self::STATUS_FORBIDDEN);
                }
            }
        }
        $folderCopy = $this->getFolderService()->copyFolder($folder, $data, $userId);

        return $response->withJson($folderCopy);
    }

    /**
     * POST /workspaces/{workspace_id}/folders
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     * @throws \Exception
     */
    public function addFolderAction(Request $request, Response $response, $args)
    {
        $workspace  = $request->getAttribute('workspace');
        $data       = $request->getParsedBody();

        $role       = $this->getRole($request);
        $userId     = $this->getAuth()->getIdentity();

        //check if can create in parent folder
        if (!empty($data['parent']) && $data['parent']['id'] != 0) {  //Root folder requires workspace admin privileges
            $privilege = FilePrivilege::PRIVILEGE_CREATE_FOLDERS;
            $helperResponse = $this->getHelper(FilesControllerHelper::HELPER_ID)
                ->checkParentFolderAccess($data['parent'], $role, $privilege, $response);
            if ($helperResponse instanceof Response) {
                return $helperResponse;
            }
            if ($this->isDesktopClient($request)) {
                if ($this->getSelectiveSyncService()->getSelectiveSync($data['parent']['id'], $userId)) {
                    $error = new Error(Error::ITEM_SYNC_DISABLED);
                    return $response->withJson($error, self::STATUS_FORBIDDEN);
                }
            }
        } else {
            $privilege = WorkspacePrivilege::PRIVILEGE_CREATE_FOLDERS;
            if (!$this->getAcl()->isAllowed($role, $workspace, $privilege)) {
                return $response->withStatus(self::STATUS_FORBIDDEN);
            }
            if ($this->isDesktopClient($request) && !$workspace->getDesktopSync()) {
                return $response->withStatus(self::STATUS_FORBIDDEN);
            }
        }
        if ($this->hasInvalidChars($data['name'])) {
            $error = new Error(Error::INVALID_CHARACTERS);
            return $response->withJson($error, self::STATUS_BAD_REQUEST);
        }
        $folder = $this->getFolderService()->createFolder($data, $workspace, $userId);

        return $response->withJson($folder, self::STATUS_CREATED);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $permanently
     * @return Response
     */
    private function deleteFolder(Request $request, Response $response, $permanently)
    {
        $acl = $this->getAcl();
        $folder = $request->getAttribute('folder');
        $userId = $this->getAuth()->getIdentity();

        $eTag = $request->getHeaderLine('If-Match');
        if ($eTag && $eTag != $folder->getEtag()) {
            return $response->withStatus(self::STATUS_PRECONDITION_FAILED);
        }
        $role = new UserRole($userId);
        $privilege = WorkspacePrivilege::PRIVILEGE_READ;
        if (!$acl->isAllowed($role, $folder->getWorkspace(), $privilege)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }
        $privilege = FilePrivilege::PRIVILEGE_DELETE;
        if (!$acl->isAllowed($role, $folder, $privilege)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }
        if ($this->isDesktopClient($request)) {
            if ($this->getSelectiveSyncService()->getSelectiveSync($folder, $userId)) {
                $error = new Error(Error::ITEM_SYNC_DISABLED);
                return $response->withJson($error, self::STATUS_NO_CONTENT);
            }
        }
        $this->getFolderService()->deleteFolder($folder, $userId, $permanently);

        return $response->withStatus(self::STATUS_NO_CONTENT);
    }

    /**
     * GET /folders/{folder_id}/path
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function getFolderPathAction(Request $request, Response $response, $args)
    {
        $folderId = $args['folder_id'];
        $pathInfo = $this->getFileService()->getFilePath($folderId);

        return $response->withJson($pathInfo);
    }

    /**
     * Route Middleware. Checks if folder exists and if user has privileges to do actions with a folder.
     *
     * @param Request $request
     * @param Response $response
     * @param callable $next
     * @return Response
     */
    public function preDispatchFolderMiddleware(Request $request, Response $response, callable $next)
    {
        $folderId   = $request->getAttribute('route')->getArgument('folder_id');
        $privilege  = $request->getAttribute('route')->getArgument(FoldersRouteConfig::ARGUMENT_FOLDER_PRIVILEGE);

        if (!$folderId) {
            return $response->withStatus(AbstractRestController::STATUS_BAD_REQUEST);
        }

        $folder = $this->getFolderService()->getFolder($folderId);

        if (!$folder) {
            return $response->withStatus(AbstractRestController::STATUS_NOT_FOUND);
        }

        $role = $this->getRole($request);
        if ($privilege) {
            if (!$this->getAcl()->isAllowed($role, $folder, $privilege)) {
                $permission = PermissionType::getPermissionTypeBitMask(File::RESOURCE_ID, PermissionType::FILE_READ);
                if ($this->getFileService()->getFileFolderHighestPermission($folder) < $permission) {
                    return $response->withStatus(AbstractRestController::STATUS_FORBIDDEN);
                }
            }
        }

        return $next($request->withAttribute('folder', $folder), $response);
    }


    /**
     * Route Middleware. Checks if workspace exists and if user has privileges to do actions with a workspace
     *
     * @param Request $request
     * @param Response $response
     * @param callable $next
     * @return Response
     */
    public function preDispatchWorkspaceMiddleware(Request $request, Response $response, callable $next)
    {
        $workspaceId = $request->getAttribute('route')->getArgument('workspace_id');
        $privilege = $request->getAttribute('route')->getArgument(FoldersRouteConfig::ARGUMENT_WORKSPACE_PRIVILEGE);

        if (!$workspaceId) {
            return $response->withStatus(AbstractRestController::STATUS_BAD_REQUEST);
        }

        $workspace = $this->getWorkspaceService()->getWorkspace($workspaceId);

        if (!$workspace) {
            return $response->withStatus(AbstractRestController::STATUS_NOT_FOUND);
        }

        $role = $this->getRole($request);
        if ($privilege) {
            if (!$this->getAcl()->isAllowed($role, $workspace, $privilege)) {
                return $response->withStatus(AbstractRestController::STATUS_FORBIDDEN);
            }
        }

        return $next($request->withAttribute('workspace', $workspace), $response);
    }

    /**
     * @param Request $request
     * @return array|null
     */
    private function getTypesFilterValue(Request $request)
    {
        $types = $request->getParam('types');
        if ($types) {
            $types = explode(',', $types);
        }

        return $types;
    }


    /**
     * @param Request $request
     * @return GuestRole|UserRole
     * @throws \Exception
     */
    private function getRole(Request $request)
    {
        return $this->getHelper(FilesControllerHelper::HELPER_ID)->getRoleWithSharedLinkToken($request);
    }

    /**
     * @return FolderService
     */
    public function getFolderService()
    {
        return $this->getContainer()->get('FolderService');
    }

    /**
     * @return FileService
     */
    private function getFileService()
    {
        return $this->getContainer()->get('FileService');
    }

    /**
     * @return WorkspaceService
     */
    private function getWorkspaceService()
    {
        return $this->getContainer()->get('WorkspaceService');
    }

    /**
     * @return SelectiveSyncService
     */
    private function getSelectiveSyncService()
    {
        return $this->getContainer()->get('SelectiveSyncService');
    }

    /**
     * @return UserService
     */
    private function getUserService()
    {
        return $this->getContainer()->get('UserService');
    }

    /**
     * @return PermissionService
     */
    private function getPermissionService()
    {
        return $this->getContainer()->get('PermissionService');
    }

    private function getFileEmailOptionsService()
    {
        return $this->getContainer()->get('FileEmailOptionsService');
    }

    public function isDesktopClient(Request $request)
    {
        $serverParams = $request->getServerParams();
        $token = str_replace("Bearer ", "", $serverParams['HTTP_AUTHORIZATION']);
        $accessToken = $this->getContainer()['entityManager']
            ->getRepository('\iCoordinator\Entity\OAuthAccessToken')
            ->findOneBy(
                array(
                    'accessToken' => $token
                )
            );
        return ($accessToken->getClientId() == 'icoordinator_desktop');
    }

    private function hasInvalidChars($str)
    {
        return preg_match(self::INVALID_CHARACTERS, $str) || trim($str) != $str || substr($str, -1) == '.';
    }
}
