<?php

namespace iCoordinator\Controller;

use iCoordinator\Entity\Error;
use iCoordinator\Permissions\Privilege\PortalPrivilege;
use iCoordinator\Permissions\Privilege\WorkspacePrivilege;
use iCoordinator\Permissions\Role\UserRole;
use iCoordinator\Service\PortalService;
use iCoordinator\Service\SubscriptionService;
use iCoordinator\Service\WorkspaceService;
use Slim\Http\Request;
use Slim\Http\Response;

class WorkspacesController extends AbstractRestController
{
    const WORKSPACES_FILTER_ACCESSIBLE = 'accessible';
    const WORKSPACES_FILTER_ALL = 'all';
    const WORKSPACES_COPY_FILE_FOLDER_LIMIT = 1000;
    public function getWorkspaceInfoAction(Request $request, Response $response, $args)
    {
        $acl = $this->getAcl();
        $auth = $this->getAuth();

        $role = new UserRole($auth->getIdentity());
        $workspaceService = $this->getContainer()->get('WorkspaceService');
        $workspaceId = $args['workspace_id'];
        if (!$workspaceId) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }
        $workspace = $workspaceService->getWorkspace($workspaceId);
        $portal = $workspace->getPortal();
        $userIds = $workspaceService->getWorkspaceUserIds($workspace);

