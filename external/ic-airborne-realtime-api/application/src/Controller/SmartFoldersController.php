<?php

namespace iCoordinator\Controller;

use iCoordinator\Controller\Helper\FilesControllerHelper;
use iCoordinator\Permissions\Privilege\FilePrivilege;
use iCoordinator\Permissions\Privilege\MetaFieldCriterionPrivilege;
use iCoordinator\Permissions\Privilege\WorkspacePrivilege;
use iCoordinator\Permissions\Role\UserRole;
use iCoordinator\Service\Exception\NotTrashedException;
use iCoordinator\Service\FolderService;
use Slim\Http\Request;
use Slim\Http\Response;

final class SmartFoldersController extends AbstractRestController
{
    public function init()
    {
        $this->addHelper(new FilesControllerHelper());
    }

    public function getSmartFolderAction(Request $request, Response $response, $args)
    {
        $smartFolder = $request->getAttribute('smartFolder');
        return $response->withJson($smartFolder);
    }

    public function getSmartFolderChildrenAction(Request $request, Response $response, $args)
    {
        $acl = $this->getAcl();
        $auth = $this->getAuth();

        $smartFolder = $request->getAttribute('smartFolder');

        $offset = $request->getParam('offset', 0);
        $limit = $request->getParam('limit', FolderService::FOLDER_CHILDREN_LIMIT_DEFAULT);

        $smartFolderService = $this->getSmartFolderService();
        $role = new UserRole($auth->getIdentity());

        $hasMore = true;
        $children = array();

        while ($hasMore && count($children) < $limit) {
            $paginator = $smartFolderService->getSmartFolderChildren($smartFolder, $limit, $offset);
            if ($paginator->count() <= $offset + $limit) {
                $hasMore = false;
            }
            foreach ($paginator->getIterator() as $child) {
                if ($acl->isAllowed($role, $child, FilePrivilege::PRIVILEGE_READ)) {
                    array_push($children, $child);
                }
                $offset++;
                if (count($children) >= $limit) {
                    break;
                }
            }
        }

        $result = array(
            'has_more' => $hasMore,
            'next_offset' => ($hasMore) ? $offset : null,
            'entries' => $children
        );

        return $response->withJson($result);
    }

    public function addSmartFolderAction(Request $request, Response $response, $args)
    {
        $workspaceId = $args['workspace_id'];

        $acl = $this->getAcl();
        $auth = $this->getAuth();

        $data = $request->getParsedBody();

        $workspaceService = $this->getContainer()->get('WorkspaceService');
        $workspace = $workspaceService->getWorkspace($workspaceId);

        if (!$workspace) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        //check if can create files in workspace
        $role = new UserRole($auth->getIdentity());
        $privilege = WorkspacePrivilege::PRIVILEGE_CREATE_SMART_FOLDERS;
        if (!$acl->isAllowed($role, $workspace, $privilege)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }

        //check if can create in parent folder
        if (!empty($data['parent'])) {
            $privilege = FilePrivilege::PRIVILEGE_CREATE_SMART_FOLDERS;
            $helperResponse = $this->getHelper(FilesControllerHelper::HELPER_ID)
                ->checkParentFolderAccess($data['parent'], $role, $privilege, $response);
            if ($helperResponse instanceof Response) {
                return $helperResponse;
            }
        }

        $smartFolderService = $this->getContainer()->get('SmartFolderService');
        $smartFolder = $smartFolderService->createSmartFolder($data, $workspaceId, $auth->getIdentity());

        return $response->withJson($smartFolder, self::STATUS_CREATED);
    }

    public function trashSmartFolderAction(Request $request, Response $response, $args)
    {
        $smartFolder = $request->getAttribute('smartFolder');
        $userId = $this->getAuth()->getIdentity();

        $this->getSmartFolderService()->deleteSmartFolder($smartFolder, $userId, false);

        return $response->withStatus(self::STATUS_NO_CONTENT);
    }

    public function deleteSmartFolderAction(Request $request, Response $response, $args)
    {
        $smartFolder = $request->getAttribute('smartFolder');
        $userId = $this->getAuth()->getIdentity();

        $this->getSmartFolderService()->deleteSmartFolder($smartFolder, $userId, true);

        return $response->withStatus(self::STATUS_NO_CONTENT);
    }

    public function updateSmartFolderAction(Request $request, Response $response, $args)
    {
        $smartFolder = $request->getAttribute('smartFolder');
        $userId = $this->getAuth()->getIdentity();
        $data = $request->getParsedBody();

        $smartFolder = $this->getSmartFolderService()->updateSmartFolder($smartFolder, $data, $userId);

        return $response->withJson($smartFolder);
    }


