<?php

namespace iCoordinator\Controller;

use iCoordinator\Config\Route\GroupsRouteConfig;
use iCoordinator\Permissions\Privilege\GroupMembershipPrivilege;
use iCoordinator\Permissions\Privilege\GroupPrivilege;
use iCoordinator\Permissions\Role\UserRole;
use iCoordinator\Service\GroupService;
use iCoordinator\Service\PortalService;
use iCoordinator\Service\UserService;
use iCoordinator\Service\WorkspaceService;
use Slim\Http\Request;
use Slim\Http\Response;

class GroupsController extends AbstractRestController
{
    /**
     * GET /portals/{portal_id}/groups
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function getPortalGroupsListAction(Request $request, Response $response, $args)
    {
        $portal = $request->getAttribute('portal');

        $offset = $request->getParam('offset', 0);
        $limit = $request->getParam('limit', GroupService::GROUPS_LIMIT_DEFAULT);
        $nextOffset = $offset + $limit;

        $paginator = $this->getGroupService()->getPortalGroups($portal, $limit, $offset);
        $hasMore = ($paginator->count() > $nextOffset);

        $result = array(
            'has_more' => $hasMore,
            'next_offset' => ($hasMore) ? $nextOffset : null,
            'entries' => $paginator->getIterator()->getArrayCopy()
        );

        return $response->withJson($result);
    }

    /**
     * GET /workspaces/{workspace_id}/groups
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function getWorkspaceGroupsListAction(Request $request, Response $response, $args)
    {
        $workspace = $request->getAttribute('workspace');

        $offset = $request->getParam('offset', 0);
        $limit = $request->getParam('limit', GroupService::GROUPS_LIMIT_DEFAULT);
        $nextOffset = $offset + $limit;

        $paginator = $this->getGroupService()->getWorkspaceGroups($workspace, $limit, $offset);
        $hasMore = ($paginator->count() > $nextOffset);

        $result = array(
            'has_more' => $hasMore,
            'next_offset' => ($hasMore) ? $nextOffset : null,
            'entries' => $paginator->getIterator()->getArrayCopy()
        );

        return $response->withJson($result);
    }

    /**
     * POST /portals/{portal_id}/groups
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     * @throws \iCoordinator\Service\Exception\ValidationFailedException
     */
    public function addPortalGroupAction(Request $request, Response $response, $args)
    {
        $portal     = $request->getAttribute('portal');
        $data       = $request->getParsedBody();
        $userId     = $this->getAuth()->getIdentity();

        $group = $this->getGroupService()->createPortalGroup($portal, $data, $userId);

        return $response->withJson($group, self::STATUS_CREATED);
    }

    /**
     * POST /workspaces/{workspace_id}/groups
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     * @throws \iCoordinator\Service\Exception\ValidationFailedException
     */
    public function addWorkspaceGroupAction(Request $request, Response $response, $args)
    {
        $workspace  = $request->getAttribute('workspace');
        $data       = $request->getParsedBody();
        $userId     = $this->getAuth()->getIdentity();

        $group = $this->getGroupService()->createWorkspaceGroup($workspace, $data, $userId);

        return $response->withJson($group, self::STATUS_CREATED);
    }

    /**
     * GET /groups/{group_id}
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function getGroupAction(Request $request, Response $response, $args)
    {
        $group = $request->getAttribute('group');

        return $response->withJson($group);
    }

    /**
     * DELETE /groups/{group_id}
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     * @throws \iCoordinator\Service\Exception\ConflictException
     * @throws \iCoordinator\Service\Exception\NotFoundException
     */
    public function deleteGroupAction(Request $request, Response $response, $args)
    {
        $userId     = $this->getAuth()->getIdentity();
        $group = $request->getAttribute('group');

        $this->getGroupService()->deleteGroup($group, $userId);

        return $response->withStatus(self::STATUS_NO_CONTENT);
    }

    /**
     * PUT /groups/{group_id}
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     * @throws \iCoordinator\Service\Exception\NotFoundException
     */
    public function updateGroupAction(Request $request, Response $response, $args)
    {
        $group  = $request->getAttribute('group');
        $data   = $request->getParsedBody();
        $userId     = $this->getAuth()->getIdentity();

        $group = $this->getGroupService()->updateGroup($group, $data, $userId);

        return $response->withJson($group);
    }

    /**
     * POST /group-memberships
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     * @throws \iCoordinator\Service\Exception\NotFoundException
     */
    public function createGroupMembershipAction(Request $request, Response $response, $args)
    {
        $userId     = $this->getAuth()->getIdentity();
        $data = $request->getParsedBody();

        if (empty($data['user']) || empty($data['group']) ||
            empty($data['user']['id']) || empty($data['group']['id'])
        ) {
            return $response->withJson(400);
        }

        $group  = $this->getGroupService()->getGroup($data['group']['id']);
        $user   = $this->getUserService()->getUser($data['user']['id']);

        if (!$group || !$user) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $auth = $this->getAuth();
        $acl = $this->getAcl();

        if (!$acl->isAllowed(new UserRole($auth->getIdentity()), $group, GroupPrivilege::PRIVILEGE_MANAGE_USERS)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }

        if (!$acl->isAllowed(new UserRole($user), $group, GroupPrivilege::PRIVILEGE_BECOME_MEMBER)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }

        $groupMembership = $this->getGroupService()->createGroupMembership($group, $user, $userId);

        return $response->withJson($groupMembership, self::STATUS_CREATED);
    }

