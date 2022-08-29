<?php

namespace iCoordinator\Controller\CustomerSpecific\Norway;

use iCoordinator\Controller\AbstractRestController;
use iCoordinator\Permissions\Privilege\PortalPrivilege;
use iCoordinator\Permissions\Resource\SystemResource;
use iCoordinator\Permissions\Role\UserRole;
use iCoordinator\Service\CustomerSpecific\Norway\FDVService;
use Slim\Http\Request;
use Slim\Http\Response;

class FDVController extends AbstractRestController
{
    public function getEntriesAction(Request $request, Response $response, $args)
    {
        $auth = $this->getAuth();

        $portal = $request->getParam('portal', null);
        $limit = $request->getParam('limit', 100);

        $fdvService = $this->getFDVService();

        $hasMore = true;
        $entries = array();

        $paginator = $fdvService->getPortalEntries($portal, $limit);

        if ($paginator->count() <= $limit) {
            $hasMore = false;
        }

        $entries = $paginator->getIterator()->getArrayCopy();

        $result = array(
            'has_more' => $hasMore,
            'entries' => $entries
        );

        return $response->withJson($result);
    }

    public function addEntryAction(Request $request, Response $response, $args)
    {
        $acl = $this->getAcl();
        $data = $request->getParsedBody();

        if (!isset($data['workspace'])) {
            return $response->withStatus(self::STATUS_BAD_REQUEST);
        }
        $workspace = $data['workspace'];
        unset($data['workspace']);
        unset($data['portal']);
        $createdBy    = $this->getAuth()->getIdentity();

        $role = new UserRole($createdBy);
        $privilege = PortalPrivilege::PRIVILEGE_READ_PERMISSIONS;
        if (!$acl->isAllowed($role, SystemResource::RESOURCE_ID, $privilege)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }

        $fdvEntry = $this->getFDVService()->addEntry($data, $workspace, $createdBy);

        return $response->withJson($fdvEntry, self::STATUS_CREATED);
    }

    /**
     * PUT /fdv/{fdv_id}
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     * @throws \iCoordinator\Service\Exception\NotFoundException
     */
    public function updateEntryAction(Request $request, Response $response, $args)
    {
        $acl = $this->getAcl();
        $fdvEntry = $request->getAttribute('fdv_id');
        $data = $request->getParsedBody();
        $updatedBy = $this->getAuth()->getIdentity();

        $role = new UserRole($updatedBy);
        $privilege = PortalPrivilege::PRIVILEGE_READ_PERMISSIONS;
        if (!$acl->isAllowed($role, SystemResource::RESOURCE_ID, $privilege)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }
        $fdvEntry = $this->getFDVService()->updateEntry($fdvEntry, $data, $updatedBy);

        return $response->withJson($fdvEntry);
    }

    public function getLicenseAction(Request $request, Response $response, $args)
    {
        $auth = $this->getAuth();

        $portal = $request->getAttribute('portal_id');

        $fdvService = $this->getFDVService();



        $license = $fdvService->getPortalLicense($portal);
        $result = array(
            'license' => $license
        );
        return $response->withJson($result);
    }

    public function exportEntriesAction(Request $request, Response $response, $args)
    {
        $auth = $this->getAuth();

        $portal = $request->getAttribute('portal_id');

        $fdvService = $this->getFDVService();


        $portalName = $this->getPortalService()->getPortal($portal)->getName();
        $data = $fdvService->getExportData($portal);

        return $response->withBody($data)
            ->withHeader('Content-Length', strlen($data))
            ->withHeader('Content-Type', 'application/vnd.ms-excel; charset=UTF-16LE')
            ->withHeader('Content-Disposition', 'attachment; filename="Komponentlogg-export - ' .$portalName. '.csv"')
            ->withHeader('Content-Transfer-Encoding', 'binary')
            ->withHeader('Expires', '0')
            ->withHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
            ->withHeader('Pragma', 'public');
    }
    /**
     * @return FDVService
     */
    public function getFDVService()
    {
        return $fdvService = $this->getContainer()->get('CustomerSpecific\\Norway\\FDVService');
    }

    /**
     * @return PortalService
     */
    public function getPortalService()
    {
        return $this->getContainer()->get('PortalService');
    }
}
