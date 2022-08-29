<?php

namespace iCoordinator\Controller;

use iCoordinator\Permissions\Privilege\PortalPrivilege;
use iCoordinator\Permissions\Privilege\SystemPrivilege;
use iCoordinator\Permissions\Resource\SystemResource;
use iCoordinator\Permissions\Role\UserRole;
use Slim\Http\Request;
use Slim\Http\Response;

class PortalsController extends AbstractRestController
{
    const CHUNK_SIZE = 1048576;
    public function getPortalInfoAction(Request $request, Response $response, $args)
    {
        $acl = $this->getAcl();
        $auth = $this->getAuth();

        $role = new UserRole($auth->getIdentity());
        $privilege = PortalPrivilege::PRIVILEGE_CREATE_WORKSPACES;
        $portalService = $this->getContainer()->get('PortalService');
        $workspaceService = $this->getContainer()->get('WorkspaceService');
        $portalId = $args['portal_id'];
        if (!$portalId) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }
        $portal = $portalService->getPortal($portalId);
        $userIds = $portalService->getPortalUserIds($portalId);
        $personalWorkspaces = $workspaceService->getWorkspacesAvailableForUser($auth->getIdentity(), $portalId);

        if (!$acl->isAllowed($role, $portal, $privilege)) {
            // return info for normal user
            $portalInfo = array(
                "userCount" => count($userIds),
                "workspaceCount" => null,
                "usedStorage" => null,
                "personalWorkspaceCount" => count($personalWorkspaces)
            );
        } else {
            // return extended info for admin and portal owner
            $workspaces = $workspaceService->getWorkspaces($portalId);
            $usedStorage = $portalService->getUsedStorage($portalId);
            $portalInfo = array(
                "userCount" => count($userIds),
                "workspaceCount" => count($workspaces),
                "usedStorage" => $usedStorage,
                "personalWorkspaceCount" => count($personalWorkspaces)
                );
        }
        return $response->withJson(json_encode($portalInfo));
    }
    public function getPortalsListAction(Request $request, Response $response, $args)
    {
        $auth = $this->getAuth();
        $state = $request->getParam('state', false);
        $slimState = $request->getParam('slim_state', false);
        $portalService = $this->getContainer()->get('PortalService');
        $portals = $portalService->getPortalsAvailableForUser($auth->getIdentity(), $state, $slimState);

        return $response->withJson($portals);
    }

    public function createPortalAction(Request $request, Response $response, $args)
    {
        $acl = $this->getAcl();
        $auth = $this->getAuth();

        $data = $request->getParsedBody();

        $role = new UserRole($auth->getIdentity());
        $privilege = SystemPrivilege::PRIVILEGE_CREATE_PORTALS;
        if (!$acl->isAllowed($role, SystemResource::RESOURCE_ID, $privilege)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }

        $portalService = $this->getContainer()->get('PortalService');
        $portal = $portalService->createPortal($data, $auth->getIdentity());

        return $response->withJson($portal, self::STATUS_CREATED);
    }

    public function getAllowedClientsAction(Request $request, Response $response, $args)
    {
        $portalId = $args['portal_id'];
        if (!$portalId) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }
        $portalService = $this->getContainer()->get('PortalService');
        $allowedClients = $portalService->getAllowedClients($portalId);

        return $response->withJson($allowedClients);
    }

    public function setAllowedClientsAction(Request $request, Response $response, $args)
    {
        $acl = $this->getAcl();
        $auth = $this->getAuth();

        $data = $request->getParsedBody();

        $role = new UserRole($auth->getIdentity());
        $privilege = PortalPrivilege::PRIVILEGE_GRANT_ADMIN_PERMISSION;
        if (!$acl->isAllowed($role, SystemResource::RESOURCE_ID, $privilege)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }

        $portalId = $args['portal_id'];
        if (!$portalId) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $portalService = $this->getContainer()->get('PortalService');
        $portal = $portalService->setAllowedClients($portalId, $data);

        return $response->withJson($portal, self::STATUS_CREATED);
    }

    public function updateAllowedClientsAction(Request $request, Response $response, $args)
    {
        $acl = $this->getAcl();
        $auth = $this->getAuth();

        $data = $request->getParsedBody();

        $role = new UserRole($auth->getIdentity());
        $privilege = PortalPrivilege::PRIVILEGE_GRANT_ADMIN_PERMISSION;
        if (!$acl->isAllowed($role, SystemResource::RESOURCE_ID, $privilege)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }

        $allowedClientId = $args['allowed_client_id'];
        if (!$allowedClientId) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $portalService = $this->getContainer()->get('PortalService');
        $allowedClient = $portalService->updateAllowedClients($allowedClientId, $data, $auth->getIdentity());

        return $response->withJson($allowedClient, self::STATUS_OK);
    }

    /**
     * @return FileService
     */
    private function getFileService()
    {
        return $this->getContainer()->get('FileService');
    }
}
