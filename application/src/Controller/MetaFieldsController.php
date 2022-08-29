<?php

namespace iCoordinator\Controller;

use iCoordinator\Entity\File;
use iCoordinator\Permissions\Privilege\FilePrivilege;
use iCoordinator\Permissions\Privilege\MetaFieldPrivilege;
use iCoordinator\Permissions\Privilege\MetaFieldValuePrivilege;
use iCoordinator\Permissions\Privilege\PortalPrivilege;
use iCoordinator\Permissions\Role\UserRole;
use iCoordinator\Service\FileService;
use iCoordinator\Service\FolderService;
use iCoordinator\Service\MetaFieldService;
use Slim\Http\Request;
use Slim\Http\Response;

class MetaFieldsController extends AbstractRestController
{
    public function getMetaFieldsListAction(Request $request, Response $response, $args)
    {
        $portalId = $args['portal_id'];

        $acl = $this->getAcl();
        $auth = $this->getAuth();

        $portalService = $this->getContainer()->get('PortalService');
        $portal = $portalService->getPortal($portalId);

        if (!$portal) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $role = new UserRole($auth->getIdentity());
        $privilege = PortalPrivilege::PRIVILEGE_READ_META_FIELDS;
        if (!$acl->isAllowed($role, $portal, $privilege)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }

        $offset = $request->getParam('offset', 0);
        $limit = $request->getParam('limit', MetaFieldService::META_FIELDS_LIMIT_DEFAULT);

        $metaFieldService = $this->getContainer()->get('MetaFieldService');

        $hasMore = true;
        $nextOffset = $offset + $limit;

        $paginator = $metaFieldService->getMetaFields($portal, $limit, $offset);
        if ($paginator->count() <= $offset + $paginator->getIterator()->count()) {
            $hasMore = false;
        }

        $result = array(
            'has_more' => $hasMore,
            'next_offset' => ($hasMore) ? $nextOffset : null,
            'entries' => $paginator->getIterator()->getArrayCopy()
        );

        return $response->withJson($result);
    }

    public function getMetaFieldAction(Request $request, Response $response, $args)
    {
        $metaFieldId = $args['meta_field_id'];

        $acl = $this->getAcl();
        $auth = $this->getAuth();

        $metaFieldService = $this->getContainer()->get('MetaFieldService');
        $metaField = $metaFieldService->getMetaField($metaFieldId);

        if (!$metaField) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $role = new UserRole($auth->getIdentity());
        $privilege = MetaFieldPrivilege::PRIVILEGE_READ;
        if (!$acl->isAllowed($role, $metaField, $privilege)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }

        return $response->withJson($metaField);
    }

    public function addMetaFieldAction(Request $request, Response $response, $args)
    {
        $portalId = $args['portal_id'];

        $acl = $this->getAcl();
        $auth = $this->getAuth();

        $portalService = $this->getContainer()->get('PortalService');
        $portal = $portalService->getPortal($portalId);

        if (!$portal) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $data = $request->getParsedBody();

        $role = new UserRole($auth->getIdentity());
        $privilege = PortalPrivilege::PRIVILEGE_CREATE_META_FIELDS;
        if (!$acl->isAllowed($role, $portal, $privilege)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }

        $metaFieldService = $this->getContainer()->get('MetaFieldService');
        $metaField = $metaFieldService->createMetaField($portal, $data, $auth->getIdentity());

        return $response->withJson($metaField, self::STATUS_CREATED);
    }

    public function deleteMetaFieldAction(Request $request, Response $response, $args)
    {
        $metaFieldId = $args['meta_field_id'];

        $acl = $this->getAcl();
        $auth = $this->getAuth();

        if (!$metaFieldId) {
            return $response->withStatus(self::STATUS_BAD_REQUEST);
        }

        $metaFieldService = $this->getContainer()->get('MetaFieldService');
        $metaField = $metaFieldService->getMetaField($metaFieldId);

        if (!$metaField) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $role = new UserRole($auth->getIdentity());
        $privilege = MetaFieldPrivilege::PRIVILEGE_DELETE;
        if (!$acl->isAllowed($role, $metaField, $privilege)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }

        $metaFieldService->deleteMetaField($metaField, $auth->getIdentity());

        return $response->withStatus(self::STATUS_NO_CONTENT);
    }

    public function updateMetaFieldAction(Request $request, Response $response, $args)
    {
        $metaFieldId = $args['meta_field_id'];

        $acl = $this->getAcl();
        $auth = $this->getAuth();

        if (!$metaFieldId) {
            return $response->withStatus(self::STATUS_BAD_REQUEST);
        }

        $metaFieldService = $this->getContainer()->get('MetaFieldService');
        $metaField = $metaFieldService->getMetaField($metaFieldId);

        if (!$metaField) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $data = $request->getParsedBody();

        $role = new UserRole($auth->getIdentity());
        $privilege = MetaFieldPrivilege::PRIVILEGE_MODIFY;
        if (!$acl->isAllowed($role, $metaField, $privilege)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }
        $metaField = $metaFieldService->updateMetaField($metaField, $data, $auth->getIdentity());

        return $response->withJson($metaField);
    }


