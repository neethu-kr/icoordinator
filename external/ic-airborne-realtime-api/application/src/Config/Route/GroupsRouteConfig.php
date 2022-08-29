<?php

namespace iCoordinator\Config\Route;

use iCoordinator\Config\AbstractConfig;
use iCoordinator\Permissions\Privilege\GroupPrivilege;
use iCoordinator\Permissions\Privilege\PortalPrivilege;
use iCoordinator\Permissions\Privilege\WorkspacePrivilege;
use Slim\App;
use Slim\CallableResolverAwareTrait;

class GroupsRouteConfig extends AbstractConfig
{
    use CallableResolverAwareTrait;

    const ROUTE_WORKSPACE_GROUPS_LIST = 'listWorkspaceGroupsEndpoint';
    const ROUTE_PORTAL_GROUPS_LIST = 'listPortalGroupsEndpoint';

    const ROUTE_WORKSPACE_GROUP_CREATE = 'createWorkspaceGroupEndpoint';
    const ROUTE_PORTAL_GROUP_CREATE = 'createPortalGroupEndpoint';

    const ROUTE_GROUP_GET = 'getGroupEndpoint';
    const ROUTE_GROUP_DELETE = 'deleteGroupEndpoint';
    const ROUTE_GROUP_UPDATE = 'updateGroupEndpoint';
    const ROUTE_GROUP_GET_GROUP_MEMBERSHIPS_LIST = 'getGroupMembershipsList';
    const ROUTE_GROUP_MEMBERSHIP_CREATE = 'createGroupMembership';
    const ROUTE_GROUP_MEMBERSHIP_DELETE = 'deleteGroupMembership';

    //available route arguments
    const ARGUMENT_GROUP_PRIVILEGE = 'group_privilege';
    const ARGUMENT_PORTAL_PRIVILEGE = 'portal_privilege';
    const ARGUMENT_WORKSPACE_PRIVILEGE = 'workspace_privilege';

    public function configure(App $app)
    {
        $this->container        = $app->getContainer();

        $groupMiddleware        = $this->resolveCallable('GroupsController:preDispatchGroupMiddleware');
        $portalMiddleware       = $this->resolveCallable('GroupsController:preDispatchPortalMiddleware');
        $workspaceMiddleWare    = $this->resolveCallable('GroupsController:preDispatchWorkspaceMiddleware');

        $app->get('/portals/{portal_id}/groups', 'GroupsController:getPortalGroupsListAction')
            ->add($portalMiddleware)
            ->setArgument(self::ARGUMENT_PORTAL_PRIVILEGE, PortalPrivilege::PRIVILEGE_READ_GROUPS)
            ->setName(self::ROUTE_PORTAL_GROUPS_LIST);

        $app->get('/workspaces/{workspace_id}/groups', 'GroupsController:getWorkspaceGroupsListAction')
            ->add($workspaceMiddleWare)
            ->setArgument(self::ARGUMENT_WORKSPACE_PRIVILEGE, WorkspacePrivilege::PRIVILEGE_READ_GROUPS)
            ->setName(self::ROUTE_WORKSPACE_GROUPS_LIST);

        $app->post('/portals/{portal_id}/groups', 'GroupsController:addPortalGroupAction')
            ->add($portalMiddleware)
            ->setArgument(self::ARGUMENT_PORTAL_PRIVILEGE, PortalPrivilege::PRIVILEGE_CREATE_GROUPS)
            ->setName(self::ROUTE_PORTAL_GROUP_CREATE);

        $app->post('/workspaces/{workspace_id}/groups', 'GroupsController:addWorkspaceGroupAction')
            ->add($workspaceMiddleWare)
            ->setArgument(self::ARGUMENT_WORKSPACE_PRIVILEGE, WorkspacePrivilege::PRIVILEGE_CREATE_GROUPS)
            ->setName(self::ROUTE_WORKSPACE_GROUP_CREATE);

        $app->get('/groups/{group_id}', 'GroupsController:getGroupAction')
            ->add($groupMiddleware)
            ->setArgument(self::ARGUMENT_GROUP_PRIVILEGE, GroupPrivilege::PRIVILEGE_READ)
            ->setName(self::ROUTE_GROUP_GET);

        $app->put('/groups/{group_id}', 'GroupsController:updateGroupAction')
            ->add($groupMiddleware)
            ->setArgument(self::ARGUMENT_GROUP_PRIVILEGE, GroupPrivilege::PRIVILEGE_MODIFY)
            ->setName(self::ROUTE_GROUP_UPDATE);

        $app->delete('/groups/{group_id}', 'GroupsController:deleteGroupAction')
            ->add($groupMiddleware)
            ->setArgument(self::ARGUMENT_GROUP_PRIVILEGE, GroupPrivilege::PRIVILEGE_DELETE)
            ->setName(self::ROUTE_GROUP_DELETE);

        $app->get('/groups/{group_id}/group-memberships', 'GroupsController:getGroupMembershipsAction')
            ->add($groupMiddleware)
            ->setArgument(self::ARGUMENT_GROUP_PRIVILEGE, GroupPrivilege::PRIVILEGE_READ_USERS)
            ->setName(self::ROUTE_GROUP_GET_GROUP_MEMBERSHIPS_LIST);

        $app->post('/group-memberships', 'GroupsController:createGroupMembershipAction')
            ->setName(self::ROUTE_GROUP_MEMBERSHIP_CREATE);

        $app->delete('/group-memberships/{group_membership_id}', 'GroupsController:deleteGroupMembershipAction')
            ->setName(self::ROUTE_GROUP_MEMBERSHIP_DELETE);
    }
}
