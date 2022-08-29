<?php

namespace iCoordinator\Config\Route;

use iCoordinator\Config\AbstractConfig;
use iCoordinator\Permissions\Privilege\WorkspacePrivilege;
use Slim\App;
use Slim\CallableResolverAwareTrait;

class FilesRouteConfig extends AbstractConfig
{
    use CallableResolverAwareTrait;

    const ROUTE_FILE_GET = 'getFileEndpoint';
    const ROUTE_FILE_ADD = 'addFileEndpoint';
    const ROUTE_FILE_ADD_IN_FOLDER = 'addFileInFolderEndpoint';
    const ROUTE_FILE_DELETE = 'deleteFileEndpoint';
    const ROUTE_FILE_DELETE_PERMANENTLY = 'deleteFilePermanentlyEndpoint';
    const ROUTE_FILE_UPDATE = 'updateFileEndpoint';
    const ROUTE_FILE_COPY = 'copyFileEndpoint';
    const ROUTE_FILE_RESTORE = 'restoreFileEndpoint';
    const ROUTE_FILE_UPDATE_CONTENT = 'updateFileContentEndpoint';
    const ROUTE_FILE_GET_CONTENT_WITH_TOKEN = 'getFileContentWithTokenEndpoint';
    const ROUTE_FILE_GET_ZIP_WITH_TOKEN = 'getZipFileWithTokenEndpoint';
    const ROUTE_FILE_GET_CONTENT = 'getFileContentEndpoint';
    const ROUTE_FILE_GET_ZIP_CONTENT = 'getFileZipContentEndpoint';
    const ROUTE_FILE_VERSIONS_LIST = 'listFileVersionsEndpoint';
    const ROUTE_FILE_VERSION_UPDATE = 'updateFileVersionEndpoint';
    const ROUTE_FILE_GET_PERMISSION = 'getFilePermissionEndPoint';
    const ROUTE_FILE_PERMISSIONS_LIST = 'listFilePermissionsEndpoint';
    const ROUTE_FILE_PERMISSION_ADD = 'addFilePermissionEndpoint';
    const ROUTE_FILE_META_FIELDS_VALUES_GET_LIST = 'getMetaFieldsValuesListEndpoint';
    const ROUTE_FILE_META_FIELD_VALUE_ADD = 'addFileMetaFieldValueEndpoint';
    const ROUTE_FILE_EMAIL_OPTIONS_GET = 'getFileEmailOptions';
    const ROUTE_FILE_EMAIL_OPTIONS_SET = 'setFileEmailOptions';
    const ROUTE_FILE_CHUNKED_UPLOAD_CREATE = 'createChunkedUploadEndpoint';
    const ROUTE_FILE_CHUNKED_UPLOAD_IN_FOLDER = 'createChunkedUploadInFolderEndpoint';
    const ROUTE_FILE_CHUNKED_UPLOAD_CONTINUE = 'uploadFileChunkEndpoint';
    const ROUTE_FILE_SELECTIVE_SYNC_GET = 'getFileSelectiveSync';
    const ROUTE_FILE_SELECTIVE_SYNC_SET = 'setFileSelectiveSync';
    const ROUTE_FILE_SELECTIVE_SYNC_DELETE = 'deleteFileSelectiveSync';
    const ROUTE_FILE_GET_PATH = 'getFilePath';

    //available route arguments
    const ARGUMENT_FILE_PRIVILEGE = 'file_privilege';
    const ARGUMENT_WORKSPACE_PRIVILEGE = 'workspace_privilege';

