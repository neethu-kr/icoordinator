<?php

namespace iCoordinator\Controller;

use iCoordinator\Entity\Error;
use iCoordinator\Entity\Folder;
use iCoordinator\Permissions\Privilege\FilePrivilege;
use iCoordinator\Permissions\Privilege\WorkspacePrivilege;
use iCoordinator\Permissions\Role\UserRole;
use iCoordinator\Service\Exception\ValidationFailedException;
use iCoordinator\Service\InboundEmailService;
use Slim\Http\Request;
use Slim\Http\Response;
use Laminas\Json\Json;

class InboundEmailsController extends AbstractRestController
{
    public function getWorkspaceInboundEmailAction(Request $request, Response $response, $args)
    {
        $workspaceId = $args['workspace_id'];

        $acl = $this->getAcl();
        $auth = $this->getAuth();

        $workspaceService = $this->getContainer()->get('WorkspaceService');
        $workspace = $workspaceService->getWorkspace($workspaceId);

        if (!$workspace) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $role = new UserRole($auth->getIdentity());
        $privilege = WorkspacePrivilege::PRIVILEGE_CREATE_FILES;
        if (!$acl->isAllowed($role, $workspace, $privilege)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }

        $inboundEmailService = $this->getContainer()->get('InboundEmailService');
        $inboundEmail = $inboundEmailService->getInboundEmail($auth->getIdentity(), $workspace);

        return $response->withJson($inboundEmail);
    }

    public function getFolderInboundEmailAction(Request $request, Response $response, $args)
    {
        $folderId = $args['folder_id'];

        $acl = $this->getAcl();
        $auth = $this->getAuth();

        $folderService = $this->getContainer()->get('FolderService');
        $folder = $folderService->getFolder($folderId);

        if (!$folder) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $role = new UserRole($auth->getIdentity());
        $privilege = FilePrivilege::PRIVILEGE_CREATE_FILES;
        if (!$acl->isAllowed($role, $folder, $privilege)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }

        $inboundEmailService = $this->getContainer()->get('InboundEmailService');
        $inboundEmail = $inboundEmailService->getInboundEmail($auth->getIdentity(), $folder);

        return $response->withJson($inboundEmail);
    }

    public function processInboundEmailAction(Request $request, Response $response, $args)
    {
        $acl = $this->getAcl();
        $auth = $this->getAuth();

        $mandrillEvents = $request->getParam('mandrill_events');

        if (!$mandrillEvents) {
            return $response->withStatus(self::STATUS_BAD_REQUEST);
        }

        $data = Json::decode($mandrillEvents, Json::TYPE_ARRAY);

        if (count($data)) {
            $data = $data[0];
        } else {
            return $response->withStatus(self::STATUS_BAD_REQUEST);
        }

        if (empty($data['event']) || $data['event'] != 'inbound') {
            return $response->withStatus(self::STATUS_BAD_REQUEST);
        }

        try {
            $inboundEmailService = $this->getContainer()->get('InboundEmailService');
            $inboundEmailService->setData($data);

            $role = new UserRole($inboundEmailService->getUser());
            $resource = $inboundEmailService->getResource();
            if ($resource instanceof Folder) {
                if ($resource->getIsTrashed()) {
                    return $response->withStatus(self::STATUS_NOT_FOUND);
                }
            }
            switch ($inboundEmailService->getAction()) {
                case InboundEmailService::ACTION_ADD_FILE_TO_WORKSPACE:
                    $privilege = WorkspacePrivilege::PRIVILEGE_CREATE_FILES;
                    break;
                case InboundEmailService::ACTION_ADD_FILE_TO_FOLDER:
                    $privilege = FilePrivilege::PRIVILEGE_CREATE_FILES;
                    break;
                default:
                    return $response->withStatus(self::STATUS_BAD_REQUEST);
                    break;
            }

            if (!$acl->isAllowed($role, $resource, $privilege)) {
                return $response->withStatus(self::STATUS_FORBIDDEN);
            }

            $result = $inboundEmailService->process();
        } catch (ValidationFailedException $e) {
            $error = new Error(Error::VALIDATION_FAILED);
            return $response->withJson($error);
            //return 200 OK always, otherwise Mandrill will continue attempts
        }

        return $response->withJson($result);
    }
}
