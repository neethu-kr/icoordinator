<?php

namespace iCoordinator\Controller;

use iCoordinator\Entity\File;
use iCoordinator\Permissions\Privilege\FilePrivilege;
use iCoordinator\Permissions\Role\UserRole;
use iCoordinator\Service\FileEmailOptionsService;
use iCoordinator\Service\FileService;
use iCoordinator\Service\FolderService;
use Slim\Http\Request;
use Slim\Http\Response;

class FileEmailOptionsController extends AbstractRestController
{
    /**
     * API call: GET /files/{file_id}/email-options
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function getFileEmailOptionsAction(Request $request, Response $response, $args)
    {
        $fileId = $args['file_id'];

        $file = $this->getFileService()->getFile($fileId);
        if (!$file) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        return $this->getFileEmailOptions($response, $file);
    }

    /**
     * API call: GET /folders/{folder_id}/email-options
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function getFolderEmailOptionsAction(Request $request, Response $response, $args)
    {
        $folderId = $args['folder_id'];

        $folder = $this->getFolderService()->getFolder($folderId);
        if (!$folder) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        return $this->getFileEmailOptions($response, $folder);
    }

    /**
     * API call: PUT /files/{file_id}/email-options
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function setFileEmailOptionsAction(Request $request, Response $response, $args)
    {
        $fileId = $args['file_id'];

        $file = $this->getFileService()->getFile($fileId);
        if (!$file) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $data = $request->getParsedBody();

        return $this->setFileEmailOptions($response, $file, $data);
    }

    /**
     * API call: PUT /folders/{folder_id}/email-options
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function setFolderEmailOptionsAction(Request $request, Response $response, $args)
    {
        $folderId = $args['folder_id'];

        $folder = $this->getFolderService()->getFolder($folderId);
        if (!$folder) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $data = $request->getParsedBody();

        return $this->setFileEmailOptions($response, $folder, $data);
    }

    /**
     * @param Response $response
     * @param File $file
     * @return Response
     * @throws \iCoordinator\Service\Exception\NotFoundException
     */
    private function getFileEmailOptions(Response $response, File $file)
    {
        $userId     = $this->getAuth()->getIdentity();
        $role       = new UserRole($userId);
        $privilege  = FilePrivilege::PRIVILEGE_READ;

        if (!$this->getAcl()->isAllowed($role, $file, $privilege)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }

        $fileEmailOptions = $this->getFileEmailOptionsService()->getFileEmailOptions($file, $userId);
        return $response->withJson($fileEmailOptions);
    }

    /**
     * @param Response $response
     * @param File $file
     * @param $data
     * @return Response
     * @throws \Exception
     * @throws \iCoordinator\Service\Exception\NotFoundException
     */
    private function setFileEmailOptions(Response $response, File $file, $data)
    {
        $userId     = $this->getAuth()->getIdentity();
        $role       = new UserRole($userId);
        $privilege  = FilePrivilege::PRIVILEGE_READ;

        if (!$this->getAcl()->isAllowed($role, $file, $privilege)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }

        $fileEmailOptions = $this->getFileEmailOptionsService()->setFileEmailOptions($file, $data, $userId);
        return $response->withJson($fileEmailOptions);
    }

    /**
     * @return FileService
     */
    private function getFileService()
    {
        return $this->getContainer()->get('FileService');
    }

    /**
     * @return FolderService
     */
    private function getFolderService()
    {
        return $this->getContainer()->get('FolderService');
    }

    /**
     * @return FileEmailOptionsService
     */
    private function getFileEmailOptionsService()
    {
        return $this->getContainer()->get('FileEmailOptionsService');
    }
}
