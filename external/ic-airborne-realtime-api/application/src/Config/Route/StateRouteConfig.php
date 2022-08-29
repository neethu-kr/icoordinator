<?php

namespace iCoordinator\Config\Route;

use iCoordinator\Config\AbstractConfig;
use iCoordinator\Permissions\Privilege\FilePrivilege;
use iCoordinator\Permissions\Privilege\WorkspacePrivilege;
use Slim\App;
use Slim\CallableResolverAwareTrait;

class StateRouteConfig extends AbstractConfig
{
    use CallableResolverAwareTrait;
    const ROUTE_STATE_GET = 'getStateEndpoint';
    const ROUTE_STATE_SAVE_GET = 'getSaveStateEndpoint';
    const ROUTE_STATE_CREATE_GET = 'getCreateStateEndpoint';
    const ROUTE_STATE_SAVED_GET = 'getSavedStateEndpoint';
    const ROUTE_FOLDER_STATE_GET = 'getFolderStateEndpoint';
    const ROUTE_FOLDER_TREE_STATE_GET = 'getFolderTreeStateEndpoint';
    const ROUTE_FOLDER_TREE_STATE_SAVE_GET = 'getSaveFolderTreeStateEndpoint';
    const ROUTE_FOLDER_TREE_STATE_CREATE_GET = 'getCreateFolderTreeStateEndpoint';
    const ROUTE_FOLDER_TREE_STATE_SAVED_GET = 'getSavedFolderTreeStateEndpoint';
    const ROUTE_WORKSPACE_STATE_GET = 'getWorkspaceStateEndpoint';
    const ROUTE_WORKSPACE_STATE_SAVE_GET = 'getSaveWorkspaceStateEndpoint';
    const ROUTE_WORKSPACE_STATE_CREATE_GET = 'getCreateWorkspaceStateEndpoint';
    const ROUTE_WORKSPACE_STATE_SAVED_GET = 'getSavedWorkspaceStateEndpoint';
    const ROUTE_PORTAL_STATE_GET = 'getPortalStateEndpoint';

    //available route arguments
    const ARGUMENT_FOLDER_PRIVILEGE = 'folder_privilege';
    const ARGUMENT_WORKSPACE_PRIVILEGE = 'workspace_privilege';

    public function configure(App $app)
    {
        $this->container        = $app->getContainer();

        $folderMiddleware       = $this->resolveCallable('FoldersController:preDispatchFolderMiddleware');
        $workspaceMiddleWare    = $this->resolveCallable('FoldersController:preDispatchWorkspaceMiddleware');

        $app->get('/state', 'StateController:getStateAction')
            ->setName(self::ROUTE_STATE_GET);
        $app->get('/saveState', 'StateController:saveStateAction')
            ->setName(self::ROUTE_STATE_SAVE_GET);
        $app->get('/createState', 'StateController:createStateAction')
            ->setName(self::ROUTE_STATE_CREATE_GET);
        $app->get('/getSavedState', 'StateController:getSavedStateAction')
            ->setName(self::ROUTE_STATE_SAVED_GET);
        $app->get('/state/{folder_id}/folder', 'StateController:getFolderStateAction')
            ->add($folderMiddleware)
            ->setArgument(self::ARGUMENT_FOLDER_PRIVILEGE, FilePrivilege::PRIVILEGE_READ)
            ->setName(self::ROUTE_FOLDER_STATE_GET);

        $app->get('/state/{folder_id}/folders', 'StateController:getFolderTreeStateAction')
            ->add($folderMiddleware)
            ->setArgument(self::ARGUMENT_FOLDER_PRIVILEGE, FilePrivilege::PRIVILEGE_READ)
            ->setName(self::ROUTE_FOLDER_TREE_STATE_GET);

        $app->get('/state/{folder_id}/saveFolders', 'StateController:saveFolderTreeStateAction')
            ->add($folderMiddleware)
            ->setArgument(self::ARGUMENT_FOLDER_PRIVILEGE, FilePrivilege::PRIVILEGE_READ)
            ->setName(self::ROUTE_FOLDER_TREE_STATE_SAVE_GET);
        $app->get('/state/{folder_id}/createFolders', 'StateController:createFolderTreeStateAction')
            ->add($folderMiddleware)
            ->setArgument(self::ARGUMENT_FOLDER_PRIVILEGE, FilePrivilege::PRIVILEGE_READ)
            ->setName(self::ROUTE_FOLDER_TREE_STATE_CREATE_GET);
        $app->get('/state/{folder_id}/getSavedFolders', 'StateController:getSavedFolderTreeStateAction')
            ->add($folderMiddleware)
            ->setArgument(self::ARGUMENT_FOLDER_PRIVILEGE, FilePrivilege::PRIVILEGE_READ)
            ->setName(self::ROUTE_FOLDER_TREE_STATE_SAVED_GET);

        $app->get('/state/{workspace_id}/workspace', 'StateController:getWorkspaceStateAction')
        ->add($workspaceMiddleWare)
        ->setArgument(self::ARGUMENT_WORKSPACE_PRIVILEGE, WorkspacePrivilege::PRIVILEGE_READ_SHARED_FILES)
        ->setName(self::ROUTE_WORKSPACE_STATE_GET);

        $app->get('/state/{workspace_id}/saveWorkspace', 'StateController:saveWorkspaceStateAction')
            ->add($workspaceMiddleWare)
            ->setArgument(self::ARGUMENT_WORKSPACE_PRIVILEGE, WorkspacePrivilege::PRIVILEGE_READ_SHARED_FILES)
            ->setName(self::ROUTE_WORKSPACE_STATE_SAVE_GET);
        $app->get('/state/{workspace_id}/createWorkspace', 'StateController:createWorkspaceStateAction')
            ->add($workspaceMiddleWare)
            ->setArgument(self::ARGUMENT_WORKSPACE_PRIVILEGE, WorkspacePrivilege::PRIVILEGE_READ_SHARED_FILES)
            ->setName(self::ROUTE_WORKSPACE_STATE_CREATE_GET);
        $app->get('/state/{workspace_id}/getSavedWorkspace', 'StateController:getSavedWorkspaceStateAction')
            ->add($workspaceMiddleWare)
            ->setArgument(self::ARGUMENT_WORKSPACE_PRIVILEGE, WorkspacePrivilege::PRIVILEGE_READ_SHARED_FILES)
            ->setName(self::ROUTE_WORKSPACE_STATE_SAVED_GET);

        $app->get('/state/{portal_id}/portal', 'StateController:getPortalStateAction')
            ->setName(self::ROUTE_PORTAL_STATE_GET);
    }
}
