<?php

namespace iCoordinator\Controller;

use iCoordinator\Entity\File;
use iCoordinator\Entity\Folder;
use iCoordinator\Entity\Portal;
use iCoordinator\Entity\Workspace;
use iCoordinator\Permissions\PermissionType;
use iCoordinator\Service\FolderService;
use iCoordinator\Service\SelectiveSyncService;
use iCoordinator\Service\UserService;
use Slim\Http\Request;
use Slim\Http\Response;

class StateController extends FoldersController
{
    const MAX_EXECUTION_TIME = 30;
    const CHUNK_SIZE = 1048576;

    public function getClientVersion()
    {
        if (isset($_SERVER['HTTP_CLIENT_VERSION'])) {
            return substr($_SERVER['HTTP_CLIENT_VERSION'], 0, strlen("1.4"));
        } else {
            return "";
        }
    }
    private function cmp($a, $b)
    {
        return strcmp($a->getName(), $b->getName());
    }

    public function getSavedFolderTreeStateAction(Request $request, Response $response, $args)
    {

        $auth = $this->getAuth();
        $folder = $request->getAttribute('folder');
        if (!$folder instanceof Folder) {
            $folderId = $folder;
            $folder = $this->getEntityManager()->getReference(Folder::ENTITY_NAME, $folderId);
        }
        if (!$folder->getIsDeleted()) {
            $userService = $this->getContainer()->get('UserService');
            $user = $userService->getUser($auth->getIdentity());
            $fileName = $user->getUuid() . DIRECTORY_SEPARATOR . 'foldersState_' . $folder->getId();
            $uploadsStorage = $this->getFileService()->getUploadsStorage();
            $filePath = $uploadsStorage->getStreamUrl(
                $fileName
            );
            if (file_exists($filePath)) {
                $responseArray = "";
                $fp = fopen($filePath, 'r');
                if ($fp === false) {
                    return $response->withStatus(self::STATUS_NO_CONTENT);
                } else {
                    while (!feof($fp)) {
                        $responseArray .= fread($fp, self::CHUNK_SIZE);
                    }
                    fclose($fp);
                    //unlink($filePath);
                    return $response->withJson(
                        json_decode($responseArray)
                    );
                }
            } else {
                return $response->withStatus(self::STATUS_NO_CONTENT);
            }
        } else {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }
    }

    public function createFolderTreeStateAction(Request $request, Response $response, $args)
    {
        $folder = $request->getAttribute('folder');
        $clientState = $request->getParam('client_state', 0);
        $slimState = $request->getParam('slim_state', 0);
        if (!$folder instanceof Folder) {
            $folderId = $folder;
            $folder = $this->getEntityManager()->getReference(Folder::ENTITY_NAME, $folderId);
        }
        if (!$folder->getIsDeleted()) {
            $auth = $this->getAuth();
            $userService = $this->getContainer()->get('UserService');
            $user = $userService->getUser($auth->getIdentity());
            $fileName = $user->getUuid() . DIRECTORY_SEPARATOR . 'foldersState_' . $folder->getId();
            $uploadsStorage = $this->getFileService()->getUploadsStorage();
            $filePath = $uploadsStorage->getStreamUrl(
                $fileName
            );
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            $dev = getenv('DEVELOPMENT');
            $azure = getenv('AZURE');
            if ($dev == 1) {
                $prefix = '/app/';
                $xdebug = 'XDEBUG_CONFIG="idekey=createFoldersState remote_enable=1 ' .
                    'remote_host=docker.for.mac.localhost remote_port=9000 remote_log=/tmp/xdebug.log ' .
                    'remote_connect_back=0 " ';
            } else {
                if ($azure) {
                    $prefix = '/app/';
                } else {
                    $prefix = '../';
                }
                $xdebug = '';
            }
            $phpCommand = $prefix . 'bin/createFoldersState create-folders-state '
                . escapeshellarg($_SERVER["HTTP_AUTHORIZATION"]) . ' '
                . escapeshellarg($folder->getId()). ' '
                . escapeshellarg($clientState). ' '
                . escapeshellarg($slimState);
            $command = $xdebug . 'nohup ' . $phpCommand . ' > /dev/null 2>&1 & echo $!';
            exec($command, $output, $return_var);

            return $response->withJson(self::STATUS_OK);
        } else {
            return $response->withJson(self::STATUS_NOT_FOUND);
        }
    }