    public function restoreSmartFolderAction(Request $request, Response $response, $args)
    {
        $smartFolder = $request->getAttribute('smartFolder');
        $userId = $this->getAuth()->getIdentity();
        $data = $request->getParsedBody();

        try {
            $smartFolder = $this->getSmartFolderService()->restoreSmartFolder($smartFolder, $userId, $data);
        } catch (NotTrashedException $e) {
            return $response->withStatus(self::STATUS_METHOD_NOT_ALLOWED);
        }

        return $response->withJson($smartFolder, self::STATUS_CREATED);
    }


    public function getSmartFolderCriteriaAction(Request $request, Response $response, $args)
    {
        $smartFolder = $request->getAttribute('smartFolder');

        $metaFieldCriteria = $this->getSmartFolderService()->getSmartFolderCriteria($smartFolder);

        return $response->withJson($metaFieldCriteria->toArray());
    }

    public function addSmartFolderCriterionAction(Request $request, Response $response, $args)
    {
        $smartFolder = $request->getAttribute('smartFolder');
        $userId = $this->getAuth()->getIdentity();
        $data = $request->getParsedBody();

        $metaFieldCriterion = $this->getSmartFolderService()->addSmartFolderCriterion($smartFolder, $data, $userId);
        return $response->withJson($metaFieldCriterion, self::STATUS_CREATED);
    }

    public function updateSmartFolderCriterionAction(Request $request, Response $response, $args)
    {
        $metaFieldCriterionId = $args['meta_field_criterion_id'];

        $acl = $this->getAcl();
        $auth = $this->getAuth();

        if (!$metaFieldCriterionId) {
            return $response->withStatus(self::STATUS_BAD_REQUEST);
        }

        $smartFolderService = $this->getContainer()->get('SmartFolderService');
        $metaFieldCriterion = $smartFolderService->getSmartFolderCriterion($metaFieldCriterionId);

        if (!$metaFieldCriterion) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $data = $request->getParsedBody();

        $role = new UserRole($auth->getIdentity());
        $privilege = MetaFieldCriterionPrivilege::PRIVILEGE_MODIFY;
        if (!$acl->isAllowed($role, $metaFieldCriterion, $privilege)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }
        $metaFieldCriterion = $smartFolderService->updateSmartFolderCriterion(
            $metaFieldCriterion,
            $data,
            $auth->getIdentity()
        );

        return $response->withJson($metaFieldCriterion);
    }

    public function deleteSmartFolderCriterionAction(Request $request, Response $response, $args)
    {
        $metaFieldCriterionId = $args['meta_field_criterion_id'];

        $acl = $this->getAcl();
        $auth = $this->getAuth();

        if (!$metaFieldCriterionId) {
            return $response->withStatus(self::STATUS_BAD_REQUEST);
        }

        $smartFolderService = $this->getContainer()->get('SmartFolderService');
        $metaFieldCriterion = $smartFolderService->getSmartFolderCriterion($metaFieldCriterionId);

        if (!$metaFieldCriterion) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $role = new UserRole($auth->getIdentity());
        $privilege = MetaFieldCriterionPrivilege::PRIVILEGE_DELETE;
        if (!$acl->isAllowed($role, $metaFieldCriterion, $privilege)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }

        $smartFolderService->deleteSmartFolderCriterion($metaFieldCriterion, $auth->getIdentity());

        return $response->withStatus(self::STATUS_NO_CONTENT);
    }

    /**
     * Smart Folder Route Middleware
     *
     * @param Request $request
     * @param Response $response
     * @param callable $next
     * @return Response
     * @throws \Exception
     */
    public function preDispatchSmartFolderMiddleware(Request $request, Response $response, callable $next)
    {
        $smartFolderId = $request->getAttribute('route')->getArgument('smart_folder_id');
        $privilege = $request->getAttribute('route')->getArgument('privilege');
        $data = $request->getParsedBody();


        if (!$smartFolderId) {
            return $response->withStatus(AbstractRestController::STATUS_BAD_REQUEST);
        }

        $smartFolder = $this->getSmartFolderService()->getSmartFolder($smartFolderId);

        if (!$smartFolder) {
            return $response->withStatus(AbstractRestController::STATUS_NOT_FOUND);
        }

        $role = new UserRole($this->getAuth()->getIdentity());

        if ($privilege) {
            if (!$this->getAcl()->isAllowed($role, $smartFolder, $privilege)) {
                return $response->withStatus(AbstractRestController::STATUS_FORBIDDEN);
            }
        }

        //check privilege of destination parent folder
        if (!empty($data['parent'])) {
            $privilege = FilePrivilege::PRIVILEGE_CREATE_SMART_FOLDERS;
            $helperResponde = $this->getHelper(FilesControllerHelper::HELPER_ID)
                ->checkParentFolderAccess($data['parent'], $role, $privilege, $response);
            if ($helperResponde instanceof Response) {
                return $helperResponde;
            }
        }

        return $next($request->withAttribute('smartFolder', $smartFolder), $response);
    }

    /**
     * @return \iCoordinator\Service\SmartFolderService
     */
    private function getSmartFolderService()
    {
        return $this->getContainer()->get('SmartFolderService');
    }
}
