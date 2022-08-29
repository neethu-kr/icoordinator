<?php

namespace iCoordinator\Config\Route;

use iCoordinator\Config\AbstractConfig;
use iCoordinator\Permissions\Privilege\FilePrivilege;
use iCoordinator\Permissions\Privilege\WorkspacePrivilege;
use Slim\App;
use Slim\CallableResolverAwareTrait;

class FoldersRouteConfig extends AbstractConfig
{
    use CallableResolverAwareTrait;

    const ROUTE_FOLDER_GET = 'getFolderEndpoint';
    const ROUTE_FOLDER_ADD = 'addFolderEndpoint';
    const ROUTE_FOLDER_DELETE = 'deleteFolderEndpoint';
    const ROUTE_FOLDER_DELETE_PERMANENTLY = 'deleteFolderPermanentlyEndpoint';
    const ROUTE_FOLDER_UPDATE = 'updateFolderEndpoint';
    const ROUTE_FOLDER_RESTORE = 'restoreFolderEndpoint';
    const ROUTE_FOLDER_COPY = 'copyFolderEndpoint';
    const ROUTE_FOLDER_CHILDREN_GET = 'getFolderChildrenEndpoint';
    const ROUTE_ROOT_FOLDER_CHILDREN_GET = 'getRootFolderChildrenEndpoint';
    const ROUTE_TRASH_FOLDER_CHILDREN_GET = 'getTrashFolderChildrenEndpoint';
    const ROUTE_FOLDER_GET_PERMISSION = 'getFolderPermissionEndPoint';
    const ROUTE_FOLDER_PERMISSIONS_LIST = 'listFolderPermissionsEndpoint';
    const ROUTE_FOLDER_PERMISSION_ADD = 'addFolderPermissionEndpoint';
    const ROUTE_FOLDER_META_FIELD_VALUE_ADD = 'addFolderMetaFieldValueEndpoint';
    const ROUTE_FOLDER_META_FIELDS_VALUES_GET_LIST = 'getFolderMetaFieldsValuesListEndpoint';
    const ROUTE_FOLDER_INBOUND_EMAIL_GET = 'getFolderInboundEmail';
    const ROUTE_FOLDER_EMAIL_OPTIONS_GET = 'getFolderEmailOptions';
    const ROUTE_FOLDER_EMAIL_OPTIONS_SET = 'setFolderEmailOptions';
    const ROUTE_FOLDER_SELECTIVE_SYNC_GET = 'getFolderSelectiveSync';
    const ROUTE_FOLDER_SELECTIVE_SYNC_SET = 'setFolderSelectiveSync';
    const ROUTE_FOLDER_SELECTIVE_SYNC_DELETE = 'removeFolderSelectiveSync';
    const ROUTE_FOLDER_GET_PATH = 'getFolderPath';

    //available route arguments
    const ARGUMENT_FOLDER_PRIVILEGE = 'folder_privilege';
    const ARGUMENT_WORKSPACE_PRIVILEGE = 'workspace_privilege';