    public function getFileMetaFieldsValuesAction(Request $request, Response $response, $args)
    {
        $fileId = $args['file_id'];

        if (!$fileId) {
            return $response->withStatus(self::STATUS_BAD_REQUEST);
        }

        $fileService = $this->getContainer()->get('FileService');
        $file = $fileService->getFile($fileId);

        if (!$file) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        return $this->getMetaFieldsValues($response, $file);
    }

    public function getFolderMetaFieldsValuesAction(Request $request, Response $response, $args)
    {
        $folderId = $args['folder_id'];

        if (!$folderId) {
            return $response->withStatus(self::STATUS_BAD_REQUEST);
        }

        $folder = $this->getFolderService()->getFolder($folderId);

        if (!$folder) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        return $this->getMetaFieldsValues($response, $folder);
    }

    public function addFileMetaFieldValueAction(Request $request, Response $response, $args)
    {
        $fileId = $args['file_id'];
        if (!$fileId) {
            return $response->withStatus(self::STATUS_BAD_REQUEST);
        }

        $file = $this->getFileService()->getFile($fileId);
        if (!$file) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $data = $request->getParsedBody();

        return $this->addMetaFieldValue($response, $file, $data);
    }

    public function addFolderMetaFieldValueAction(Request $request, Response $response, $args)
    {
        $folderId = $args['folder_id'];
        if (!$folderId) {
            return $response->withStatus(self::STATUS_BAD_REQUEST);
        }

        $folder = $this->getFolderService()->getFolder($folderId);
        if (!$folder) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $data = $request->getParsedBody();

        return $this->addMetaFieldValue($response, $folder, $data);
    }

    private function addMetaFieldValue(Response $response, File $file, $data)
    {
        $acl = $this->getAcl();
        $auth = $this->getAuth();

        $role = new UserRole($auth->getIdentity());
        $privilege = FilePrivilege::PRIVILEGE_ADD_META_FIELDS_VALUES;
        if (!$acl->isAllowed($role, $file, $privilege)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }

        $metaFieldValue = $this->getMetaFieldService()->addMetaFieldValue($file, $data, $auth->getIdentity());

        return $response->withJson($metaFieldValue, self::STATUS_CREATED);
    }

    private function getMetaFieldsValues(Response $response, File $file)
    {
        $acl = $this->getAcl();
        $auth = $this->getAuth();

        $role = new UserRole($auth->getIdentity());
        $privilege = FilePrivilege::PRIVILEGE_READ_META_FIELDS_VALUES;
        if (!$acl->isAllowed($role, $file, $privilege)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }

        $metaFieldsValues = $this->getMetaFieldService()->getMetaFieldsValues($file);

        return $response->withJson($metaFieldsValues->toArray());
    }

    public function updateMetaFieldValueAction(Request $request, Response $response, $args)
    {
        $metaFieldValueId = $args['meta_field_value_id'];

        $acl = $this->getAcl();
        $auth = $this->getAuth();

        if (!$metaFieldValueId) {
            return $response->withStatus(self::STATUS_BAD_REQUEST);
        }

        $metaFieldService = $this->getContainer()->get('MetaFieldService');
        $metaFieldValue = $metaFieldService->getMetaFieldValue($metaFieldValueId);

        if (!$metaFieldValue) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $data = $request->getParsedBody();

        $role = new UserRole($auth->getIdentity());
        $privilege = MetaFieldValuePrivilege::PRIVILEGE_MODIFY;
        if (!$acl->isAllowed($role, $metaFieldValue, $privilege)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }
        $metaFieldValue = $metaFieldService->updateMetaFieldValue($metaFieldValue, $data, $auth->getIdentity());

        return $response->withJson($metaFieldValue);
    }

    public function deleteMetaFieldValueAction(Request $request, Response $response, $args)
    {
        $metaFieldValueId = $args['meta_field_value_id'];

        $acl = $this->getAcl();
        $auth = $this->getAuth();

        if (!$metaFieldValueId) {
            return $response->withStatus(self::STATUS_BAD_REQUEST);
        }

        $metaFieldService = $this->getContainer()->get('MetaFieldService');
        $metaFieldValue = $metaFieldService->getMetaFieldValue($metaFieldValueId);

        if (!$metaFieldValue) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $role = new UserRole($auth->getIdentity());
        $privilege = MetaFieldValuePrivilege::PRIVILEGE_DELETE;
        if (!$acl->isAllowed($role, $metaFieldValue, $privilege)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }

        $metaFieldService->deleteMetaFieldValue($metaFieldValue, $auth->getIdentity());

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
     * @return FolderService
     */
    private function getFolderService()
    {
        return $this->getContainer()->get('FolderService');
    }

    /**
     * @return MetaFieldService
     */
    private function getMetaFieldService()
    {
        return $this->getContainer()->get('MetaFieldService');
    }
}
