<?php

namespace iCoordinator\Controller;

use iCoordinator\Config\Route\FilesRouteConfig;
use iCoordinator\Controller\Helper\FilesControllerHelper;
use iCoordinator\Entity\Error;
use iCoordinator\Entity\File;
use iCoordinator\Entity\Folder;
use iCoordinator\Permissions\PermissionType;
use iCoordinator\Permissions\Privilege\FilePrivilege;
use iCoordinator\Permissions\Privilege\PortalPrivilege;
use iCoordinator\Permissions\Privilege\WorkspacePrivilege;
use iCoordinator\Permissions\Role\GuestRole;
use iCoordinator\Permissions\Role\UserRole;
use iCoordinator\Service\DownloadTokenService;
use iCoordinator\Service\Exception\NotFoundException;
use iCoordinator\Service\Exception\NotTrashedException;
use iCoordinator\Service\Exception\ValidationFailedException;
use iCoordinator\Service\FileService;
use iCoordinator\Service\FolderService;
use iCoordinator\Service\PermissionService;
use iCoordinator\Service\SubscriptionService;
use iCoordinator\Service\UserService;
use iCoordinator\Service\WorkspaceService;
use Slim\Http\Request;
use Slim\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipStream;
use ZipStream\Option\Archive as ArchiveOptions;
use ZipStream\Option\File as FileOptions;

class FilesController extends AbstractRestController
{
    private $treeSortedMap = null;
    private $selSyncData = array();
    private $fileIds = array();

    public function init()
    {
        $this->addHelper(new FilesControllerHelper());
    }

    /**
     * API call: GET /files/<ID>
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function getFileAction(Request $request, Response $response, $args)
    {
        $fileId = $args['file_id'];

        $acl = $this->getAcl();

        $fileService = $this->getContainer()->get('FileService');
        $file = $fileService->getFile($fileId);
        if (!$file) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $role = $this->getRole($request);
        $privilege = FilePrivilege::PRIVILEGE_READ;
        if (!$acl->isAllowed($role, $file, $privilege)) {
            $permission = PermissionType::getPermissionTypeBitMask(File::RESOURCE_ID, PermissionType::FILE_READ);
            if ($this->getFileService()->getFileFolderHighestPermission($file) < $permission) {
                return $response->withStatus(self::STATUS_FORBIDDEN);
            }
        }

        return $response->withJson($file);
    }
    /**
     * GET /files/<ID>/permission
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function getFilePermissionAction(Request $request, Response $response, $args)
    {
        $fileId = $args['file_id'];

        $acl = $this->getAcl();
        $auth = $this->getAuth();

        if (!$fileId) {
            return $response->withStatus(self::STATUS_BAD_REQUEST);
        }

        $fileService = $this->getContainer()->get('FileService');
        $file = $fileService->getFile($fileId);
        if (!$file) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $role = new UserRole($auth->getIdentity());
        if ($acl->isAllowed($role, $file, FilePrivilege::PRIVILEGE_MODIFY)) {
            $result = array(
                'actions' => "edit"
            );
        } elseif ($acl->isAllowed($role, $file, FilePrivilege::PRIVILEGE_READ)) {
            $result = array(
                'actions' => "read"
            );
        } else {
            $highest = $this->getFileService()->getFileFolderHighestPermission($file);
            $editPermission = PermissionType::getPermissionTypeBitMask(File::RESOURCE_ID, PermissionType::FILE_EDIT);
            $grantEditPermission = PermissionType::getPermissionTypeBitMask(
                File::RESOURCE_ID,
                PermissionType::FILE_GRANT_EDIT
            );
            $readPermission = PermissionType::getPermissionTypeBitMask(File::RESOURCE_ID, PermissionType::FILE_READ);
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
            if ($this->getSelectiveSyncService()->getSelectiveSync($file, $auth->getIdentity())) {
                $result = array(
                    'actions' => "none"
                );
            }
        }
        return $response->withJson($result);
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
     * API call: POST /workspaces/{workspace_id}/files/content
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     * @throws ValidationFailedException
     * @throws \Exception
     * @throws \iCoordinator\Service\Exception\ConflictException
     */
    public function createFileAction(Request $request, Response $response, $args)
    {

        $data       = $request->getParsedBody();

        if (isset($data['data'])) {
            $postData = json_decode($data['data']);
            if (!isset($data['name']) && isset($postData->name)) {
                $data['name'] = $postData->name;
            }
            if (!isset($data['parent_id']) && isset($postData->parent_id)) {
                $data['parent_id'] = $postData->parent_id;
            }
            if (!isset($data['content_modified_at']) && isset($postData->content_modified_at)) {
                $data['content_modified_at'] = $postData->content_modified_at;
            }
            unset($data['data']);
        }
        if (isset($args['folder_id'])) {
            if ($args['folder_id'] == 0) {
                return $response->withStatus(self::STATUS_FORBIDDEN);
            }
            $folder = $this->getFileService()->getFile($args['folder_id']);
            $workspace = $folder->getWorkspace();
            $data['parent_id'] = $args['folder_id'];
        } else {
            $workspace = $request->getAttribute('workspace');
        }
        $auth       = $this->getAuth();

        if (isset($data['parent_id']) && $data['parent_id'] == 0) {
            if ($this->isDesktopClient($request) && !$workspace->getDesktopSync()) {
                return $response->withStatus(self::STATUS_FORBIDDEN);
            }
            unset($data['parent_id']);
        }

        //check if can create in parent folder
        $role = new UserRole($auth->getIdentity());
        if (!empty($data['parent_id']) && $data['parent_id'] != 0) {
            $privilege = FilePrivilege::PRIVILEGE_CREATE_FILES;
            $helperResponse = $this->getHelper(FilesControllerHelper::HELPER_ID)
                ->checkParentFolderAccess($data['parent_id'], $role, $privilege, $response);
            if ($helperResponse instanceof Response) {
                return $helperResponse;
            }
        }

        // check if storage quota is exceeded
        $portal = $workspace->getPortal();
        $files  = $request->getUploadedFiles();
        $size   = 0;
        foreach ($files as $file) {
            $size += $file->getSize();
        }
        if (!$this->getSubscriptionService()->checkCanAddFile($size, $portal)) {
            $error = new Error(Error::LICENSE_UPDATE_REQUIRED);
            return $response->withJson($error, self::STATUS_FORBIDDEN);
        }
        if ($this->hasInvalidChars($data['name'])) {
            $error = new Error(Error::INVALID_CHARACTERS);
            return $response->withJson($error, self::STATUS_BAD_REQUEST);
        }
        if ($this->isDesktopClient($request)) {
            if ($this->getSelectiveSyncService()->getSelectiveSync(
                (isset($data['parent_id']) ? $data['parent_id']:null),
                $auth->getIdentity()
            )) {
                $error = new Error(Error::ITEM_SYNC_DISABLED);
                return $response->withJson($error, self::STATUS_FORBIDDEN);
            }
        }

        $file = $this->getFileService()->createFile($data, $workspace, $auth->getIdentity());
        return $response->withJson($file, (isset($data['upload_id']) ? self::STATUS_ACCEPTED : self::STATUS_CREATED))
            ->withHeader('Access-Control-Expose-Headers', 'Heroku-dyno-id')
            ->withHeader('Heroku-dyno-id', getenv('HEROKU_DYNO_ID'));
    }