    public function saveFolderTreeStateAction(Request $request, Response $response, $args)
    {
        ini_set('max_execution_time', 0);
        $auth = $this->getAuth();
        $folder = $request->getAttribute('folder');
        if (!$folder instanceof Folder) {
            $folderId = $folder;
            $folder = $this->getEntityManager()->getReference(Folder::ENTITY_NAME, $folderId);
        }
        if (!$folder->getIsDeleted()) {
            $userService = $this->getContainer()->get('UserService');
            $user = $userService->getUser($auth->getIdentity());
            $fileName = $user->getUuid() . DIRECTORY_SEPARATOR . 'foldersState_' . $folder->getId();
            $uploadsStorage = $this->getFileService()->getUploadsStorage();
            $filePath = $uploadsStorage->getStreamUrl(
                $fileName
            );
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            $resp = $this->getFolderTreeStateAction($request, $response, $args);
            $streamBody = $resp->getBody();
            $streamBody->rewind();
            $responseArray = $streamBody->getContents();
            $fp = $uploadsStorage->fopen($fileName, 'wb');
            fwrite($fp, $responseArray);
            fclose($fp);
            return $response->withJson(self::STATUS_OK);
        } else {
            return $response->withJson(self::STATUS_NOT_FOUND);
        }
    }

    public function getSavedWorkspaceStateAction(Request $request, Response $response, $args)
    {

        $auth = $this->getAuth();
        $workspace = $request->getAttribute('workspace');
        if (!$workspace instanceof Workspace) {
            $workspaceId = $workspace;
            $workspace = $this->getEntityManager()->getReference(Workspace::ENTITY_NAME, $workspaceId);
        }
        if (!$workspace->getIsDeleted()) {
            $userService = $this->getContainer()->get('UserService');
            $user = $userService->getUser($auth->getIdentity());
            $fileName = $user->getUuid() . DIRECTORY_SEPARATOR . 'workspaceState_' . $workspace->getId();
            $uploadsStorage = $this->getFileService()->getUploadsStorage();
            $filePath = $uploadsStorage->getStreamUrl(
                $fileName
            );
            if (file_exists($filePath)) {
                $fp = fopen($filePath, 'r');
                if ($fp === false) {
                    return $response->withStatus(self::STATUS_NO_CONTENT);
                } else {
                    $response->withHeader('Content-Type', 'application/json');
                    while (!feof($fp)) {
                        $response->write(fread($fp, self::CHUNK_SIZE));
                    }
                    fclose($fp);
                    //unlink($filePath);
                    
                    return $response;
                }
            } else {
                return $response->withStatus(self::STATUS_NO_CONTENT);
            }
        } else {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }
    }
    public function createWorkspaceStateAction(Request $request, Response $response, $args)
    {
        $workspace = $request->getAttribute('workspace');
        $clientState = $request->getParam('client_state', 0);
        $slimState = $request->getParam('slim_state', 0);
        if (!$workspace instanceof Workspace) {
            $workspaceId = $workspace;
            $workspace = $this->getEntityManager()->getReference(Workspace::ENTITY_NAME, $workspaceId);
        }
        if (!$workspace->getIsDeleted()) {
            $auth = $this->getAuth();
            $userService = $this->getContainer()->get('UserService');
            $user = $userService->getUser($auth->getIdentity());
            $fileName = $user->getUuid() . DIRECTORY_SEPARATOR . 'workspaceState_' . $workspace->getId();
            $uploadsStorage = $this->getFileService()->getUploadsStorage();
            $filePath = $uploadsStorage->getStreamUrl(
                $fileName
            );
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            $dev = getenv('DEVELOPMENT');
            $azure = getenv('AZURE');
            if ($dev == 1) {
                $prefix = '/app/';
                $xdebug = 'XDEBUG_CONFIG="idekey=createWorkspaceState remote_enable=1 ' .
                    'remote_host=docker.for.mac.localhost remote_port=9000 remote_log=/tmp/xdebug.log ' .
                    'remote_connect_back=0 " ';
            } else {
                if ($azure) {
                    $prefix = '/app/';
                } else {
                    $prefix = '../';
                }
                $xdebug = '';
            }
            $phpCommand = $prefix . 'bin/createWorkspaceState create-workspace-state '
                . escapeshellarg($_SERVER["HTTP_AUTHORIZATION"]) . ' '
                . escapeshellarg($workspace->getId()). ' '
                . escapeshellarg($clientState). ' '
                . escapeshellarg($slimState);
            $command = $xdebug . 'nohup ' . $phpCommand . ' > /dev/null 2>&1 & echo $!';
            exec($command, $output, $return_var);

            return $response->withJson(self::STATUS_OK);
        } else {
            return $response->withJson(self::STATUS_NOT_FOUND);
        }
    }

    public function saveWorkspaceStateAction(Request $request, Response $response, $args)
    {
        ini_set('max_execution_time', 0);
        $auth = $this->getAuth();
        $workspace = $request->getAttribute('workspace');
        if (!$workspace instanceof Workspace) {
            $workspaceId = $workspace;
            $workspace = $this->getEntityManager()->getReference(Workspace::ENTITY_NAME, $workspaceId);
        }
        if (!$workspace->getIsDeleted()) {
            $userService = $this->getContainer()->get('UserService');
            $user = $userService->getUser($auth->getIdentity());
            $fileName = $user->getUuid() . DIRECTORY_SEPARATOR . 'workspaceState_' . $workspace->getId();
            $uploadsStorage = $this->getFileService()->getUploadsStorage();
            $filePath = $uploadsStorage->getStreamUrl(
                $fileName
            );
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            $resp = $this->getWorkspaceStateAction($request, $response, $args);
            $streamBody = $resp->getBody();
            $streamBody->rewind();
            $responseArray = $streamBody->getContents();
            $fp = $uploadsStorage->fopen($fileName, 'wb');
            fwrite($fp, $responseArray);
            fclose($fp);
            return $response->withJson(self::STATUS_OK);
        } else {
            return $response->withJson(self::STATUS_NOT_FOUND);
        }
    }

