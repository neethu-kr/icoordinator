<?php

namespace iCoordinator\Controller;

use iCoordinator\Permissions\Privilege\PortalPrivilege;
use iCoordinator\Permissions\Privilege\UserPrivilege;
use iCoordinator\Permissions\Privilege\WorkspacePrivilege;
use iCoordinator\Permissions\Resource\SystemResource;
use iCoordinator\Permissions\Role\UserRole;
use iCoordinator\Service\UserService;
use Slim\Http\Request;
use Slim\Http\Response;

class UsersController extends AbstractRestController
{
    public function getUsersListAction(Request $request, Response $response, $args)
    {
        $auth = $this->getAuth();
        $acl = $this->getAcl();

        $offset = $request->getParam('offset', 0);
        $limit = $request->getParam('limit', UserService::USERS_LIMIT_DEFAULT);

        $userService = $this->getContainer()->get('UserService');

        $hasMore = true;
        $nextOffset = $offset + $limit;

        $paginator = $userService->getUsers($limit, $offset);
        if ($paginator->count() <= $offset  + $paginator->getIterator()->count()) {
            $hasMore = false;
        }

        $result = array(
            'has_more' => $hasMore,
            'next_offset' => ($hasMore) ? $nextOffset : null,
            'entries' => $paginator->getIterator()->getArrayCopy()
        );

        return $response->withJson($result);
    }


    public function getWorkspaceUsersListAction(Request $request, Response $response, $args)
    {
        $workspaceId = $args['workspace_id'];

        $auth = $this->getAuth();
        $acl = $this->getAcl();

        $workspaceService = $this->getContainer()->get('WorkspaceService');
        $workspace = $workspaceService->getWorkspace($workspaceId);

        if (!$workspace) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $role = new UserRole($auth->getIdentity());
        $privilege = WorkspacePrivilege::PRIVILEGE_READ_USERS;
        if (!$acl->isAllowed($role, $workspace, $privilege)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }

        $users = $workspaceService->getWorkspaceUsers($workspace);

        return $response->withJson($users);
    }


    public function getPortalUsersListAction(Request $request, Response $response, $args)
    {
        $portalId = $args['portal_id'];

        $auth = $this->getAuth();
        $acl = $this->getAcl();

        $portalService = $this->getContainer()->get('PortalService');
        $portal = $portalService->getPortal($portalId);

        if (!$portal) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $role = new UserRole($auth->getIdentity());
        $privilege = PortalPrivilege::PRIVILEGE_READ_USERS;
        if (!$acl->isAllowed($role, $portal, $privilege)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }

        $users = $portalService->getPortalUsers($portal);

        return $response->withJson($users);
    }

    public function getCurrentUserAction(Request $request, Response $response, $args)
    {
        return $this->getUserAction($request, $response, array('user_id' => $this->getAuth()->getIdentity()));
    }

    public function getUserAction(Request $request, Response $response, $args)
    {
        $userId = $args['user_id'];

        $auth = $this->getAuth();
        $acl = $this->getAcl();

        $userService = $this->getContainer()->get('UserService');
        $user = $userService->getUser($userId);

        if (!$user) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $role = new UserRole($auth->getIdentity());
        $privilege = UserPrivilege::PRIVILEGE_READ;
        if (!$acl->isAllowed($role, $user, $privilege)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }

        return $response->withJson($user);
    }

    public function deleteUserAction(Request $request, Response $response, $args)
    {
        $userId = $args['user_id'];

        $acl = $this->getAcl();
        $auth = $this->getAuth();

        if (!$userId) {
            return $response->withStatus(self::STATUS_BAD_REQUEST);
        }

        $userService = $this->getContainer()->get('UserService');
        $user = $userService->getUser($userId);

        if (!$user) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $role = new UserRole($auth->getIdentity());
        $privilege = UserPrivilege::PRIVILEGE_DELETE;
        if (!$acl->isAllowed($role, $user, $privilege)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }

        $userService->deleteUser($user, $auth->getIdentity());

        return $response->withStatus(self::STATUS_NO_CONTENT);
    }

