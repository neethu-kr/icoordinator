<?php

namespace iCoordinator\Controller;

use iCoordinator\Permissions\Privilege\FilePrivilege;
use iCoordinator\Permissions\Role\UserRole;
use iCoordinator\Service\FileService;
use Slim\Http\Request;
use Slim\Http\Response;

class SearchController extends AbstractRestController
{

    /**
     * API call: GET /search
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function searchAction(Request $request, Response $response, $args)
    {
        $auth = $this->getAuth();
        $acl = $this->getAcl();

        $offset = $request->getParam('offset', 0);
        $limit = $request->getParam('limit', FileService::FILES_LIMIT_DEFAULT);
        $search = $request->getParam('search');
        $portalId = $request->getParam('portal', 0);
        $workspaceId = $request->getParam('workspace', 0);

        $fileService = $this->getContainer()->get('FileService');
        $userService = $this->getContainer()->get('UserService');
        $role = new UserRole($auth->getIdentity());

        $hasMore = true;
        $files = array();

        while ($hasMore && count($files) < $limit) {
            if ($workspaceId > 0) {
                $entries = $fileService->getMatchingWorkspaceFiles($workspaceId, $limit, $offset, $search);
            } else {
                $entries = $fileService->getMatchingFiles($portalId, $limit, $offset, $search);
            }
            if (count($entries) <= $offset + $limit) {
                $hasMore = false;
            }
            foreach ($entries as $entry) {
                $file = $fileService->getFile($entry['id']);
                if (strpos(strtolower($entry['hit']), strtolower($search)) !== false ||
                    strpos($file->getContentModifiedAt(), $search) !== false) {
                    if ($file !== null && $acl->isAllowed($role, $file, FilePrivilege::PRIVILEGE_READ)) {
                        $fileVersion = $fileService->getLatestFileVersion($file->getId());
                        if ($fileVersion) {
                            $file->setVersionComment($fileVersion['comment']);
                            $modifiedBy = $userService->getUser($fileVersion['modified_by']);
                            $file->setModifiedBy($modifiedBy);
                            $file->setVersionCreatedAt($fileVersion['created_at']);
                        }
                        array_push($files, $file);
                    }
                }
                $offset++;
                if (count($files) >= $limit) {
                    break;
                }
            }
        }

        $result = array(
            'has_more' => $hasMore,
            'next_offset' => ($hasMore) ? $offset : null,
            'entries' => $files
        );

        return $response->withJson($result);
    }
}
