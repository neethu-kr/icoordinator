<?php

namespace iCoordinator\Controller;

use iCoordinator\Entity\File;
use iCoordinator\Entity\Portal;
use iCoordinator\Entity\Workspace;
use iCoordinator\Permissions\Privilege\HavingDynamicPermissionsResourcePrivilege;
use iCoordinator\Permissions\Privilege\PermissionPrivilege;
use iCoordinator\Permissions\Resource\HavingDynamicPermissionsResourceInterface;
use iCoordinator\Permissions\Role\GroupRole;
use iCoordinator\Permissions\Role\UserRole;
use iCoordinator\Service\GroupService;
use iCoordinator\Service\PermissionService;
use iCoordinator\Service\PortalService;
use iCoordinator\Service\UserService;
use Slim\Http\Request;
use Slim\Http\Response;

class PermissionsController extends AbstractRestController
{
    /**
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function getPermissionAction(Request $request, Response $response, $args)
    {
        $permissionId = $args['permission_id'];

        $acl = $this->getAcl();
        $auth = $this->getAuth();

        $permission = $this->getPermissionService()->getPermission($permissionId);

        if (!$permission) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $role = new UserRole($auth->getIdentity());
        $privilege = PermissionPrivilege::PRIVILEGE_READ;
        if (!$acl->isAllowed($role, $permission, $privilege)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }

        return $response->withJson($permission);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     * @throws \iCoordinator\Service\Exception\NotFoundException
     */
    public function updatePermissionAction(Request $request, Response $response, $args)
    {
        $permissionId = $args['permission_id'];

        $acl = $this->getAcl();
        $auth = $this->getAuth();

        if (!$permissionId) {
            return $response->withStatus(self::STATUS_BAD_REQUEST);
        }

        $permission = $this->getPermissionService()->getPermission($permissionId);
        if (!$permission) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $data = $request->getParsedBody();

        $role = new UserRole($auth->getIdentity());
        $privilege = PermissionPrivilege::PRIVILEGE_MODIFY;
        if (!$acl->isAllowed($role, $permission, $privilege)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }
        /*if ($permission->getAclRole()->getEntityType() == 'group' &&
        ($data['actions'] == 'none' || $data['actions'][0] == 'none')) {
            $this->getPermissionService()->deletePermission($permission, $auth->getIdentity());
            return $response->withStatus(self::STATUS_NO_CONTENT);
        } else {*/
        $permission = $this->getPermissionService()->updatePermission(
            $permission,
            $data['actions'],
            $auth->getIdentity()
        );
        //}
        return $response->withJson($permission);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     * @throws \iCoordinator\Service\Exception\NotFoundException
     */
    public function deletePermissionAction(Request $request, Response $response, $args)
    {
        $permissionId = $args['permission_id'];

        $acl = $this->getAcl();
        $auth = $this->getAuth();

        if (!$permissionId) {
            return $response->withStatus(self::STATUS_BAD_REQUEST);
        }

        $permission = $this->getPermissionService()->getPermission($permissionId);
        if (!$permission) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $role = new UserRole($auth->getIdentity());
        $privilege = PermissionPrivilege::PRIVILEGE_DELETE;
        if (!$acl->isAllowed($role, $permission, $privilege)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }

        $this->getPermissionService()->deletePermission($permission, $auth->getIdentity());

        return $response->withStatus(self::STATUS_NO_CONTENT);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function getPortalPermissionsAction(Request $request, Response $response, $args)
    {
        $portalId = $args['portal_id'];

        $portalService = $this->getContainer()->get('PortalService');
        $portal = $portalService->getPortal($portalId);

        if (!$portal) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        return $this->getPermissions($response, $portal);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function addPortalPermissionAction(Request $request, Response $response, $args)
    {
        $portalId   = $args['portal_id'];

        $portal = $this->getPortalService()->getPortal($portalId);

        return $this->addPermission($request, $response, $portal, $portal);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function getWorkspacePermissionsAction(Request $request, Response $response, $args)
    {
        $workspaceId = $args['workspace_id'];

        $workspaceService = $this->getContainer()->get('WorkspaceService');
        $workspace = $workspaceService->getWorkspace($workspaceId);

        if (!$workspace) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        return $this->getPermissions($response, $workspace);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function addWorkspacePermissionAction(Request $request, Response $response, $args)
    {
        $workspaceId = $args['workspace_id'];

        $workspaceService = $this->getContainer()->get('WorkspaceService');
        $workspace = $workspaceService->getWorkspace($workspaceId);

        if (!$workspace) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        return $this->addPermission($request, $response, $workspace, $workspace->getPortal());
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function getFilePermissionsAction(Request $request, Response $response, $args)
    {
        $fileId = $args['file_id'];

        $fileService = $this->getContainer()->get('FileService');
        $file = $fileService->getFile($fileId);

        if (!$file) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        //for files and folders resource type is the same
        return $this->getPermissions($response, $file);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function getFolderPermissionsAction(Request $request, Response $response, $args)
    {
        $folderId = $args['folder_id'];

        $folderService = $this->getContainer()->get('FolderService');
        $folder = $folderService->getFolder($folderId);

        if (!$folder) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        //for files and folders resource type is the same
        return $this->getPermissions($response, $folder);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function getSmartFolderPermissionsAction(Request $request, Response $response, $args)
    {
        $smartFolder = $request->getAttribute('smartFolder');

        //for files and folders resource type is the same
        return $this->getPermissions($response, $smartFolder);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function addFilePermissionAction(Request $request, Response $response, $args)
    {
        $fileId = $args['file_id'];

        $fileService = $this->getContainer()->get('FileService');
        $file = $fileService->getFile($fileId);

        if (!$file) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        //for files and folders resource type is the same
        return $this->addPermission($request, $response, $file, $file->getWorkspace()->getPortal());
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function addFolderPermissionAction(Request $request, Response $response, $args)
    {
        $folderId = $args['folder_id'];

        $folderService = $this->getContainer()->get('FolderService');
        $folder = $folderService->getFolder($folderId);

        if (!$folder) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        //for files and folders resource type is the same
        return $this->addPermission($request, $response, $folder, $folder->getWorkspace()->getPortal());
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function addSmartFolderPermissionAction(Request $request, Response $response)
    {
        $smartFolder = $request->getAttribute('smartFolder');

        //for files and folders resource type is the same
        return $this->addPermission($request, $response, $smartFolder, $smartFolder->getWorkspace()->getPortal());
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param HavingDynamicPermissionsResourceInterface $resource
     * @param Portal $portal
     * @return Response
     * @throws \iCoordinator\Service\Exception\ValidationFailedException
     */
    private function addPermission(
        Request $request,
        Response $response,
        HavingDynamicPermissionsResourceInterface $resource,
        Portal $portal
    ) {
        $data       = $request->getParsedBody();
        $userId     = $this->getAuth()->getIdentity();
        $acl        = $this->getAcl();

        if (!isset($data['actions']) || !isset($data['grant_to']) || !is_array($data['grant_to'])
            || !isset($data['grant_to']['entity_id']) || !isset($data['grant_to']['entity_type'])
        ) {
            return $response->withStatus(self::STATUS_BAD_REQUEST);
        }

        $role = new UserRole($userId);
        foreach ($data['actions'] as $action) {
            $privilege = $resource::getPrivilegeForGrantingPermission($action);
            if (!$acl->isAllowed($role, $resource, $privilege)) {
                return $response->withStatus(self::STATUS_FORBIDDEN);
            }
        }

        $entityType  = $data['grant_to']['entity_type'];
        $entityId    = $data['grant_to']['entity_id'];
        $grantTo     = null;
        $role        = null;

        switch ($entityType) {
            case 'user':
                $grantTo    = $this->getUserService()->getUser($entityId);
                $role       = new UserRole($grantTo);
                break;
            case 'group':
                $grantTo    = $this->getGroupService()->getGroup($entityId);
                $role       = new GroupRole($grantTo);
                break;
        }

        if (!$grantTo) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $privilege = HavingDynamicPermissionsResourcePrivilege::PRIVILEGE_HAVE_PERMISSIONS;
        if (!$this->getAcl()->isAllowed($role, $resource, $privilege)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }

        $permission = $this->getPermissionService()->addPermission(
            $resource,
            $grantTo,
            $data['actions'],
            $userId,
            $portal
        );

        return $response->withJson($permission, self::STATUS_CREATED);
    }

    /**
     * @param Response $response
     * @param HavingDynamicPermissionsResourceInterface $resource
     * @return Response
     */
    private function getPermissions(
        Response $response,
        HavingDynamicPermissionsResourceInterface $resource
    ) {

        $auth = $this->getAuth();
        $acl = $this->getAcl();

        $user = $this->getUserService()->getUser($auth->getIdentity());
        $role = new UserRole($auth->getIdentity());

        if ($resource instanceof Portal) {
            $userPermissions = $this->getPermissionService()->getPermissions($resource, $user, $resource);
            if (!count($userPermissions)) {
                $privilege = $resource::getPrivilegeForReadingPermissions();
                if (!$acl->isAllowed($role, $resource, $privilege)) {
                    return $response->withStatus(self::STATUS_FORBIDDEN);
                }
            }
        } elseif ($resource instanceof Workspace) {
            $userPermissions = $this->getPermissionService()->getPermissions($resource, $user, $resource->getPortal());
            if (!count($userPermissions)) {
                $privilege = $resource::getPrivilegeForReadingPermissions();
                if (!$acl->isAllowed($role, $resource, $privilege)) {
                    return $response->withStatus(self::STATUS_FORBIDDEN);
                }
            }
        } else {
            $privilege = $resource::getPrivilegeForReadingPermissions();

            if (!$acl->isAllowed($role, $resource, $privilege)) {
                return $response->withStatus(self::STATUS_FORBIDDEN);
            }
        }
        if ($resource instanceof File) {
            $permissions = $this->getPermissionService()->getFirstFoundPermissions($resource);
        } else {
            $permissions = $this->getPermissionService()->getPermissions($resource);
        }

        return $response->withJson($permissions);
    }

    /**
     * @return PermissionService
     */
    private function getPermissionService()
    {
        return $this->getContainer()->get('PermissionService');
    }

    /**
     * @return PortalService
     */
    private function getPortalService()
    {
        return $this->getContainer()->get('PortalService');
    }

    /**
     * @return UserService
     */
    private function getUserService()
    {
        return $this->getContainer()->get('UserService');
    }

    /**
     * @return GroupService
     */
    private function getGroupService()
    {
        return $this->getContainer()->get('GroupService');
    }
}