    public function getSavedStateAction(Request $request, Response $response, $args)
    {

        $auth = $this->getAuth();
        $userService = $this->getContainer()->get('UserService');
        $user = $userService->getUser($auth->getIdentity());
        $fileName = $user->getUuid().DIRECTORY_SEPARATOR.'state_'.$user->getUuid();
        $uploadsStorage = $this->getFileService()->getUploadsStorage();
        $filePath = $uploadsStorage->getStreamUrl(
            $fileName
        );
        if (file_exists($filePath)) {
            $fp = fopen($filePath, 'r');
            if ($fp === false) {
                return $response->withStatus(self::STATUS_NO_CONTENT);
            } else {
                $response->withHeader('Content-Type', 'application/json; charset=utf-8');
                while (!feof($fp)) {
                    $response->write(fread($fp, self::CHUNK_SIZE));
                }
                fclose($fp);
                //unlink($filePath);

                return $response;
            }
        } else {
            return $response->withStatus(self::STATUS_NO_CONTENT);
        }
    }
    public function createStateAction(Request $request, Response $response, $args)
    {
        $dev = getenv('DEVELOPMENT');
        $azure = getenv('AZURE');
        $clientState = $request->getParam('client_state', 0);
        $slimState = $request->getParam('slim_state', 0);
        $auth = $this->getAuth();
        $userService = $this->getContainer()->get('UserService');
        $user = $userService->getUser($auth->getIdentity());
        $fileName = $user->getUuid().DIRECTORY_SEPARATOR.'state_'.$user->getUuid();
        $uploadsStorage = $this->getFileService()->getUploadsStorage();
        $filePath = $uploadsStorage->getStreamUrl(
            $fileName
        );
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        if ($dev == 1) {
            $prefix = '/app/';
            $xdebug = 'XDEBUG_CONFIG="idekey=createState remote_enable=1 '.
                'remote_host=docker.for.mac.localhost remote_port=9000 remote_log=/tmp/xdebug.log '.
                'remote_connect_back=0 " ';
        } else {
            if ($azure) {
                $prefix = '/app/';
            } else {
                $prefix = '../';
            }
            $xdebug = '';
        }
        $phpCommand = $prefix . 'bin/createState create-state '
            . escapeshellarg($_SERVER["HTTP_AUTHORIZATION"]). ' '
            . escapeshellarg($clientState). ' '
            . escapeshellarg($slimState);
        $command = $xdebug . 'nohup ' . $phpCommand . ' > /dev/null 2>&1 & echo $!';
        exec($command, $output, $return_var);

        return $response->withJson(self::STATUS_OK);
    }

