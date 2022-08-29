<?php

namespace iCoordinator\Config\Route;

use iCoordinator\Config\AbstractConfig;
use Slim\App;

class UsersRouteConfig extends AbstractConfig
{
    const ROUTE_USERS_LIST = 'listUsersEndpoint';
    const ROUTE_USER_CURRENT_GET = 'getCurrentUserEndpoint';
    const ROUTE_USER_GET = 'getUserEndpoint';
    const ROUTE_USER_DELETE = 'deleteUserEndpoint';
    const ROUTE_USER_UPDATE = 'updateUserEndpoint';
    const ROUTE_USER_GET_GROUPS_LIST = 'getUserGroupsList';
    const ROUTE_USER_GET_GROUP_MEMBERSHIPS_LIST = 'getUserGroupMembershipsList';
    const ROUTE_USER_RESET_PASSWORD = 'resetUserPasswordEndpoint';
    const ROUTE_USER_GET_WORKSPACES_LIST = 'getUserWorkspacesList';

    public function configure(App $app)
    {
        $app->get('/users/me', 'UsersController:getCurrentUserAction')
            ->setName(self::ROUTE_USER_CURRENT_GET);

        $app->post('/users/reset-password', 'UsersController:resetPasswordAction')
            ->setName(self::ROUTE_USER_RESET_PASSWORD);

        $app->get('/users/{user_id}', 'UsersController:getUserAction')
            ->setName(self::ROUTE_USER_GET);

        $app->put('/users/{user_id}', 'UsersController:updateUserAction')
            ->setName(self::ROUTE_USER_UPDATE);

        $app->delete('/users/{user_id}', 'UsersController:deleteUserAction')
            ->setName(self::ROUTE_USER_DELETE);

        $app->get('/users/{user_id}/portals/{portal_id}/groups', 'UsersController:getGroupsAction')
            ->setName(self::ROUTE_USER_GET_GROUPS_LIST);

        $app->get('/users/{user_id}/portals/{portal_id}/group-memberships', 'UsersController:getGroupMembershipsAction')
            ->setName(self::ROUTE_USER_GET_GROUP_MEMBERSHIPS_LIST);

        $app->get('/users/{user_id}/portals/{portal_id}/workspaces', 'WorkspacesController:getWorkspacesAction')
            ->setName(self::ROUTE_USER_GET_WORKSPACES_LIST);
    }
}