    public function updateUserAction(Request $request, Response $response, $args)
    {
        $userId = $args['user_id'];

        $acl = $this->getAcl();
        $auth = $this->getAuth();

        if (!$userId) {
            return $response->withStatus(self::STATUS_BAD_REQUEST);
        }

        $userService = $this->getContainer()->get('UserService');
        $user = $userService->getUser($userId);

        if (!$user) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $data = $request->getParsedBody();

        if ($userId != $auth->getIdentity() && in_array($auth->getIdentity(), explode(',', getenv('SUPERADMIN')))) {
            $role = new UserRole($auth->getIdentity());
            $privilege = PortalPrivilege::PRIVILEGE_GRANT_ADMIN_PERMISSION;
            if (!$acl->isAllowed($role, SystemResource::RESOURCE_ID, $privilege)) {
                return $response->withStatus(self::STATUS_FORBIDDEN);
            }
        } else {
            $role = new UserRole($auth->getIdentity());
            $privilege = UserPrivilege::PRIVILEGE_MODIFY;
            if (!$acl->isAllowed($role, $user, $privilege)) {
                return $response->withStatus(self::STATUS_FORBIDDEN);
            }
        }
        $user = $userService->updateUser($user, $data, $auth->getIdentity());

        return $response->withJson($user, self::STATUS_OK);
    }

    public function getGroupMembershipsAction(Request $request, Response $response, $args)
    {
        $userId = $args['user_id'];
        $portalId = $args['portal_id'];

        $acl = $this->getAcl();
        $auth = $this->getAuth();

        $userService = $this->getContainer()->get('UserService');
        $user = $userService->getUser($userId);

        if (!$user) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $portalService = $this->getContainer()->get('PortalService');
        $portal = $portalService->getPortal($portalId);

        if (!$portal) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $role = new UserRole($auth->getIdentity());
        $privilege = PortalPrivilege::PRIVILEGE_READ_USER_GROUPS;
        if (!$acl->isAllowed($role, $portal, $privilege)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }

        $offset = $request->getParam('offset', 0);
        $limit = $request->getParam('limit', UserService::GROUP_MEMBERSHIPS_LIMIT_DEFAULT);

        $paginator = $userService->getUserGroupMemberships($user, $portal, $limit, $offset);
        if ($paginator->count() <= $offset  + $paginator->getIterator()->count()) {
            $hasMore = false;
        } else {
            $hasMore = true;
        }

        $result = array(
            'has_more' => $hasMore,
            'next_offset' => ($hasMore) ? $limit : null,
            'entries' => $paginator->getIterator()->getArrayCopy()
        );

        return $response->withJson($result);
    }


    public function getGroupsAction(Request $request, Response $response, $args)
    {
        $userId = $args['user_id'];
        $portalId = $args['portal_id'];

        $acl = $this->getAcl();
        $auth = $this->getAuth();

        $userService = $this->getContainer()->get('UserService');
        $user = $userService->getUser($userId);

        if (!$user) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $portalService = $this->getContainer()->get('PortalService');
        $portal = $portalService->getPortal($portalId);

        if (!$portal) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $role = new UserRole($auth->getIdentity());
        $privilege = PortalPrivilege::PRIVILEGE_READ_USER_GROUPS;
        if (!$acl->isAllowed($role, $portal, $privilege)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }

        $offset = $request->getParam('offset', 0);
        $limit = $request->getParam('limit', UserService::GROUPS_LIMIT_DEFAULT);

        $paginator = $userService->getUserGroups($user, $portal, $limit, $offset);
        if ($paginator->count() <= $offset  + $paginator->getIterator()->count()) {
            $hasMore = false;
        } else {
            $hasMore = true;
        }

        $result = array(
            'has_more' => $hasMore,
            'next_offset' => ($hasMore) ? $limit : null,
            'entries' => $paginator->getIterator()->getArrayCopy()
        );

        return $response->withJson($result);
    }

    public function resetPasswordAction(Request $request, Response $response, $args)
    {
        $data = $request->getParsedBody();
        $email = $data['email'];
        $userService = $this->getContainer()->get('UserService');
        $user = $userService->getUserByEmail($email);

        if (!$user) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $user = $userService->userPasswordReset($user);


        return $response->withJson($user);
    }
}