    /**
     * DELETE /group-memberships/{group_membership_id}
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     * @throws \iCoordinator\Service\Exception\NotFoundException
     */
    public function deleteGroupMembershipAction(Request $request, Response $response, $args)
    {
        $groupMembershipId = $args['group_membership_id'];

        $acl = $this->getAcl();
        $auth = $this->getAuth();
        $userId     = $this->getAuth()->getIdentity();

        if (!$groupMembershipId) {
            return $response->withStatus(self::STATUS_BAD_REQUEST);
        }
        
        $groupMembership = $this->getGroupService()->getGroupMembership($groupMembershipId);

        if (!$groupMembership) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $role = new UserRole($auth->getIdentity());
        $privilege = GroupMembershipPrivilege::PRIVILEGE_DELETE;
        if (!$acl->isAllowed($role, $groupMembership, $privilege)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }

        $this->getGroupService()->deleteGroupMembership($groupMembership, $userId);

        return $response->withStatus(self::STATUS_NO_CONTENT);
    }

    /**
     * GET /groups/{group_id}/group-memberships
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function getGroupMembershipsAction(Request $request, Response $response, $args)
    {
        $group = $request->getAttribute('group');

        $offset = $request->getParam('offset', 0);
        $limit = $request->getParam('limit', GroupService::GROUP_MEMBERSHIPS_LIMIT_DEFAULT);
        $nextOffset = $offset + $limit;

        $paginator = $this->getGroupService()->getGroupMemberships($group, $limit, $offset);
        $hasMore = ($paginator->count() > $nextOffset);

        $result = array(
            'has_more' => $hasMore,
            'next_offset' => ($hasMore) ? $nextOffset : null,
            'entries' => $paginator->getIterator()->getArrayCopy()
        );

        return $response->withJson($result);
    }

    /**
     * Route Middleware. Checks if group exists and in user has privileges to do actions with a group.
     *
     * @param Request $request
     * @param Response $response
     * @param callable $next
     * @return Response
     */
    public function preDispatchGroupMiddleware(Request $request, Response $response, callable $next)
    {
        $groupId    = $request->getAttribute('route')->getArgument('group_id');
        $privilege  = $request->getAttribute('route')->getArgument(GroupsRouteConfig::ARGUMENT_GROUP_PRIVILEGE);

        if (!$groupId) {
            return $response->withStatus(AbstractRestController::STATUS_BAD_REQUEST);
        }

        $group = $this->getGroupService()->getGroup($groupId);

        if (!$group) {
            return $response->withStatus(AbstractRestController::STATUS_NOT_FOUND);
        }

        $role = new UserRole($this->getAuth()->getIdentity());

        if ($privilege) {
            if (!$this->getAcl()->isAllowed($role, $group, $privilege)) {
                return $response->withStatus(AbstractRestController::STATUS_FORBIDDEN);
            }
        }

        return $next($request->withAttribute('group', $group), $response);
    }

    /**
     * Route Middleware. Checks if portal exists and  if user has privileges to do actions with a portal
     *
     * @param Request $request
     * @param Response $response
     * @param callable $next
     * @return Response
     */
    public function preDispatchPortalMiddleware(Request $request, Response $response, callable $next)
    {
        $portalId    = $request->getAttribute('route')->getArgument('portal_id');
        $privilege  = $request->getAttribute('route')->getArgument(GroupsRouteConfig::ARGUMENT_PORTAL_PRIVILEGE);

        if (!$portalId) {
            return $response->withStatus(AbstractRestController::STATUS_BAD_REQUEST);
        }

        $portal = $this->getPortalService()->getPortal($portalId);

        if (!$portal) {
            return $response->withStatus(AbstractRestController::STATUS_NOT_FOUND);
        }

        $role = new UserRole($this->getAuth()->getIdentity());

        if ($privilege) {
            if (!$this->getAcl()->isAllowed($role, $portal, $privilege)) {
                return $response->withStatus(AbstractRestController::STATUS_FORBIDDEN);
            }
        }

        return $next($request->withAttribute('portal', $portal), $response);
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
        $privilege  = $request->getAttribute('route')->getArgument(GroupsRouteConfig::ARGUMENT_WORKSPACE_PRIVILEGE);

        if (!$workspaceId) {
            return $response->withStatus(AbstractRestController::STATUS_BAD_REQUEST);
        }

        $workspace = $this->getWorkspaceService()->getWorkspace($workspaceId);

        if (!$workspace) {
            return $response->withStatus(AbstractRestController::STATUS_NOT_FOUND);
        }

        $role = new UserRole($this->getAuth()->getIdentity());

        if ($privilege) {
            if (!$this->getAcl()->isAllowed($role, $workspace, $privilege)) {
                return $response->withStatus(AbstractRestController::STATUS_FORBIDDEN);
            }
        }

        return $next($request->withAttribute('workspace', $workspace), $response);
    }


    /**
     * @return GroupService
     */
    private function getGroupService()
    {
        return $this->getContainer()->get('GroupService');
    }

    /**
     * @return UserService
     */
    private function getUserService()
    {
        return $this->getContainer()->get('UserService');
    }

    /**
     * @return PortalService
     */
    private function getPortalService()
    {
        return $this->getContainer()->get('PortalService');
    }

    /**
     * @return WorkspaceService
     */
    private function getWorkspaceService()
    {
        return $this->getContainer()->get('WorkspaceService');
    }
}