    public function configure(App $app)
    {
        $this->container        = $app->getContainer();

        $folderMiddleware       = $this->resolveCallable('FoldersController:preDispatchFolderMiddleware');
        $workspaceMiddleWare    = $this->resolveCallable('FoldersController:preDispatchWorkspaceMiddleware');

        $app->get('/folders/{folder_id}', 'FoldersController:getFolderAction')
            ->add($folderMiddleware)
            ->setArgument(self::ARGUMENT_FOLDER_PRIVILEGE, FilePrivilege::PRIVILEGE_READ)
            ->setName(self::ROUTE_FOLDER_GET);

        $app->get('/workspaces/{workspace_id}/root-folder/children', 'FoldersController:getRootFolderChildrenAction')
            ->add($workspaceMiddleWare)
            ->setArgument(self::ARGUMENT_WORKSPACE_PRIVILEGE, WorkspacePrivilege::PRIVILEGE_READ_SHARED_FILES)
            ->setName(self::ROUTE_ROOT_FOLDER_CHILDREN_GET);

        $app->get('/workspaces/{workspace_id}/trash/children', 'FoldersController:getTrashFolderChildrenAction')
            ->add($workspaceMiddleWare)
            ->setArgument(self::ARGUMENT_WORKSPACE_PRIVILEGE, WorkspacePrivilege::PRIVILEGE_READ_SHARED_FILES)
            ->setName(self::ROUTE_TRASH_FOLDER_CHILDREN_GET);

        $app->get('/folders/{folder_id}/children', 'FoldersController:getFolderChildrenAction')
            ->add($folderMiddleware)
            ->setArgument(self::ARGUMENT_FOLDER_PRIVILEGE, FilePrivilege::PRIVILEGE_READ)
            ->setName(self::ROUTE_FOLDER_CHILDREN_GET);

        $app->post('/workspaces/{workspace_id}/folders', 'FoldersController:addFolderAction')
            ->add($workspaceMiddleWare)
            ->setArgument(self::ARGUMENT_FOLDER_PRIVILEGE, WorkspacePrivilege::PRIVILEGE_CREATE_FOLDERS)
            ->setName(self::ROUTE_FOLDER_ADD);

        $app->delete('/folders/{folder_id}', 'FoldersController:trashFolderAction')
            ->add($folderMiddleware)
            ->setArgument(self::ARGUMENT_FOLDER_PRIVILEGE, FilePrivilege::PRIVILEGE_DELETE)
            ->setName(self::ROUTE_FOLDER_DELETE);

        $app->delete('/folders/{folder_id}/trash', 'FoldersController:deleteFolderAction')
            ->add($folderMiddleware)
            ->setArgument(self::ARGUMENT_FOLDER_PRIVILEGE, FilePrivilege::PRIVILEGE_DELETE)
            ->setName(self::ROUTE_FOLDER_DELETE_PERMANENTLY);

        $app->post('/folders/{folder_id}', 'FoldersController:restoreFolderAction')
            ->add($folderMiddleware)
            ->setArgument(self::ARGUMENT_FOLDER_PRIVILEGE, FilePrivilege::PRIVILEGE_MODIFY)
            ->setName(self::ROUTE_FOLDER_RESTORE);

        $app->put('/folders/{folder_id}', 'FoldersController:updateFolderAction')
            ->add($folderMiddleware)
            ->setArgument(self::ARGUMENT_FOLDER_PRIVILEGE, FilePrivilege::PRIVILEGE_MODIFY)
            ->setName(self::ROUTE_FOLDER_UPDATE);

        $app->post('/folders/{folder_id}/copy', 'FoldersController:copyFolderAction')
            ->add($folderMiddleware)
            ->setArgument(self::ARGUMENT_FOLDER_PRIVILEGE, FilePrivilege::PRIVILEGE_READ)
            ->setName(self::ROUTE_FOLDER_COPY);

        $app->get('/folders/{folder_id}/permission', 'FoldersController:getFolderPermissionAction')
            ->setName(self::ROUTE_FOLDER_GET_PERMISSION);

        $app->get('/folders/{folder_id}/permissions', 'PermissionsController:getFolderPermissionsAction')
            ->setName(self::ROUTE_FOLDER_PERMISSIONS_LIST);

        $app->post('/folders/{folder_id}/permissions', 'PermissionsController:addFolderPermissionAction')
            ->setName(self::ROUTE_FOLDER_PERMISSION_ADD);

        $app->get('/folders/{folder_id}/meta-fields-values', 'MetaFieldsController:getFolderMetaFieldsValuesAction')
            ->setName(self::ROUTE_FOLDER_META_FIELDS_VALUES_GET_LIST);

        $app->post('/folders/{folder_id}/meta-fields-values', 'MetaFieldsController:addFolderMetaFieldValueAction')
            ->setName(self::ROUTE_FOLDER_META_FIELD_VALUE_ADD);

        $app->get('/folders/{folder_id}/inbound-email', 'InboundEmailsController:getFolderInboundEmailAction')
            ->setName(self::ROUTE_FOLDER_INBOUND_EMAIL_GET);

        $app->get('/folders/{folder_id}/email-options', 'FileEmailOptionsController:getFolderEmailOptionsAction')
            ->setName(self::ROUTE_FOLDER_EMAIL_OPTIONS_GET);

        $app->put('/folders/{folder_id}/email-options', 'FileEmailOptionsController:setFolderEmailOptionsAction')
            ->setName(self::ROUTE_FOLDER_EMAIL_OPTIONS_SET);

        $app->get('/folders/{folder_id}/selective-sync', 'SelectiveSyncController:getSelectiveSyncAction')
            ->setName(self::ROUTE_FOLDER_SELECTIVE_SYNC_GET);

        $app->put('/folders/{folder_id}/selective-sync', 'SelectiveSyncController:setSelectiveSyncAction')
            ->setName(self::ROUTE_FOLDER_SELECTIVE_SYNC_SET);

        $app->delete('/folders/{folder_id}/selective-sync', 'SelectiveSyncController:deleteSelectiveSyncAction')
            ->setName(self::ROUTE_FOLDER_SELECTIVE_SYNC_DELETE);

        $app->get('/folders/{folder_id}/path', 'FoldersController:getFolderPathAction')
            ->setName(self::ROUTE_FOLDER_GET_PATH);
    }
}