    public function configure(App $app)
    {
        $this->container        = $app->getContainer();

        $workspaceMiddleWare    = $this->resolveCallable('FilesController:preDispatchWorkspaceMiddleware');

        $app->post('/folders/{folder_id}/files/content', 'FilesController:createFileAction')
            ->setName(self::ROUTE_FILE_ADD_IN_FOLDER);

        $app->post('/workspaces/{workspace_id}/files/content', 'FilesController:createFileAction')
            ->add($workspaceMiddleWare)
            ->setArgument(self::ARGUMENT_WORKSPACE_PRIVILEGE, WorkspacePrivilege::PRIVILEGE_CREATE_FILES)
            ->setName(self::ROUTE_FILE_ADD);

        $app->post('/folders/{folder_id}/files/uploads', 'FilesController:createFileUploadAction')
            ->setName(self::ROUTE_FILE_CHUNKED_UPLOAD_IN_FOLDER);

        $app->post('/workspaces/{workspace_id}/files/uploads', 'FilesController:createFileUploadAction')
            ->add($workspaceMiddleWare)
            ->setArgument(self::ARGUMENT_WORKSPACE_PRIVILEGE, WorkspacePrivilege::PRIVILEGE_CREATE_FILES)
            ->setName(self::ROUTE_FILE_CHUNKED_UPLOAD_CREATE);

        $app->get('/files/{file_id}/content/{token}', 'FilesController:downloadFileWithTokenAction')
            ->setName(self::ROUTE_FILE_GET_CONTENT_WITH_TOKEN);

        $app->get('/files/{token}/zip', 'FilesController:downloadZipWithTokenAction')
            ->setName(self::ROUTE_FILE_GET_ZIP_WITH_TOKEN);

        $app->map(['GET', 'OPTIONS'], '/files/{file_id}/content', 'FilesController:downloadFileAction')
            ->setName(self::ROUTE_FILE_GET_CONTENT);

        $app->map(['POST', 'OPTIONS'], '/files/zip', 'FilesController:downloadZipFileAction')
            ->setName(self::ROUTE_FILE_GET_ZIP_CONTENT);

        $app->post('/files/{file_id}/content', 'FilesController:updateFileContentAction')
            ->setName(self::ROUTE_FILE_UPDATE_CONTENT);

        $app->get('/files/{file_id}', 'FilesController:getFileAction')
            ->setName(self::ROUTE_FILE_GET);

        $app->delete('/files/{file_id}', 'FilesController:trashFileAction')
            ->setName(self::ROUTE_FILE_DELETE);

        $app->post('/files/{file_id}', 'FilesController:restoreFileAction')
            ->setName(self::ROUTE_FILE_RESTORE);

        $app->delete('/files/{file_id}/trash', 'FilesController:deleteFileAction')
            ->setName(self::ROUTE_FILE_DELETE_PERMANENTLY);

        $app->put('/files/{file_id}', 'FilesController:updateFileAction')
            ->setName(self::ROUTE_FILE_UPDATE);

        $app->post('/files/{file_id}/copy', 'FilesController:copyFileAction')
            ->setName(self::ROUTE_FILE_COPY);

        $app->get('/files/{file_id}/versions', 'FilesController:getFileVersionsAction')
            ->setName(self::ROUTE_FILE_VERSIONS_LIST);

        $app->get('/files/{file_id}/permission', 'FilesController:getFilePermissionAction')
            ->setName(self::ROUTE_FILE_GET_PERMISSION);

        $app->get('/files/{file_id}/permissions', 'PermissionsController:getFilePermissionsAction')
            ->setName(self::ROUTE_FILE_PERMISSIONS_LIST);

        $app->post('/files/{file_id}/permissions', 'PermissionsController:addFilePermissionAction')
            ->setName(self::ROUTE_FILE_PERMISSION_ADD);

        $app->get('/files/{file_id}/meta-fields-values', 'MetaFieldsController:getFileMetaFieldsValuesAction')
            ->setName(self::ROUTE_FILE_META_FIELDS_VALUES_GET_LIST);

        $app->post('/files/{file_id}/meta-fields-values', 'MetaFieldsController:addFileMetaFieldValueAction')
            ->setName(self::ROUTE_FILE_META_FIELD_VALUE_ADD);

        $app->get('/files/{file_id}/email-options', 'FileEmailOptionsController:getFileEmailOptionsAction')
            ->setName(self::ROUTE_FILE_EMAIL_OPTIONS_GET);

        $app->put('/files/{file_id}/email-options', 'FileEmailOptionsController:setFileEmailOptionsAction')
            ->setName(self::ROUTE_FILE_EMAIL_OPTIONS_SET);

        $app->post('/files/uploads/{upload_id}', 'FilesController:uploadFileChunkAction')
            ->setName(self::ROUTE_FILE_CHUNKED_UPLOAD_CONTINUE);

        $app->get('/files/{file_id}/selective-sync', 'SelectiveSyncController:getSelectiveSyncAction')
            ->setName(self::ROUTE_FILE_SELECTIVE_SYNC_GET);

        $app->put('/files/{file_id}/selective-sync', 'SelectiveSyncController:setSelectiveSyncAction')
            ->setName(self::ROUTE_FILE_SELECTIVE_SYNC_SET);

        $app->delete('/files/{file_id}/selective-sync', 'SelectiveSyncController:deleteSelectiveSyncAction')
            ->setName(self::ROUTE_FILE_SELECTIVE_SYNC_DELETE);

        $app->put('/files/version/{file_version_id}', 'FilesController:updateFileVersionAction')
            ->setName(self::ROUTE_FILE_VERSION_UPDATE);

        $app->get('/files/{file_id}/path', 'FilesController:getFilePathAction')
            ->setName(self::ROUTE_FILE_GET_PATH);
    }
}