    /**
     * API call: POST /workspaces/{workspace_id}/files/uploads
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     * @throws \Exception
     */
    public function createFileUploadAction(Request $request, Response $response, $args)
    {
        $data       = $request->getParsedBody();

        if (isset($args['folder_id'])) {
            if ($args['folder_id'] == 0) {
                return $response->withStatus(self::STATUS_FORBIDDEN);
            }
            $folder = $this->getFileService()->getFile($args['folder_id']);
            $workspace = $folder->getWorkspace();
            $data['parent_id'] = $args['folder_id'];
        } else {
            $workspace = $request->getAttribute('workspace');
            $folder = null;
        }

        $auth       = $this->getAuth();

        if (!isset($data['size'])) {
            return $response->withStatus(self::STATUS_BAD_REQUEST);
        }

        if (isset($data['parent_id']) && $data['parent_id'] == 0) {
            unset($data['parent_id']);
        }

        //check if can create in parent folder
        $role = new UserRole($auth->getIdentity());
        if (!empty($data['parent_id']) && $data['parent_id'] != 0) {
            $privilege = FilePrivilege::PRIVILEGE_CREATE_FILES;
            $helperResponse = $this->getHelper(FilesControllerHelper::HELPER_ID)
                ->checkParentFolderAccess($data['parent_id'], $role, $privilege, $response);
            if ($helperResponse instanceof Response) {
                return $helperResponse;
            }
        }

        // check if file size limit is exceeded
        $portal = $workspace->getPortal();
        if (!$this->getSubscriptionService()->checkFileSizeOK($data['size'], $portal)) {
            $error = new Error(Error::FILE_SIZE_LIMIT_EXCEEDED);
            return $response->withJson($error, self::STATUS_FORBIDDEN);
        }

        // check if storage quota is exceeded
        $portal = $workspace->getPortal();
        if (!$this->getSubscriptionService()->checkCanAddFile($data['size'], $portal)) {
            $error = new Error(Error::LICENSE_UPDATE_REQUIRED);
            return $response->withJson($error, self::STATUS_FORBIDDEN);
        }

        if ($this->isDesktopClient($request)) {
            if ($this->getSelectiveSyncService()->getSelectiveSync(
                (isset($data['parent_id']) ? $data['parent_id'] : null),
                $auth->getIdentity()
            )
            ) {
                $error = new Error(Error::ITEM_SYNC_DISABLED);
                return $response->withJson($error, self::STATUS_FORBIDDEN);
            }
        }
        if (!empty($data["name"])) {
            if ($this->hasInvalidChars($data['name'])) {
                $error = new Error(Error::INVALID_CHARACTERS);
                return $response->withJson($error, self::STATUS_BAD_REQUEST);
            }
            if (isset($data['parent_id'])) {
                $parentFolder = $this->getFileService()->getFile($data['parent_id']);
            } else {
                $parentFolder = null;
            }
            if ($this->getFileService()->checkNameExists($data['name'], $workspace, $parentFolder)) {
                return $response->withStatus(self::STATUS_CONFLICT);
            }
        }

        $fileUpload = $this->getFileService()->fileUploadCreate($data, $auth->getIdentity());

        return $response->withJson($fileUpload, self::STATUS_CREATED)
            ->withHeader('Access-Control-Expose-Headers', 'Heroku-dyno-id')
            ->withHeader('Heroku-dyno-id', getenv('HEROKU_DYNO_ID'));
    }

    /**
     * API call: PUT /files/uploads/{upload_id}
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws ValidationFailedException
     */
    public function uploadFileChunkAction(Request $request, Response $response)
    {
        $fileUploadId   = $request->getAttribute('upload_id');
        $offset         = $request->getParam('offset', null);

        if (!$fileUploadId && count($request->getUploadedFiles()) == 0) {
            return $response->withStatus(self::STATUS_BAD_REQUEST);
        }

        $fileUpload = $this->getFileService()->getFileUpload($fileUploadId);
        if (!$fileUpload) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        if ($fileUpload->getCreatedBy()->getId() != $this->getAuth()->getIdentity()) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }

        $fileUpload = $this->getFileService()->uploadFileChunk(
            $fileUpload,
            current($request->getUploadedFiles()),
            $offset
        );

        return $response->withJson($fileUpload)
            ->withHeader('Access-Control-Expose-Headers', 'Heroku-dyno-id')
            ->withHeader('Heroku-dyno-id', getenv('HEROKU_DYNO_ID'));
    }

    /**
     * API call: GET /files/<ID>/content
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     * @throws NotFoundException
     * @throws \Exception
     */
    public function downloadFileAction(Request $request, Response $response, $args)
    {
        $fileId     = $args['file_id'];
        $acl        = $this->getAcl();
        $userId     = $this->getAuth()->getIdentity();
        $version    = $request->getParam('version', false);
        $openStyle  = $request->getParam('open_style', 'inline');

        /** @var Router $router */
        $router     = $this->getContainer()->get('router');

        if (!$fileId) {
            return $response->withStatus(self::STATUS_BAD_REQUEST);
        }

        $fileService = $this->getContainer()->get('FileService');
        $file = $fileService->getFile($fileId);
        if (!$file) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }
        if ($file->getIsDeleted() || $file->getIsTrashed()) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }
        if ($userId) {
            $role = new UserRole($userId);
            $privilege = PortalPrivilege::PRIVILEGE_READ_USERS;
            if (!$acl->isAllowed($role, $file->getWorkspace()->getPortal(), $privilege)) {
                return $response->withStatus(self::STATUS_FORBIDDEN);
            }
        }
        $role = $this->getRole($request);
        $privilege = FilePrivilege::PRIVILEGE_DOWNLOAD;
        if (!$acl->isAllowed($role, $file, $privilege)) {
            $permission = PermissionType::getPermissionTypeBitMask(File::RESOURCE_ID, PermissionType::FILE_READ);
            if ($this->getFileService()->getFileFolderHighestPermission($file) < $permission) {
                return $response->withStatus(self::STATUS_FORBIDDEN);
            }
        }

        if ($this->isDesktopClient($request)) {
            if ($this->getSelectiveSyncService()->getSelectiveSync($file, $userId)) {
                $error = new Error(Error::ITEM_SYNC_DISABLED);
                return $response->withJson($error, self::STATUS_FORBIDDEN);
            }
        }

        /** @var DownloadTokenService $downloadTokenService */
        $downloadTokenService = $this->getContainer()->get('DownloadTokenService');
        $downloadServer = $downloadTokenService->getDownloadServer($file, $router, $openStyle, $version, $userId);

        if ($request->isOptions()) {
            return $response->withJson($downloadServer);
        }

        return $response->withStatus(self::STATUS_FOUND)->withHeader('Location', $downloadServer->getUrl());
    }

    /**
     * API call: GET /files/<ID>/download/<TOKEN>
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     * @throws NotFoundException
     */
    public function downloadFileWithTokenAction(Request $request, Response $response, $args)
    {
        $fileId     = $args['file_id'];
        $userId     = $this->getAuth()->getIdentity();
        $acl        = $this->getAcl();
        $token      = $args['token'];
        $version    = $request->getParam('version', null);
        $openStyle  = $request->getParam('open_style', 'inline');

        if (!$fileId) {
            return $response->withStatus(self::STATUS_BAD_REQUEST);
        }

        $file = $this->getFileService()->getFile($fileId);
        if (!$file) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        if (!$this->getDownloadTokenService()->checkToken($token)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }

        if ($this->isDesktopClient($request)) {
            if ($this->getSelectiveSyncService()->getSelectiveSync($file, $userId)) {
                $error = new Error(Error::ITEM_SYNC_DISABLED);
                return $response->withJson($error, self::STATUS_FORBIDDEN);
            }
        }

        $stream = $request->getParam('stream', false);

        $downloader = $this->getFileService()->getFileDownloader($file, $version, array(
            'httpRange' => $request->getHeaderLine('Range'),
            'stream' => $stream
        ), $openStyle);

        foreach ($downloader->getHeaders() as $headerName => $headerValue) {
            $response = $response->withHeader($headerName, $headerValue);
        }
        $response = $response->withHeader('X-Accel-Buffering', 'no');
        return $response->withBody($downloader->getStream())
            ->withStatus($downloader->getStatusCode());
    }

    /**
     * API call: GET /files/zip
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     * @throws NotFoundException
     * @throws \Exception
     */
    public function downloadZipFileAction(Request $request, Response $response, $args)
    {
        $acl        = $this->getAcl();
        $userId     = $this->getAuth()->getIdentity();
        $version    = $request->getParam('version', false);
        $openStyle  = $request->getParam('open_style', 'inline');

        $data = $request->getParsedBody();

        /** @var Router $router */
        $router     = $this->getContainer()->get('router');

        $fileService = $this->getContainer()->get('FileService');
        if (!isset($data['files'])) {
            return $response->withStatus(self::STATUS_BAD_REQUEST);
        } elseif (!isset($data['files'][0])) {
            return $response->withStatus(self::STATUS_BAD_REQUEST);
        }
        $file = $fileService->getFile($data['files'][0]);
        if (!$file) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }
        if ($userId) {
            $role = new UserRole($userId);
            $privilege = PortalPrivilege::PRIVILEGE_READ_USERS;
            if (!$acl->isAllowed($role, $file->getWorkspace()->getPortal(), $privilege)) {
                return $response->withStatus(self::STATUS_FORBIDDEN);
            }
        }
        $role = $this->getRole($request);
        $privilege = FilePrivilege::PRIVILEGE_DOWNLOAD;
        if (!$acl->isAllowed($role, $file, $privilege)) {
            $permission = PermissionType::getPermissionTypeBitMask(File::RESOURCE_ID, PermissionType::FILE_READ);
            if ($this->getFileService()->getFileFolderHighestPermission($file) < $permission) {
                return $response->withStatus(self::STATUS_FORBIDDEN);
            }
        }

        if ($this->isDesktopClient($request)) {
            if ($this->getSelectiveSyncService()->getSelectiveSync($file, $userId)) {
                $error = new Error(Error::ITEM_SYNC_DISABLED);
                return $response->withJson($error, self::STATUS_FORBIDDEN);
            }
        }

        /** @var DownloadZipTokenService $downloadZipTokenService */
        $downloadZipTokenService = $this->getContainer()->get('DownloadZipTokenService');
        $downloadServer = $downloadZipTokenService->getDownloadServer($data['files'], $router, $openStyle, $userId);

        if ($request->isOptions()) {
            return $response->withJson($downloadServer);
        }

        return $response->withStatus(self::STATUS_OK)->withJson(array('Location' => $downloadServer->getUrl()));
    }

    /**
     * API call: GET /files/<ID>/download/<TOKEN>
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     * @throws NotFoundException
     */
    public function downloadZipWithTokenAction(Request $request)
    {
        $userId     = $this->getAuth()->getIdentity();
        $acl        = $this->getAcl();
        $matches = array();
        preg_match('/files\/([0-f]+)\/zip/', $_SERVER["REQUEST_URI"], $matches);
        $token      = $matches[1];
        $userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);
        //$token      = $args['token'];

        if (!strlen($token)) {
            $response = new Response();
            return $response->withStatus(self::STATUS_BAD_REQUEST);
        }
        $files = null;
        $zipToken = $this->getDownloadZipTokenService()->getToken($token, $userId);
        if ($zipToken) {
            $zipFiles = $zipToken->getFiles();
            $this->getDownloadZipTokenService()->deleteToken($zipToken);
        } else {
            $response = new Response();
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }
        $streamedResponse = new StreamedResponse(function () use ($userId, $zipFiles, $request, $acl, $userAgent) {
            $opt = new ArchiveOptions();
            $opt->setOutputStream(fopen('php://output', 'wb'));
            $opt->setFlushOutput(true);
            $opt->setDeflateLevel(6);
            $opt->setLargeFileMethod(ZipStream\Option\Method::DEFLATE());
            $opt->setSendHttpHeaders(false);
            $opt->setContentDisposition('attachment; filename="export-'.date('YmdHis').'.zip"');
            $opt->setContentType('application/x-zip');
            $opt->setHttpHeaderCallback('header');

            /*if (strpos($userAgent, 'mac') === false ||
                strpos($userAgent, 'windows') === true) {
                $opt->setZeroHeader(true);
            }*/
            $opt->setZeroHeader(true);
            $opt->setStatFiles(true);
            $opt->setEnableZip64(false);
            //initialise zipstream with output zip filename and options.
            $zip = new ZipStream\ZipStream(null, $opt);
            $role = new UserRole($userId);
            $privilege = FilePrivilege::PRIVILEGE_DOWNLOAD;

            foreach ($zipFiles as $zipFile) {
                $file = $zipFile->getFile();
                if (!$file) {
                } else {
                    if ($file instanceof Folder) {
                        $this->addFolderToZipRecursive(
                            $file,
                            $zip,
                            $userId,
                            $this->isDesktopClient($request),
                            $file->getName()
                        );
                    } else {
                        if (!$acl->isAllowed($role, $file, $privilege)) {
                            $permission = PermissionType::getPermissionTypeBitMask(
                                File::RESOURCE_ID,
                                PermissionType::FILE_READ
                            );
                            if ($this->getFileService()->getFileFolderHighestPermission($file) < $permission) {
                                continue;
                            }
                        }
                        if ($this->isDesktopClient($request)) {
                            if ($this->getSelectiveSyncService()->getSelectiveSync($file, $userId)) {
                                continue;
                            }
                        }

                        $downloader = $this->getFileService()->getFileDownloader(
                            $file,
                            null,
                            array(),
                            'attachment'
                        );
                        $options = new FileOptions();
                        $options->setSize($file->getSize());
                        $zip->addFileFromPsr7Stream($file->getName(), $downloader->getStream(), $options);
                    }
                }
            }
            $zip->finish();
        });
        $streamedResponse->headers->set('Content-Type', 'application/x-zip');
        $streamedResponse->headers->set('Content-Disposition', 'attachment; filename="export-'.date('YmdHis').'.zip"');
        $streamedResponse->send();
    }

    private function addFolderToZipRecursive($folder, $zip, $userId, $isDesktopClient, $path)
    {
        $selSyncData = array();
        $fileIds = null;
        $workspace = $folder->getWorkspace();
        $stateService = $this->getStateService();
        $folderService = $this->getFolderService();
        $userService = $this->getUserService();
        $permissionService = $this->getPermissionService();

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
            $this->createZipRecursive($treeSortedMap[0], $path, $zip);
        } else {
            $zip->addFile($path.'/', '');
        }
    }
    public function cmpClean($a, $b)
    {
        return strcmp($a['name'], $b['name']);
    }
    public function createZipRecursive(&$node, $path, $zip)
    {
        if (isset($node['id']) && $node['id'] > 0) {
            $gznode = (array)json_decode($node['gzentry']);
            if ($gznode["type"] == "file") {
                $downloader = $this->getFileService()->getFileDownloader(
                    $node['id'],
                    null,
                    array(),
                    'attachment'
                );
                $options = new FileOptions();
                $options->setSize($gznode['size']);
                $zip->addFileFromPsr7Stream(
                    $path . "/" . $gznode["name"],
                    $downloader->getStream(),
                    $options
                );
            } else {
                if (!count($node['children'])) {
                    $zip->addFile(
                        $path . "/" . $gznode["name"] . "/",
                        ''
                    );
                }
                $path = $path . "/" . $gznode["name"];
            }
        }
        if (count($node['children'])) {
            foreach ($node['children'] as $key => $child) {
                $plainChild = (array)json_decode($child['gzentry']);
                $node['children'][$key]['name'] = $plainChild['name'];
                $node['children'][$key]['type'] = $plainChild['type'];
            }
            usort($node['children'], array($this, "cmpClean"));
            while ($child = array_shift($node['children'])) {
                $this->createZipRecursive(
                    $child,
                    $path,
                    $zip
                );
            }
        }
    }
    /**
     * API call: DELETE /files/<ID>
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function deleteFileAction(Request $request, Response $response, $args)
    {
        return $this->deleteFile($request, $response, $args['file_id'], true);
    }

    private function deleteFile(Request $request, Response $response, $fileId, $permanently = false)
    {
        $acl = $this->getAcl();
        $auth = $this->getAuth();

        if (!$fileId) {
            return $response->withStatus(self::STATUS_BAD_REQUEST);
        }

        $fileService = $this->getContainer()->get('FileService');
        $file = $fileService->getFile($fileId);
        if (!$file) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $eTag = $request->getHeaderLine('If-Match');
        if ($eTag && $eTag != $file->getEtag()) {
            return $response->withStatus(
                self::STATUS_PRECONDITION_FAILED,
                "Version mismatch: ".$eTag." != ".$file->getEtag()
            );
        }
        $workspaceUserIds = $this->getWorkspaceService()->getWorkspaceUserIds($file->getWorkspace());
        if (!in_array($auth->getIdentity(), $workspaceUserIds)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }
        $role = new UserRole($auth->getIdentity());
        $privilege = WorkspacePrivilege::PRIVILEGE_READ;
        if (!$acl->isAllowed($role, $file->getWorkspace(), $privilege)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }

        $privilege = FilePrivilege::PRIVILEGE_DELETE;
        if (!$acl->isAllowed($role, $file, $privilege)) {
            $editPermission = PermissionType::getPermissionTypeBitMask(File::RESOURCE_ID, PermissionType::FILE_EDIT);
            $grantEditPermission = PermissionType::getPermissionTypeBitMask(
                File::RESOURCE_ID,
                PermissionType::FILE_GRANT_EDIT
            );
            $highestPermission = $this->getFileService()->getFileFolderHighestPermission($file);
            if (!($highestPermission & ($editPermission | $grantEditPermission))) {
                return $response->withStatus(self::STATUS_FORBIDDEN);
            }
        }

        if ($this->isDesktopClient($request)) {
            if ($this->getSelectiveSyncService()->getSelectiveSync($file, $auth->getIdentity())) {
                $msg = new Error(Error::ITEM_SYNC_DISABLED);
                return $response->withJson($msg, self::STATUS_NO_CONTENT);
            }
        }

        $fileService->deleteFile($fileId, $auth->getIdentity(), $permanently);

        return $response->withStatus(self::STATUS_NO_CONTENT);
    }

    /**
     * API call: DELETE /files/<ID>/trash
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function trashFileAction(Request $request, Response $response, $args)
    {
        return $this->deleteFile($request, $response, $args['file_id'], false);
    }

    /**
     * API call: POST /files/{file_id}
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     * @throws \Exception
     */
    public function restoreFileAction(Request $request, Response $response, $args)
    {
        $fileId = $args['file_id'];

        $acl = $this->getAcl();
        $auth = $this->getAuth();

        if (!$fileId) {
            return $response->withStatus(self::STATUS_BAD_REQUEST);
        }

        $file = $this->getFileService()->getFile($fileId);
        if (!$file) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $data = $request->getParsedBody();

        //check if can modify file
        $role = new UserRole($auth->getIdentity());
        $privilege = FilePrivilege::PRIVILEGE_MODIFY;
        if (!$acl->isAllowed($role, $file, $privilege)) {
            $editPermission = PermissionType::getPermissionTypeBitMask(File::RESOURCE_ID, PermissionType::FILE_EDIT);
            $grantEditPermission = PermissionType::getPermissionTypeBitMask(
                File::RESOURCE_ID,
                PermissionType::FILE_GRANT_EDIT
            );
            $highestPermission = $this->getFileService()->getFileFolderHighestPermission($file);
            if (!($highestPermission & ($editPermission | $grantEditPermission))) {
                return $response->withStatus(self::STATUS_FORBIDDEN);
            }
        }

        //check if can move to other folder
        if (!empty($data['parent'])) {
            $privilege = FilePrivilege::PRIVILEGE_CREATE_FILES;
            $helperResponse = $this->getHelper(FilesControllerHelper::HELPER_ID)
                ->checkParentFolderAccess($data['parent'], $role, $privilege, $response);
            if ($helperResponse instanceof Response) {
                return $helperResponse;
            }
        }

        try {
            $file = $this->getFileService()->restoreFile($file, $auth->getIdentity(), $data);
        } catch (NotTrashedException $e) {
            return $response->withStatus(self::STATUS_METHOD_NOT_ALLOWED);
        }


        return $response->withJson($file, self::STATUS_CREATED);
    }

    /**
     * API call: PUT /files/<ID>
     *
     * @param $fileId
     * @throws NotFoundException
     * @throws \Exception
     * @throws \iCoordinator\Service\Exception\ConflictException
     */
    public function updateFileAction(Request $request, Response $response, $args)
    {
        $fileId = $args['file_id'];

        $acl = $this->getAcl();
        $auth = $this->getAuth();

        if (!$fileId) {
            return $response->withStatus(self::STATUS_BAD_REQUEST);
        }

        $fileService = $this->getContainer()->get('FileService');
        $file = $fileService->getFile($fileId);
        if (!$file) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        if ($file->getIsDeleted() || $file->getIsTrashed()) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }
        $eTag = $request->getHeaderLine('If-Match');
        if ($eTag && $eTag != $file->getEtag()) {
            return $response->withStatus(
                self::STATUS_PRECONDITION_FAILED,
                "Version mismatch: ".$eTag." != ".$file->getEtag()
            );
        }

        $data = $request->getParsedBody();

        //check if can modify file
        $role = new UserRole($auth->getIdentity());
        $privilege = FilePrivilege::PRIVILEGE_MODIFY;
        if (!$acl->isAllowed($role, $file, $privilege)) {
            $editPermission = PermissionType::getPermissionTypeBitMask(File::RESOURCE_ID, PermissionType::FILE_EDIT);
            $grantEditPermission = PermissionType::getPermissionTypeBitMask(
                File::RESOURCE_ID,
                PermissionType::FILE_GRANT_EDIT
            );
            $highestPermission = $this->getFileService()->getFileFolderHighestPermission($file);
            if (!($highestPermission & ($editPermission | $grantEditPermission))) {
                return $response->withStatus(self::STATUS_FORBIDDEN);
            }
        }

        //check if can move to other folder
        if (!empty($data['parent'])) {
            $newParentId = $data['parent']['id'];
            $parentId = ($file->getParent() != null ? $file->getParent()->getId() : null);
            if ($newParentId != $parentId) {
                if ((int)$newParentId == 0) {
                    $privilege = WorkspacePrivilege::PRIVILEGE_CREATE_FILES;
                    if (!$this->getAcl()->isAllowed($role, $file->getWorkspace(), $privilege)) {
                        return $response->withStatus(self::STATUS_FORBIDDEN);
                    }
                } else {
                    $privilege = FilePrivilege::PRIVILEGE_CREATE_FILES;
                    $helperResponse = $this->getHelper(FilesControllerHelper::HELPER_ID)
                        ->checkParentFolderAccess($data['parent'], $role, $privilege, $response);
                    if ($helperResponse instanceof Response) {
                        return $helperResponse;
                    }
                }
                if ($this->isDesktopClient($request)) {
                    if ($this->getSelectiveSyncService()->getSelectiveSync($newParentId, $auth->getIdentity())) {
                        $msg = new Error(Error::ITEM_SYNC_DISABLED);
                        return $response->withJson($msg, self::STATUS_FORBIDDEN);
                    }
                }
                $privilege = FilePrivilege::PRIVILEGE_MODIFY;
                if (!$acl->isAllowed($role, $file, $privilege)) {
                    $editPermission = PermissionType::getPermissionTypeBitMask(
                        File::RESOURCE_ID,
                        PermissionType::FILE_EDIT
                    );
                    $grantEditPermission = PermissionType::getPermissionTypeBitMask(
                        File::RESOURCE_ID,
                        PermissionType::FILE_GRANT_EDIT
                    );
                    $highestPermission = $this->getFileService()->getFileFolderHighestPermission($file);
                    if (!($highestPermission & ($editPermission | $grantEditPermission))) {
                        return $response->withStatus(self::STATUS_FORBIDDEN);
                    }
                }
            }
        }

        if (!empty($data['shared_link'])) {
            $privilege = FilePrivilege::PRIVILEGE_CREATE_SHARED_LINK;
            if (!$acl->isAllowed($role, $file, $privilege)) {
                $editPermission = PermissionType::getPermissionTypeBitMask(
                    File::RESOURCE_ID,
                    PermissionType::FILE_EDIT
                );
                $grantEditPermission = PermissionType::getPermissionTypeBitMask(
                    File::RESOURCE_ID,
                    PermissionType::FILE_GRANT_EDIT
                );
                $highestPermission = $this->getFileService()->getFileFolderHighestPermission($file);
                if (!($highestPermission & ($editPermission | $grantEditPermission))) {
                    return $response->withStatus(self::STATUS_FORBIDDEN);
                }
            }
        }
        if ($this->isDesktopClient($request)) {
            if ($this->getSelectiveSyncService()->getSelectiveSync($file, $auth->getIdentity())) {
                $msg = new Error(Error::ITEM_SYNC_DISABLED);
                return $response->withJson($msg, self::STATUS_FORBIDDEN);
            }
        }
        if (!empty($data['name'])) {
            if ($this->hasInvalidChars($data['name'])) {
                $error = new Error(Error::INVALID_CHARACTERS);
                return $response->withJson($error, self::STATUS_BAD_REQUEST);
            }
        }
        $file = $fileService->updateFile($file, $data, $auth->getIdentity());
        $fileVersion = $this->getFileService()->getLatestFileVersion($file->getId());
        if ($fileVersion) {
            $file->setVersionComment($fileVersion['comment']);
            $modifiedBy = $this->getUserService()->getUser($fileVersion['modified_by']);
            $file->setModifiedBy($modifiedBy);
            $file->setVersionCreatedAt($fileVersion['created_at']);
        }
        return $response->withJson($file);
    }

    /**
     * API call: PUT /fileversion/<ID>
     *
     * @param $fileVersionId
     * @throws NotFoundException
     * @throws \Exception
     * @throws \iCoordinator\Service\Exception\ConflictException
     */
    public function updateFileVersionAction(Request $request, Response $response, $args)
    {
        $fileVersionId = $args['file_version_id'];

        $acl = $this->getAcl();
        $auth = $this->getAuth();

        if (!$fileVersionId) {
            return $response->withStatus(self::STATUS_BAD_REQUEST);
        }

        $fileService = $this->getContainer()->get('FileService');
        $fileVersion = $fileService->getFileVersion($fileVersionId);
        if (!$fileVersion) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $data = $request->getParsedBody();

        //check if can modify file version
        $role = new UserRole($auth->getIdentity());
        $privilege = FilePrivilege::PRIVILEGE_MODIFY;
        if (!$acl->isAllowed($role, $fileVersion->getFile(), $privilege)) {
            $editPermission = PermissionType::getPermissionTypeBitMask(File::RESOURCE_ID, PermissionType::FILE_EDIT);
            $grantEditPermission = PermissionType::getPermissionTypeBitMask(
                File::RESOURCE_ID,
                PermissionType::FILE_GRANT_EDIT
            );
            $highestPermission = $this->getFileService()->getFileFolderHighestPermission($fileVersion->getFile());
            if (!($highestPermission & ($editPermission | $grantEditPermission))) {
                return $response->withStatus(self::STATUS_FORBIDDEN);
            }
        }

        $fileVersion = $fileService->updateFileVersion($fileVersion, $data, $auth->getIdentity());

        return $response->withJson($fileVersion);
    }

    /**
     * API call: POST /files/<ID>/copy
     *
     * @param $fileId
     * @throws NotFoundException
     * @throws \Exception
     * @throws \iCoordinator\Service\Exception\ConflictException
     */
    public function copyFileAction(Request $request, Response $response, $args)
    {
        $fileId = $args['file_id'];

        $acl = $this->getAcl();
        $auth = $this->getAuth();

        if (!$fileId) {
            return $response->withStatus(self::STATUS_BAD_REQUEST);
        }

        $fileService = $this->getContainer()->get('FileService');
        $file = $fileService->getFile($fileId);
        if (!$file) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }
        if ($file->getIsDeleted() || $file->getIsTrashed()) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }
        $data = $request->getParsedBody();

        //check if can copy(read) file
        $role = new UserRole($auth->getIdentity());
        $privilege = FilePrivilege::PRIVILEGE_READ;
        if (!$acl->isAllowed($role, $file, $privilege)) {
            $permission = PermissionType::getPermissionTypeBitMask(File::RESOURCE_ID, PermissionType::FILE_READ);
            if ($this->getFileService()->getFileFolderHighestPermission($file) < $permission) {
                return $response->withStatus(self::STATUS_FORBIDDEN);
            }
        }

        //check if can move to other folder
        $parentId = 0;
        if (empty($data['parent'])) {
            $parent = $file->getParent();
            if ($parent) {
                $parentId = $parent->getId();
            }
        } else {
            $parent = $data['parent'];
            $parentId = $parent['id'];
        }
        if ((int)$parentId == 0) {
            $privilege = WorkspacePrivilege::PRIVILEGE_CREATE_FILES;
            if (!$this->getAcl()->isAllowed($role, $file->getWorkspace(), $privilege)) {
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
            if ($this->getSelectiveSyncService()->getSelectiveSync($file, $auth->getIdentity())) {
                $msg = new Error(Error::ITEM_SYNC_DISABLED);
                return $response->withJson($msg, self::STATUS_FORBIDDEN);
            }
            if ($parentId > 0) {
                if ($this->getSelectiveSyncService()->getSelectiveSync($parentId, $auth->getIdentity())) {
                    $msg = new Error(Error::ITEM_SYNC_DISABLED);
                    return $response->withJson($msg, self::STATUS_FORBIDDEN);
                }
            }
        }

        $fileCopy = $fileService->copyFile($file, $auth->getIdentity(), $data);

        return $response->withJson($fileCopy);
    }

    /**
     * API call: PUT /files/<ID>/content
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function updateFileContentAction(Request $request, Response $response, $args)
    {
        $fileId = $args['file_id'];

        $acl = $this->getAcl();
        $auth = $this->getAuth();

        if (!$fileId) {
            return $response->withStatus(self::STATUS_BAD_REQUEST);
        }

        $fileService = $this->getContainer()->get('FileService');
        $file = $fileService->getFile($fileId);
        if (!$file) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }
        if ($file->getIsDeleted() || $file->getIsTrashed()) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }
        if ($file->getType() != 'file') {
            return $response->withStatus(self::STATUS_BAD_REQUEST);
        }
        $eTag = $request->getHeaderLine('If-Match');
        if ($eTag && $eTag != $file->getEtag()) {
            return $response->withStatus(
                self::STATUS_PRECONDITION_FAILED,
                "Version mismatch: ".$eTag." != ".$file->getEtag()
            );
        }

        //check if can modify file
        $role = new UserRole($auth->getIdentity());
        $privilege = FilePrivilege::PRIVILEGE_MODIFY;
        if (!$acl->isAllowed($role, $file, $privilege)) {
            $editPermission = PermissionType::getPermissionTypeBitMask(File::RESOURCE_ID, PermissionType::FILE_EDIT);
            $grantEditPermission = PermissionType::getPermissionTypeBitMask(
                File::RESOURCE_ID,
                PermissionType::FILE_GRANT_EDIT
            );
            $highestPermission = $this->getFileService()->getFileFolderHighestPermission($file);
            if (!($highestPermission & ($editPermission | $grantEditPermission))) {
                return $response->withStatus(self::STATUS_FORBIDDEN);
            }
        }
        if ($this->isDesktopClient($request)) {
            if ($this->getSelectiveSyncService()->getSelectiveSync($file, $auth->getIdentity())) {
                $msg = new Error(Error::ITEM_SYNC_DISABLED);
                return $response->withJson($msg, self::STATUS_FORBIDDEN);
            }
        }
        $data = $request->getParsedBody();
        $file = $fileService->updateFileContent($file, $auth->getIdentity(), $data);

        return $response->withJson($file, (isset($data['upload_id']) ? self::STATUS_ACCEPTED : self::STATUS_OK))
            ->withHeader('Access-Control-Expose-Headers', 'Heroku-dyno-id')
            ->withHeader('Heroku-dyno-id', getenv('HEROKU_DYNO_ID'));
    }

    /**
     * API call: GET /files/<ID>/versions
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function getFileVersionsAction(Request $request, Response $response, $args)
    {
        $fileId = $args['file_id'];

        $acl = $this->getAcl();
        $auth = $this->getAuth();

        if (!$fileId) {
            return $response->withStatus(self::STATUS_BAD_REQUEST);
        }

        $fileService = $this->getContainer()->get('FileService');
        $file = $fileService->getFile($fileId);
        if (!$file) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }
        if ($file->getIsDeleted() || $file->getIsTrashed()) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }
        $role = new UserRole($auth->getIdentity());
        $privilege = FilePrivilege::PRIVILEGE_READ_VERSIONS;
        if (!$acl->isAllowed($role, $file, $privilege)) {
            $permission = PermissionType::getPermissionTypeBitMask(File::RESOURCE_ID, PermissionType::FILE_READ);
            if ($this->getFileService()->getFileFolderHighestPermission($file) < $permission) {
                return $response->withStatus(self::STATUS_FORBIDDEN);
            }
        }

        $offset = $request->getParam('offset', 0);
        $limit = $request->getParam('limit', FileService::FILE_VERSIONS_LIMIT_DEFAULT);

        $hasMore = true;

        $paginator = $fileService->getFileVersions($file, $limit, $offset);
        if ($paginator->count() <= $offset + $limit) {
            $hasMore = false;
        }

        $result = array(
            'has_more' => $hasMore,
            'next_offset' => ($hasMore) ? $offset + $limit : null,
            'entries' => $paginator->getIterator()->getArrayCopy()
        );

        return $response->withJson($result);
    }

    /**
     * GET /files/{file_id}/path
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function getFilePathAction(Request $request, Response $response, $args)
    {
        $fileId = $args['file_id'];
        $pathInfo = $this->getFileService()->getFilePath($fileId);

        return $response->withJson($pathInfo);
    }
    /**
     * Route Middleware. Checks if workspace exists and  if user has privileges to do actions with a workspace
     *
     * @param Request $request
     * @param Response $response
     * @param callable $next
     * @return Response
     */
    public function preDispatchWorkspaceMiddleware(Request $request, Response $response, callable $next)
    {
        $workspaceId    = $request->getAttribute('route')->getArgument('workspace_id');
        $privilege  = $request->getAttribute('route')->getArgument(FilesRouteConfig::ARGUMENT_WORKSPACE_PRIVILEGE);

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
                return $response->withStatus(self::STATUS_FORBIDDEN);
            }
        }

        return $next($request->withAttribute('workspace', $workspace), $response);
    }

    /**
     * @return FileService
     */
    private function getFileService()
    {
        return $this->getContainer()->get('FileService');
    }

    /**
     * @return FolderService
     */
    private function getFolderService()
    {
        return $this->getContainer()->get('FolderService');
    }

    /**
     * @return SubscriptionService
     */
    private function getSubscriptionService()
    {
        return $this->getContainer()->get('SubscriptionService');
    }

    /**
     * @return WorkspaceService
     */
    private function getWorkspaceService()
    {
        return $this->getContainer()->get('WorkspaceService');
    }

    /**
     * @return DownloadTokenService
     */
    private function getDownloadTokenService()
    {
        return $this->getContainer()->get('DownloadTokenService');
    }

    /**
     * @return DownloadZipTokenService
     */
    private function getDownloadZipTokenService()
    {
        return $this->getContainer()->get('DownloadZipTokenService');
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

    /**
     * @return StateService
     */
    private function getStateService()
    {
        return $this->getContainer()->get('StateService');
    }

    private function isDesktopClient(Request $request)
    {
        $serverParams = $request->getServerParams();
        if (isset($serverParams['HTTP_AUTHORIZATION'])) {
            $token = str_replace("Bearer ", "", $serverParams['HTTP_AUTHORIZATION']);
            $accessToken = $this->getContainer()['entityManager']
                ->getRepository('\iCoordinator\Entity\OAuthAccessToken')
                ->findOneBy(
                    array(
                        'accessToken' => $token
                    )
                );
            return ($accessToken->getClientId() == 'icoordinator_desktop');
        } else {
            return false;
        }
    }

    private function hasInvalidChars($str)
    {
        return preg_match(self::INVALID_CHARACTERS, $str) || trim($str) != $str;
    }
}
