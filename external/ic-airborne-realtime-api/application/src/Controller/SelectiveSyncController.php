<?php

namespace iCoordinator\Controller;

use iCoordinator\Permissions\Privilege\FilePrivilege;
use iCoordinator\Permissions\Privilege\WorkspacePrivilege;
use iCoordinator\Permissions\Role\UserRole;
use iCoordinator\Service\FileService;
use iCoordinator\Service\SelectiveSyncService;
use Slim\Http\Request;
use Slim\Http\Response;

class SelectiveSyncController extends AbstractRestController
{
    /**
     * API call: GET /files/{file_id}/selective-sync
     * API call: GET /folders/{folder_id}/selective-sync
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function getSelectiveSyncAction(Request $request, Response $response, $args)
    {
        $fileId = isset($args['file_id']) ? $args['file_id'] : $args['folder_id'];

        $file = $this->getFileService()->getFile($fileId);
        if (!$file) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }
        $userId     = $this->getAuth()->getIdentity();
        $role       = new UserRole($userId);
        $privilege  = FilePrivilege::PRIVILEGE_READ;

        if (!$this->getAcl()->isAllowed($role, $file, $privilege)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }
        $selectiveSync = $this->getSelectiveSyncService()->getSelectiveSync($file, $userId);
        return $response->withJson($selectiveSync);
    }

    /**
     * API call: PUT /files/{file_id}/selective-sync
     * API call: PUT /folders/{folder_id}/selective-sync
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function setSelectiveSyncAction(Request $request, Response $response, $args)
    {
        $fileId = isset($args['file_id']) ? $args['file_id'] : $args['folder_id'];
        $data   = $request->getParsedBody();
        $userId = isset($data['user_id']) ? $data['user_id'] : '';
        $file = $this->getFileService()->getFile($fileId);
        if (!$file) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }
        if ($userId) {
            $adminId = $this->getAuth()->getIdentity();
            $role = new UserRole($adminId);
            $privilege = WorkspacePrivilege::PRIVILEGE_GRAND_ADMIN_PERMISSION;
            if (!$this->getAcl()->isAllowed($role, $file->getWorkspace(), $privilege)) {
                return $response->withStatus(self::STATUS_FORBIDDEN);
            }
        } else {
            $userId = $this->getAuth()->getIdentity();
        }
        $role = new UserRole($userId);
        $privilege = FilePrivilege::PRIVILEGE_READ;

        if (!$this->getAcl()->isAllowed($role, $file, $privilege)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }
        $selectiveSync = $this->getSelectiveSyncService()->setSelectiveSync($file, $userId);
        return $response->withJson($selectiveSync);
    }

    /**
     * API call: DELETE /files/{file_id}/selective-sync
     * API call: DELETE /folders/{folder_id}/selective-sync
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function deleteSelectiveSyncAction(Request $request, Response $response, $args)
    {
        $fileId = isset($args['file_id']) ? $args['file_id'] : $args['folder_id'];
        $data   = $request->getParsedBody();
        $userId = isset($data['user_id']) ? $data['user_id'] : '';
        $file = $this->getFileService()->getFile($fileId);
        if (!$file) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }
        if ($userId) {
            $adminId = $this->getAuth()->getIdentity();
            $role = new UserRole($adminId);
            $privilege = WorkspacePrivilege::PRIVILEGE_GRAND_ADMIN_PERMISSION;
            if (!$this->getAcl()->isAllowed($role, $file->getWorkspace(), $privilege)) {
                return $response->withStatus(self::STATUS_FORBIDDEN);
            }
        } else {
            $userId = $this->getAuth()->getIdentity();
            $role = new UserRole($userId);
            $privilege = FilePrivilege::PRIVILEGE_READ;

            if (!$this->getAcl()->isAllowed($role, $file, $privilege)) {
                return $response->withStatus(self::STATUS_FORBIDDEN);
            }
        }
        $this->getSelectiveSyncService()->deleteSelectiveSync($fileId, $userId);
        return $response->withStatus(self::STATUS_NO_CONTENT);
    }

    /**
     * @return FileService
     */
    private function getFileService()
    {
        return $this->getContainer()->get('FileService');
    }

    /**
     * @return SelectiveSyncService
     */
    private function getSelectiveSyncService()
    {
        return $this->getContainer()->get('SelectiveSyncService');
    }
}