        if (!$acl->isAllowed($role, $portal, PortalPrivilege::PRIVILEGE_MANAGE_WORKSPACES)) {
            // return info for normal user
            $workspaceInfo = array(
                "userCount" => count($userIds),
                "usedStorage" => null
            );
        } else {
            // return extended info for admin and portal owner
            $usedStorage = $workspaceService->getUsedStorage($workspaceId);
            $workspaceInfo = array(
                "userCount" => count($userIds),
                "usedStorage" => $usedStorage
            );
        }
        return $response->withJson(json_encode($workspaceInfo));
    }
    public function getWorkspacesListAction(Request $request, Response $response, $args)
    {
        $portalId   = $args['portal_id'];
        $filter     = $request->getParam('filter');
        $userId     = $this->getAuth()->getIdentity();
        $acl        = $this->getAcl();
        $role       = new UserRole($userId);

        $portal = $this->getPortalService()->getPortal($portalId);
        if (!$portal) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        if (!$acl->isAllowed($role, $portal, PortalPrivilege::PRIVILEGE_READ_WORKSPACES)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }

        if (!$acl->isAllowed($role, $portal, PortalPrivilege::PRIVILEGE_MANAGE_WORKSPACES)) {
            if ($filter == self::WORKSPACES_FILTER_ALL) {
                $filter = self::WORKSPACES_FILTER_ACCESSIBLE;
            }
        }

        $offset     = $request->getParam('offset', 0);
        $limit      = $request->getParam('limit', WorkspaceService::WORKSPACES_LIMIT_DEFAULT);
        $nextOffset = $offset + $limit;

        $workspaceService = $this->getWorkspaceService();
        switch ($filter) {
            case self::WORKSPACES_FILTER_ALL:
                $paginator = $workspaceService->getWorkspaces($portal, $limit, $offset);
                break;
            default:
                $paginator = $workspaceService->getWorkspacesAvailableForUser($userId, $portal, $limit, $offset);
                break;
        }

        $hasMore = ($paginator->count() > $nextOffset);

        $result = array(
            'has_more' => $hasMore,
            'next_offset' => ($hasMore) ? $nextOffset : null,
            'entries' => $paginator->getIterator()->getArrayCopy()
        );

        return $response->withJson($result);
    }

    public function getWorkspacesAction(Request $request, Response $response, $args)
    {
        $portalId   = $args['portal_id'];
        $userId     = $args['user_id'];
        $adminUserId     = $this->getAuth()->getIdentity();
        $acl        = $this->getAcl();
        $role       = new UserRole($adminUserId);

        $portal = $this->getPortalService()->getPortal($portalId);
        if (!$portal) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        if (!$acl->isAllowed($role, $portal, PortalPrivilege::PRIVILEGE_MANAGE_WORKSPACES)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }

        $offset     = $request->getParam('offset', 0);
        $limit      = $request->getParam('limit', WorkspaceService::WORKSPACES_LIMIT_DEFAULT);
        $nextOffset = $offset + $limit;

        $workspaceService = $this->getWorkspaceService();
        $paginator = $workspaceService->getWorkspacesAvailableForUser($userId, $portal, $limit, $offset);


        $hasMore = ($paginator->count() > $nextOffset);

        $result = array(
            'has_more' => $hasMore,
            'next_offset' => ($hasMore) ? $nextOffset : null,
            'entries' => $paginator->getIterator()->getArrayCopy()
        );

        return $response->withJson($result);
    }

    public function getWorkspaceAction(Request $request, Response $response, $args)
    {
        $workspaceId = $args['workspace_id'];

        $acl = $this->getAcl();
        $auth = $this->getAuth();


        $workspace = $this->getWorkspaceService()->getWorkspace($workspaceId);

        if (!$workspace) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $role = new UserRole($auth->getIdentity());
        $privilege = WorkspacePrivilege::PRIVILEGE_READ;
        if (!$acl->isAllowed($role, $workspace, $privilege)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }

        return $response->withJson($workspace);
    }

    public function addWorkspaceAction(Request $request, Response $response, $args)
    {
        $portalId = $args['portal_id'];

        $acl = $this->getAcl();
        $auth = $this->getAuth();

        $portal = $this->getPortalService()->getPortal($portalId);

        if (!$portal) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $data = $request->getParsedBody();

        $role = new UserRole($auth->getIdentity());
        $privilege = PortalPrivilege::PRIVILEGE_CREATE_WORKSPACES;
        if (!$acl->isAllowed($role, $portal, $privilege)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }

        if (!$this->getSubscriptionService()->checkCanAddWorkspace($portal)) {
            $error = new Error(Error::LICENSE_UPDATE_REQUIRED);
            return $response->withJson($error, self::STATUS_FORBIDDEN);
        }
        if ($this->getWorkspaceService()->exists($portal, $data)) {
            return $response->withStatus(self::STATUS_CONFLICT);
        }
        $workspace = $this->getWorkspaceService()->createWorkspace($portal, $data, $auth->getIdentity());

        return $response->withJson($workspace, self::STATUS_CREATED);
    }

    public function deleteWorkspaceAction(Request $request, Response $response, $args)
    {
        $workspaceId = $args['workspace_id'];

        $acl = $this->getAcl();
        $auth = $this->getAuth();

        if (!$workspaceId) {
            return $response->withStatus(self::STATUS_BAD_REQUEST);
        }

        $workspace = $this->getWorkspaceService()->getWorkspace($workspaceId);

        if (!$workspace) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $role = new UserRole($auth->getIdentity());
        $privilege = WorkspacePrivilege::PRIVILEGE_DELETE;
        if (!$acl->isAllowed($role, $workspace, $privilege)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }

        $this->getWorkspaceService()->deleteWorkspace($workspace, $auth->getIdentity());

        return $response->withStatus(self::STATUS_NO_CONTENT);
    }

    public function updateWorkspaceAction(Request $request, Response $response, $args)
    {
        $workspaceId = $args['workspace_id'];

        $acl = $this->getAcl();
        $auth = $this->getAuth();

        if (!$workspaceId) {
            return $response->withStatus(self::STATUS_BAD_REQUEST);
        }

        $workspace = $this->getWorkspaceService()->getWorkspace($workspaceId);

        if (!$workspace) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $data = $request->getParsedBody();

        $role = new UserRole($auth->getIdentity());
        $privilege = WorkspacePrivilege::PRIVILEGE_MODIFY;
        if (!$acl->isAllowed($role, $workspace, $privilege)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }
        if (!empty($data['name']) && $data['name'] != $workspace->getName()) {
            if ($this->getWorkspaceService()->exists($workspace->getPortal(), $data)) {
                return $response->withStatus(self::STATUS_CONFLICT);
            }
        }
        $workspace = $this->getWorkspaceService()->updateWorkspace($workspace, $data, $auth->getIdentity());

        return $response->withJson($workspace);
    }

    public function copyWorkspaceAction(Request $request, Response $response, $args)
    {
        ini_set('max_execution_time', 0);
        $workspaceId = $args['workspace_id'];

        $acl = $this->getAcl();
        $auth = $this->getAuth();

        if (!$workspaceId) {
            return $response->withStatus(self::STATUS_BAD_REQUEST);
        }

        $workspace = $this->getWorkspaceService()->getWorkspace($workspaceId);

        if (!$workspace) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $portal = $this->getPortalService()->getPortal($workspace->getPortal());

        if (!$portal) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $data = $request->getParsedBody();

        $role = new UserRole($auth->getIdentity());
        $privilege = PortalPrivilege::PRIVILEGE_CREATE_WORKSPACES;
        if (!$acl->isAllowed($role, $portal, $privilege)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }

        if (!$this->getSubscriptionService()->checkCanAddWorkspace($portal)) {
            $error = new Error(Error::LICENSE_UPDATE_REQUIRED);
            return $response->withJson($error, self::STATUS_FORBIDDEN);
        }
        if ($this->getWorkspaceService()->exists($portal, $data)) {
            return $response->withStatus(self::STATUS_CONFLICT);
        }
        if ($data["folders"]) {
            $count = $this->getWorkspaceService()->workspaceFileFolderCount($workspace, $data["files"]);
            if ($count > self::WORKSPACES_COPY_FILE_FOLDER_LIMIT) {
                $error = new Error(
                    Error::WORKSPACE_SIZE_LIMIT_EXCEEDED . ' ' .
                    $count . '>' . self::WORKSPACES_COPY_FILE_FOLDER_LIMIT
                );
                return $response->withJson($error, self::STATUS_FORBIDDEN);
            }
        }
        $workspace = $this->getWorkspaceService()->copyWorkspace($workspace, $data, $auth->getIdentity());

        return $response->withJson($workspace, self::STATUS_CREATED);
    }

    /**
     * @return WorkspaceService
     */
    private function getWorkspaceService()
    {
        return $this->getContainer()->get('WorkspaceService');
    }

    /**
     * @return PortalService
     */
    private function getPortalService()
    {
        return $this->getContainer()->get('PortalService');
    }

    /**
     * @return SubscriptionService
     */
    private function getSubscriptionService()
    {
        return $this->getContainer()->get('SubscriptionService');
    }
}