    public function saveStateAction(Request $request, Response $response, $args)
    {
        ini_set('max_execution_time', 0);
        $auth = $this->getAuth();
        $userService = $this->getContainer()->get('UserService');
        $user = $userService->getUser($auth->getIdentity());
        $fileName = $user->getUuid().DIRECTORY_SEPARATOR.'state_'.$user->getUuid();
        $uploadsStorage = $this->getFileService()->getUploadsStorage();
        $filePath = $uploadsStorage->getStreamUrl(
            $fileName
        );
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        $resp = $this->getStateAction($request, $response, $args);
        $streamBody = $resp->getBody();
        $streamBody->rewind();
        $responseArray = $streamBody->getContents();
        $fp = $uploadsStorage->fopen($fileName, 'wb');
        fwrite($fp, $responseArray);
        fclose($fp);
        return $response->withJson(self::STATUS_OK);
    }
    /**
     * API call: GET /state
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function getStateAction(Request $request, Response $response, $args)
    {
        $auth = $this->getAuth();
        $userId     = $this->getAuth()->getIdentity();
        $clientState = $request->getParam('client_state', 0);
        $slimState = $request->getParam('slim_state', 0);
        $userService = $this->getContainer()->get('UserService');
        $user = $userService->getUser($auth->getIdentity());
        $isDesktopClient = $this->isDesktopClient($request);
        $responseArray = array(
            'state' => '',
            'countEntries' => 0,
            'countVisibleEntries' => 0,
            'entries' => array()
        );
        $stateContent = "";
        $jsonEntries = "";
        $portalService = $this->getContainer()->get('PortalService');
        $workspaceService = $this->getContainer()->get('WorkspaceService');
        $stateService = $this->getStateService();
        $folderService = $this->getFolderService();
        $permissionService = $this->getPermissionService();
        $readPermission = PermissionType::getPermissionTypeBitMask(
            File::RESOURCE_ID,
            PermissionType::FILE_READ
        );
        $editPermission = PermissionType::getPermissionTypeBitMask(
            File::RESOURCE_ID,
            PermissionType::FILE_EDIT
        );
        $grantEditPermission = PermissionType::getPermissionTypeBitMask(
            File::RESOURCE_ID,
            PermissionType::FILE_GRANT_EDIT
        );
        $portals = $portalService->getPortalsAvailableForUser($user);
        usort($portals, array($this, "cmp"));
        foreach ($portals as $portal) {
            if ($slimState) {
                $responseArray['state'] = hash(
                    'sha256',
                    $responseArray['state'] . $portal->getName()
                );
            } else {
                $stateContent .= hash('sha256', $portal->getName());
            }
            $jsonEntries .= json_encode(array(
                'id' => $portal->getId(),
                'name' => $portal->getName(),
                'type' => 'portal',
                'hash' => null
            )) . ",";
            $responseArray['countEntries']++;
            $responseArray['countVisibleEntries']++;
            $paginator = $workspaceService->getWorkspacesAvailableForUser($user, $portal);
            $workspaces = $paginator->getIterator()->getArrayCopy();
            unset($paginator);
            usort($workspaces, array($this, "cmp"));
            foreach ($workspaces as $workspace) {
                $fileIds = null;
                $selSyncData = array();
                if (!$workspace->getIsDeleted()) {
                    if ($slimState) {
                        $responseArray['state'] = hash(
                            'sha256',
                            $responseArray['state'] . $workspace->getName()
                        );
                    } else {
                        $stateContent .= hash('sha256', $workspace->getName());
                    }
                    $jsonEntries .= json_encode(array(
                        'id' => $workspace->getId(),
                        'name' => $workspace->getName(),
                        'type' => 'workspace',
                        'hash' => null
                    )) . ",";
                    $responseArray['countEntries']++;
                    $responseArray['countVisibleEntries']++;
                    if (!$permissionService->isWorkspaceAdmin($userId, $workspace)) {
                        $fileIds = $folderService->getWorkspaceFileIdsAvailableForUser($userId, $workspace, false);
                        $read = false;
                    } else {
                        $read = true;
                    }
                    if ($isDesktopClient) {
                        $selectiveSyncService = $this->getSelectiveSyncService();
                        $selSyncData = $selectiveSyncService->getSelectiveSyncFileIds($userId, $workspace);
                    }
                    $parentArrays = null;
                    $parents = null;
                    $entries = null;
                    $treeSortedMap = null;
                    $nrOfEntries = 0;
                    do {
                        $levelEntries = $stateService->getEntriesForLevel($parentArrays, $workspace);
                        $nrOfEntries += count($levelEntries);
                        unset($parents);
                        $parents = null;
                        $cnt = 0;
                        while ($levelEntry = array_shift($levelEntries)) {
                            $node = (array) json_decode($levelEntry['json']);
                            if (isset($fileIds[$node['id']])) {
                                if (!$fileIds[$node['id']]) {
                                } else {
                                    if (!isset($selSyncData[$node['id']])) {
                                        $entries[] = $levelEntry['json'];
                                        if ($node['type'] == "folder") {
                                            $parents[] = $node['id'];
                                        }
                                    }
                                }
                            } elseif (!isset($selSyncData[$node['id']])) {
                                if ($parentArrays != null) {
                                    $entries[] = $levelEntry['json'];
                                    if ($node['type'] == "folder") {
                                        $parents[] = $node['id'];
                                    }
                                } else {
                                    $rootObj = $this->getFileService()->getFile($node['id']);
                                    if ($permissionService->isWorkspaceAdmin($userId, $workspace) ||
                                        ($this->getFileService()->getFileFolderHighestPermission($rootObj) &
                                            ($readPermission | $editPermission | $grantEditPermission))) {
                                        $entries[] = $levelEntry['json'];
                                        if ($node['type'] == "folder") {
                                            $parents[] = $node['id'];
                                        }
                                    }
                                }
                            }
                        }
                        if ($parents != null) {
                            $cnt = count($parents);
                            if ($cnt) {
                                $parentArrays = array_chunk($parents, 1000, true);
                            }
                        }
                    } while ($cnt);
                    unset($levelEntries);
                    unset($selSyncData);
                    unset($fileIds);
                    if ($entries) {
                        $treeSortedMap = $stateService->getTreeMap2($entries);
                    }
                    unset($entries);
                    if ($treeSortedMap) {
                        $visibleEntries = $stateService->getVisibleChildren2(
                            $treeSortedMap,
                            -1
                        );
                    }
                    unset($treeSortedMap);

                    if (!empty($visibleEntries)) {
                        $responseArray['countVisibleEntries'] += count($visibleEntries);
                        while ($visibleEntry = array_shift($visibleEntries)) {
                            $node = (array) json_decode($visibleEntry);
                            if ($slimState) {
                                $responseArray['state'] = hash(
                                    'sha256',
                                    $responseArray['state'] .
                                    $node['name'] . $node['hash']
                                );
                            } else {
                                $stateContent .= hash(
                                    'sha256',
                                    $node['name'] . $node['hash']
                                );
                            }
                            $jsonEntries .= $visibleEntry.",";
                        }
                        unset($visibleEntries);
                    }
                }
            }
        }
        if ($slimState) {
            if ($responseArray['state'] == '') {
                $responseArray['state'] = hash('sha256', '');
            }
        } else {
            $responseArray['state'] = hash('sha256', $stateContent);
            unset($stateContent);
        }
        if ($clientState && $responseArray['state'] != $clientState) {
            $jsonArray = json_encode($responseArray);
            $jsonArray = str_replace("[]", "[".substr($jsonEntries, 0, -1)."]", $jsonArray);
        } else {
            $responseArray['entries'] = array();
            $jsonArray = json_encode($responseArray);
        }
        unset($jsonEntries);
        unset($responseArray);
        $response->withHeader('Content-Type', 'application/json; charset=utf-8');
        return $response->write($jsonArray);
    }

    /**
     * API call: GET /state{folder_id}/folder
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function getFolderStateAction(Request $request, Response $response, $args)
    {
        $folder = $request->getAttribute('folder');
        $userId     = $this->getAuth()->getIdentity();
        $clientState = $request->getParam('client_state', 0);
        $slimState = $request->getParam('slim_state', 0);
        $responseArray = array(
            'state' => '',
            'countEntries' => 0,
            'countVisibleEntries' => 0,
            'entries' => array()
        );
        $stateContent = "";
        $offset = $request->getParam('offset', 0);
        $limit = $request->getParam('limit', FolderService::FOLDER_CHILDREN_LIMIT_DEFAULT);

        $paginator = $this->getFolderService()->getFolderChildrenAvailableForUser(
            $folder,
            $userId,
            null,
            null,
            null
        );
        $entries = $paginator->getIterator()->getArrayCopy();
        unset($paginator);
        usort($entries, array($this, "cmp"));
        $isDesktopClient = $this->isDesktopClient($request);

        $visibleEntries = $this->getVisibleFolderChildren($userId, $entries, $isDesktopClient);
        if (!empty($entries)) {
            $responseArray['countEntries'] = count($entries);
            unset($entries);
        } else {
            $responseArray['countEntries'] = 0;
        }
        if (!empty($visibleEntries)) {
            $responseArray['countVisibleEntries'] = count($visibleEntries);
            while ($visibleEntry = array_shift($visibleEntries)) {
                if ($slimState) {
                    $responseArray['state'] = hash(
                        'sha256',
                        $responseArray['state'] .
                        $visibleEntry->getName() . $visibleEntry->getHash()
                    );
                } else {
                    $stateContent .= hash(
                        'sha256',
                        $visibleEntry->getName() . $visibleEntry->getHash()
                    );
                }
                $responseArray['entries'][] = array(
                    'id' => $visibleEntry->getId(),
                    'name' => $visibleEntry->getName(),
                    'parent' => $visibleEntry->getParent() ? $visibleEntry->getParent()->getId() : null(),
                    'type' => $visibleEntry->getType(),
                    'hash' => $visibleEntry->getHash(),
                    'size' => $visibleEntry->getSize(),
                    'version' => $visibleEntry->getEtag()
                );
            }
            unset($visibleEntries);
        }
        if ($slimState) {
            if ($responseArray['state'] == '') {
                $responseArray['state'] = hash('sha256', '');
            }
        } else {
            $responseArray['state'] = hash('sha256', $stateContent);
            unset($stateContent);
        }
        if ($clientState && $responseArray['state'] != $clientState) {
        } else {
            $responseArray['entries'] = array();
        }
        return $response->withJson(
            $responseArray
        );
    }

    /**
     * API call: GET /state{folder_id}/folders
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function getFolderTreeStateAction(Request $request, Response $response, $args)
    {
        $folder = $request->getAttribute('folder');
        $userId     = $this->getAuth()->getIdentity();
        $clientState = $request->getParam('client_state', 0);
        $slimState = $request->getParam('slim_state', 0);
        $responseArray = array(
            'state' => '',
            'countEntries' => 0,
            'countVisibleEntries' => 0,
            'entries' => array()
        );
        $selSyncData = array();
        $fileIds = null;
        $stateContent = "";
        $jsonEntries = "";
        $workspace = $folder->getWorkspace();
        $stateService = $this->getStateService();
        $folderService = $this->getFolderService();
        $userService = $this->getUserService();
        $permissionService = $this->getPermissionService();
        $isDesktopClient = $this->isDesktopClient($request);

        $user = $userService->getUser($userId);
        if (!$permissionService->isWorkspaceAdmin($user, $workspace)) {
            $fileIds = $folderService->getWorkspaceFileIdsAvailableForUser($user, $workspace, false);
            $read = false;
        } else {
            $read = true;
        }
        if ($isDesktopClient) {
            $selectiveSyncService = $this->getSelectiveSyncService();
            $selSyncData = $selectiveSyncService->getSelectiveSyncFileIds($user, $workspace);
        }
        $parentArrays = array(array($folder->getId()));
        $topLevel = true;
        $parents = null;
        $nrOfEntries = 0;
        $entries = null;
        $treeSortedMap = null;
        $notAvailableFileIds = null;
        if (!$permissionService->isWorkspaceAdmin($user, $folder->getWorkspace())) {
            $notAvailableFileIds = $folderService->getFolderFileIdsNotAvailableForUser($user, $folder, false);
        }
        do {
            $levelEntries = $stateService->getEntriesForLevel($parentArrays, $workspace);
            $nrOfEntries += count($levelEntries);
            unset($parents);
            $parents = null;
            $cnt = 0;
            while ($levelEntry = array_shift($levelEntries)) {
                $node = (array) json_decode($levelEntry['json']);
                if (isset($fileIds[$node['id']])) {
                    if (!$fileIds[$node['id']]) {
                    } else {
                        if (!isset($selSyncData[$node['id']])) {
                            $entries[] = $levelEntry['json'];
                            if ($node['type'] == "folder") {
                                $parents[] = $node['id'];
                            }
                        }
                    }
                } elseif (!isset($selSyncData[$node['id']])) {
                    if (!$topLevel || !isset($notAvailableFileIds[$node['id']])) {
                        $entries[] = $levelEntry['json'];
                        if ($node['type'] == "folder") {
                            $parents[] = $node['id'];
                        }
                    }
                }
            }
            if ($parents != null) {
                $cnt = count($parents);
                if ($cnt) {
                    $parentArrays = array_chunk($parents, 1000, true);
                }
            }
            $topLevel = false;
        } while ($cnt);
        unset($levelEntries);
        unset($selSyncData);
        unset($fileIds);
        if ($entries) {
            $treeSortedMap = $stateService->getTreeMap2($entries, true);
        }
        if ($treeSortedMap) {
            $visibleEntries = $stateService->getVisibleChildren2(
                $treeSortedMap[0],
                0
            );
        }
        unset($treeSortedMap);
        $clientVersion = $this->getClientVersion();
        if (!empty($visibleEntries)) {
            //unset($visibleEntries[0]);
            $responseArray['countVisibleEntries'] = count($visibleEntries);
            while ($visibleEntry = array_shift($visibleEntries)) {
                $node = (array) json_decode($visibleEntry);
                if ($slimState) {
                    $responseArray['state'] = hash(
                        'sha256',
                        $responseArray['state'] .
                        $node['name'] . $node['hash'] .
                        ($clientVersion == '1.4' ? '' : $node['level'])
                    );
                } else {
                    $stateContent .= hash(
                        'sha256',
                        $node['name'] . $node['hash'] .
                        ($clientVersion == '1.4' ? '' : $node['level'])
                    );
                }
                $jsonEntries .= $visibleEntry.",";
            }
            unset($visibleEntries);
        }
        if ($slimState) {
            if ($responseArray['state'] == '') {
                $responseArray['state'] = hash('sha256', '');
            }
        } else {
            $responseArray['state'] = hash('sha256', $stateContent);
            unset($stateContent);
        }
        if ($clientState && $responseArray['state'] != $clientState) {
            $jsonArray = json_encode($responseArray);
            $jsonArray = str_replace("[]", "[".substr($jsonEntries, 0, -1)."]", $jsonArray);
        } else {
            $responseArray['entries'] = array();
            $jsonArray = json_encode($responseArray);
        }
        unset($jsonEntries);
        unset($responseArray);
        $response->withHeader('Content-Type', 'application/json; charset=utf-8');
        return $response->write($jsonArray);
    }

    /**
     * API call: GET /state{workspace_id}/workspace
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function getWorkspaceStateAction(Request $request, Response $response, $args)
    {
        $workspace = $request->getAttribute('workspace');
        $userId     = $this->getAuth()->getIdentity();
        $clientState = $request->getParam('client_state', 0);
        $slimState = $request->getParam('slim_state', 0);
        $fileIds = null;
        $jsonEntries = "";
        $responseArray = array(
            'state' => '',
            'countEntries' => 0,
            'countVisibleEntries' => 0,
            'entries' => array()
        );
        $selSyncData = array();
        $stateContent = "";

        if (!$workspace instanceof Workspace) {
            $workspaceId = $workspace;
            $workspace = $this->getEntityManager()->getReference(Workspace::ENTITY_NAME, $workspaceId);
        }
        if (!$workspace->getIsDeleted()) {
            $stateService = $this->getStateService();
            $folderService = $this->getFolderService();
            $permissionService = $this->getPermissionService();
            $isDesktopClient = $this->isDesktopClient($request);
            $readPermission = PermissionType::getPermissionTypeBitMask(
                File::RESOURCE_ID,
                PermissionType::FILE_READ
            );
            $editPermission = PermissionType::getPermissionTypeBitMask(
                File::RESOURCE_ID,
                PermissionType::FILE_EDIT
            );
            $grantEditPermission = PermissionType::getPermissionTypeBitMask(
                File::RESOURCE_ID,
                PermissionType::FILE_GRANT_EDIT
            );
            if (!$permissionService->isWorkspaceAdmin($userId, $workspace)) {
                $fileIds = $folderService->getWorkspaceFileIdsAvailableForUser($userId, $workspace, false);
                $read = false;
            } else {
                $read = true;
            }
            if ($isDesktopClient) {
                $selectiveSyncService = $this->getSelectiveSyncService();
                $selSyncData = $selectiveSyncService->getSelectiveSyncFileIds($userId, $workspace);
            }
            $parentArrays = null;
            $parents = null;
            $nrOfEntries = 0;
            $entries = null;
            $treeSortedMap = null;
            do {
                $levelEntries = $stateService->getEntriesForLevel($parentArrays, $workspace);
                $nrOfEntries += count($levelEntries);
                unset($parents);
                $parents = null;
                $cnt = 0;
                while ($levelEntry = array_shift($levelEntries)) {
                    $node = (array) json_decode($levelEntry['json']);
                    if (isset($fileIds[$node['id']])) {
                        if (!$fileIds[$node['id']]) {
                        } else {
                            if (!isset($selSyncData[$node['id']])) {
                                $entries[] = $levelEntry['json'];
                                if ($node['type'] == "folder") {
                                    $parents[] = $node['id'];
                                }
                            }
                        }
                    } elseif (!isset($selSyncData[$node['id']])) {
                        if ($parentArrays != null) {
                            $entries[] = $levelEntry['json'];
                            if ($node['type'] == "folder") {
                                $parents[] = $node['id'];
                            }
                        } else {
                            $rootObj = $this->getFileService()->getFile($node['id']);
                            if ($permissionService->isWorkspaceAdmin($userId, $workspace) ||
                                ($this->getFileService()->getFileFolderHighestPermission($rootObj) &
                                    ($readPermission | $editPermission | $grantEditPermission))) {
                                $entries[] = $levelEntry['json'];
                                if ($node['type'] == "folder") {
                                    $parents[] = $node['id'];
                                }
                            }
                        }
                    }
                }
                if ($parents != null) {
                    $cnt = count($parents);
                    if ($cnt) {
                        $parentArrays = array_chunk($parents, 1000, true);
                    }
                }
            } while ($cnt);
            unset($levelEntries);
            unset($selSyncData);
            unset($fileIds);
            if ($entries) {
                $treeSortedMap = $stateService->getTreeMap2($entries);
            }
            if ($treeSortedMap) {
                $visibleEntries = $stateService->getVisibleChildren2(
                    $treeSortedMap,
                    -1
                );
            }
            unset($treeSortedMap);
            $clientVersion = $this->getClientVersion();
            if (!empty($visibleEntries)) {
                $responseArray['countVisibleEntries'] = count($visibleEntries);
                while ($visibleEntry = array_shift($visibleEntries)) {
                    $node = (array) json_decode($visibleEntry);
                    if ($slimState) {
                        $responseArray['state'] = hash(
                            'sha256',
                            $responseArray['state'] .
                            $node['name'] . $node['hash'] .
                            ($clientVersion == '1.4' ? '' : $node['level'])
                        );
                    } else {
                        $stateContent .= hash(
                            'sha256',
                            $node['name'] . $node['hash'] .
                            ($clientVersion == '1.4' ? '' : $node['level'])
                        );
                    }
                    $jsonEntries .= $visibleEntry.",";
                }
                unset($visibleEntries);
            }
        }
        if ($slimState) {
            if ($responseArray['state'] == '') {
                $responseArray['state'] = hash('sha256', '');
            }
        } else {
            $responseArray['state'] = hash('sha256', $stateContent);
            unset($stateContent);
        }
        if ($clientState && $responseArray['state'] != $clientState) {
            $jsonArray = json_encode($responseArray);
            $jsonArray = str_replace("[]", "[".substr($jsonEntries, 0, -1)."]", $jsonArray);
        } else {
            $responseArray['entries'] = array();
            $jsonArray = json_encode($responseArray);
        }
        unset($jsonEntries);
        unset($responseArray);
        $response->withHeader('Content-Type', 'application/json; charset=utf-8');
        return $response->write($jsonArray);
    }

    
    /**
     * API call: GET /state{portal_id}/portal
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function getPortalStateAction(Request $request, Response $response, $args)
    {
        $portal = $args['portal_id'];
        $auth = $this->getAuth();
        $userId = $this->getAuth()->getIdentity();
        $clientState = $request->getParam('client_state', 0);
        $slimState = $request->getParam('slim_state', 0);
        $responseArray = array(
            'state' => '',
            'countEntries' => 0,
            'countVisibleEntries' => 0,
            'entries' => array()
        );
        $stateContent = "";
        $jsonEntries = "";
        $portalService = $this->getContainer()->get('PortalService');
        $workspaceService = $this->getContainer()->get('WorkspaceService');
        $userService = $this->getContainer()->get('UserService');
        $stateService = $this->getStateService();
        $folderService = $this->getFolderService();
        $permissionService = $this->getPermissionService();
        if (!$portal instanceof Portal) {
            $portalId = $portal;
            $portal = $portalService->getPortal($portalId);
        }
        $user = $userService->getUser($auth->getIdentity());

        $paginator = $workspaceService->getWorkspacesAvailableForUser($user, $portal);
        $workspaces = $paginator->getIterator()->getArrayCopy();
        unset($paginator);
        usort($workspaces, array($this, "cmp"));
        foreach ($workspaces as $workspace) {
            $selSyncData = array();
            $fileIds = null;
            if (!$workspace->getIsDeleted()) {
                if ($slimState) {
                    $responseArray['state'] = hash(
                        'sha256',
                        $responseArray['state'] .$workspace->getName()
                    );
                } else {
                    $stateContent .= hash('sha256', $workspace->getName());
                }
                $jsonEntries .= json_encode(array(
                        'id' => $workspace->getId(),
                        'name' => $workspace->getName(),
                        'type' => 'workspace',
                        'hash' => null
                    )) . ",";
                $responseArray['countEntries']++;
                $responseArray['countVisibleEntries']++;
                $entries = $folderService->getAllWorkspaceChildren($workspace);
                if (!empty($entries)) {
                    $responseArray['countEntries'] += count($entries);
                    $treeSortedMap = $stateService->getTreeMap($entries);
                    unset($entries);
                    if (!$permissionService->isWorkspaceAdmin($userId, $workspace)) {
                        $fileIds = $folderService->getWorkspaceFileIdsAvailableForUser(
                            $userId,
                            $workspace,
                            false
                        );
                        $read = false;
                    } else {
                        $read = true;
                    }
                    $isDesktopClient = $this->isDesktopClient($request);

                    if ($isDesktopClient) {
                        $selectiveSyncService = $this->getSelectiveSyncService();
                        $selSyncData = $selectiveSyncService->getSelectiveSyncFileIds($userId, $workspace);
                    }
                    $visibleEntries = $stateService->getVisibleChildren(
                        $treeSortedMap,
                        $selSyncData,
                        $fileIds,
                        -1,
                        $read
                    );
                    unset($treeSortedMap);
                    unset($selSyncData);
                    unset($fileIds);
                    $clientVersion = $this->getClientVersion();
                    if (!empty($visibleEntries)) {
                        $responseArray['countVisibleEntries'] += count($visibleEntries);
                        while ($visibleEntry = array_shift($visibleEntries)) {
                            $node = (array) json_decode($visibleEntry['gzentry']);
                            if ($slimState) {
                                $responseArray['state'] = hash(
                                    'sha256',
                                    $responseArray['state'] .
                                    $node['name'] . $node['hash'] .
                                    ($clientVersion == '1.4' ?
                                        '' : $node['level']
                                    )
                                );
                            } else {
                                $stateContent .= hash(
                                    'sha256',
                                    $node['name'] . $node['hash'] .
                                    ($clientVersion == '1.4' ?
                                        '' : $node['level']
                                    )
                                );
                            }
                            $jsonEntries .= $visibleEntry['gzentry'].",";
                        }
                        unset($visibleEntries);
                    }
                }
            }
        }
        if ($slimState) {
            if ($responseArray['state'] == '') {
                $responseArray['state'] = hash('sha256', '');
            }
        } else {
            $responseArray['state'] = hash('sha256', $stateContent);
            unset($stateContent);
        }
        if ($clientState && $responseArray['state'] != $clientState) {
            $jsonArray = json_encode($responseArray);
            $jsonArray = str_replace("[]", "[".substr($jsonEntries, 0, -1)."]", $jsonArray);
        } else {
            $responseArray['entries'] = array();
            $jsonArray = json_encode($responseArray);
        }
        unset($jsonEntries);
        unset($responseArray);
        $response->withHeader('Content-Type', 'application/json; charset=utf-8');
        return $response->write($jsonArray);
    }


    /**
 * @return SelectiveSyncService
 */
    private function getSelectiveSyncService()
    {
        return $this->getContainer()->get('SelectiveSyncService');
    }

    /**
     * @return PermissionService
     */
    protected function getPermissionService()
    {

        return $this->getContainer()->get('PermissionService');
    }

    /**
     * @return StateService
     */
    protected function getStateService()
    {

        return $this->getContainer()->get('StateService');
    }

    /**
     * @return FileService
     */
    private function getFileService()
    {
        return $this->getContainer()->get('FileService');
    }

    /**
     * @return UserService
     */
    private function getUserService()
    {
        return $this->getContainer()->get('UserService');
    }
}
