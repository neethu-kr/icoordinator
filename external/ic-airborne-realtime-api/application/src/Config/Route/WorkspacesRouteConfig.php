<?php

namespace iCoordinator\Config\Route;

use iCoordinator\Config\AbstractConfig;
use Slim\App;

class WorkspacesRouteConfig extends AbstractConfig
{
    const ROUTE_WORKSPACES_LIST = 'listWorkspacesEndpoint';
    const ROUTE_WORKSPACE_GET = 'getWorkspaceEndpoint';
    const ROUTE_WORKSPACE_ADD = 'addWorkspaceEndpoint';
    const ROUTE_WORKSPACE_DELETE = 'deleteWorkspaceEndpoint';
    const ROUTE_WORKSPACE_UPDATE = 'updateWorkspaceEndpoint';
    const ROUTE_WORKSPACE_COPY = 'copyWorkspaceEndpoint';
    const ROUTE_WORKSPACE_PERMISSIONS_LIST = 'listWorkspacePermissionsEndpoint';
    const ROUTE_WORKSPACE_PERMISSION_ADD = 'addWorkspacePermissionEndpoint';
    const ROUTE_WORKSPACE_USERS_LIST = 'listWorkspaceUsersEndpoint';
    const ROUTE_WORKSPACE_INBOUND_EMAIL_GET = 'getWorkspaceInboundEmail';
    const ROUTE_WORKSPACE_GET_INFO = 'getWorkspaceInfo';

    public function configure(App $app)
    {
        $app->get('/portals/{portal_id}/workspaces', 'WorkspacesController:getWorkspacesListAction')
            ->setName(self::ROUTE_WORKSPACES_LIST);

        $app->get('/workspaces/{workspace_id}', 'WorkspacesController:getWorkspaceAction')
            ->setName(self::ROUTE_WORKSPACE_GET);

        $app->post('/portals/{portal_id}/workspaces', 'WorkspacesController:addWorkspaceAction')
            ->setName(self::ROUTE_WORKSPACE_ADD);

        $app->delete('/workspaces/{workspace_id}', 'WorkspacesController:deleteWorkspaceAction')
            ->setName(self::ROUTE_WORKSPACE_DELETE);

        $app->put('/workspaces/{workspace_id}', 'WorkspacesController:updateWorkspaceAction')
            ->setName(self::ROUTE_WORKSPACE_UPDATE);

        $app->post('/workspaces/{workspace_id}/copy', 'WorkspacesController:copyWorkspaceAction')
            ->setName(self::ROUTE_WORKSPACE_COPY);

        $app->get('/workspaces/{workspace_id}/permissions', 'PermissionsController:getWorkspacePermissionsAction')
            ->setName(self::ROUTE_WORKSPACE_PERMISSIONS_LIST);

        $app->post('/workspaces/{workspace_id}/permissions', 'PermissionsController:addWorkspacePermissionAction')
            ->setName(self::ROUTE_WORKSPACE_PERMISSION_ADD);

        $app->get('/workspaces/{workspace_id}/users', 'UsersController:getWorkspaceUsersListAction')
            ->setName(self::ROUTE_WORKSPACE_USERS_LIST);

        $app->get('/workspaces/{workspace_id}/inbound-email', 'InboundEmailsController:getWorkspaceInboundEmailAction')
            ->setName(self::ROUTE_WORKSPACE_INBOUND_EMAIL_GET);

        $app->get('/workspaces/{workspace_id}/info', 'WorkspacesController:getWorkspaceInfoAction')
            ->setName(self::ROUTE_WORKSPACE_GET_INFO);
    }
}
