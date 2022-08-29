<?php

namespace iCoordinator\Controller;

use iCoordinator\Controller\Helper\FilesControllerHelper;
use iCoordinator\Permissions\Privilege\FilePrivilege;
use iCoordinator\Permissions\Role\UserRole;
use Slim\Http\Request;
use Slim\Http\Response;

class SharedLinksController extends AbstractRestController
{
    public function init()
    {
        $this->addHelper(new FilesControllerHelper());
    }

    public function getSharedLinkAction(Request $request, Response $response, $args)
    {
        $token = $args['token'];

        $auth = $this->getAuth();
        $acl = $this->getAcl();

        $sharedLinkService = $this->getContainer()->get('SharedLinkService');
        $sharedLink = $sharedLinkService->getSharedLinkByToken($token);

        if (!$sharedLink) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        //check permissions for underlying resource
        $file = $sharedLink->getFile();
        $role = $this->getRole($request, $token);
        $privilege = FilePrivilege::PRIVILEGE_READ;

        if (!$acl->isAllowed($role, $file, $privilege)) {
            if (!$auth->getIdentity()) {
                return $response->withStatus(self::STATUS_UNAUTHORIZED);
            } else {
                return $response->withStatus(self::STATUS_FORBIDDEN);
            }
        }

        return $response->withJson($file);
    }

    /**
     * @param $token
     * @param null $user
     * @return mixed
     * @throws \Exception
     */
    private function getRole($request, $token, $user = null)
    {
        return $this->getHelper(FilesControllerHelper::HELPER_ID)->getRoleWithSharedLinkToken($request, $token, $user);
    }

    public function getSharedLinkUrlAction(Request $request, Response $response, $args)
    {
        $sharedLinkId = $args['shared_link_id'];

        $auth = $this->getAuth();
        $acl = $this->getAcl();

        $sharedLinkService = $this->getContainer()->get('SharedLinkService');
        $sharedLink = $sharedLinkService->getSharedLink($sharedLinkId);

        if (!$sharedLink) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $role = new UserRole($auth->getIdentity());
        if (!$acl->isAllowed($role, $sharedLink->getFile(), FilePrivilege::PRIVILEGE_CREATE_SHARED_LINK)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }
        $sharedLinkUrl = $sharedLinkService->getSharedLinkUrl($sharedLink);

        return $response->withJson(array('shared_link_url' => $sharedLinkUrl), self::STATUS_OK);
    }
    public function sendSharedLinkNotificationAction(Request $request, Response $response, $args)
    {
        $sharedLinkId = $args['shared_link_id'];

        $auth = $this->getAuth();
        $acl = $this->getAcl();

        $data = $request->getParsedBody();
        if (empty($data['emails']) || !is_array($data['emails'])) {
            return $response->withStatus(self::STATUS_BAD_REQUEST);
        }

        $sharedLinkService = $this->getContainer()->get('SharedLinkService');
        $sharedLink = $sharedLinkService->getSharedLink($sharedLinkId);

        if (!$sharedLink) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $role = new UserRole($auth->getIdentity());
        if (!$acl->isAllowed($role, $sharedLink->getFile(), FilePrivilege::PRIVILEGE_CREATE_SHARED_LINK)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }

        $data['successful_emails'] = array();
        $data['failed_emails'] = array();

        $userService = $this->getContainer()->get('UserService');
        foreach ($data['emails'] as $email) {
            $user = $userService->getUserByEmail($email);
            $role = $this->getRole($request, $sharedLink->getToken(), $user);
            if ($acl->isAllowed($role, $sharedLink->getFile(), FilePrivilege::PRIVILEGE_READ)) {
                array_push($data['successful_emails'], $email);
            } else {
                array_push($data['failed_emails'], $email);
            }
        }

        $emailNotification = $sharedLinkService->sendSharedLinkNotification(
            $sharedLink,
            $data,
            $userService->getUser($auth->getIdentity())
        );

        return $response->withJson($emailNotification, self::STATUS_CREATED);
    }

    public function sendMultipleSharedLinkNotificationAction(Request $request, Response $response, $args)
    {
        $auth = $this->getAuth();
        $acl = $this->getAcl();

        $data = $request->getParsedBody();
        if (empty($data['emails']) || !is_array($data['emails'])) {
            return $response->withStatus(self::STATUS_BAD_REQUEST);
        }

        $sharedLinkService = $this->getContainer()->get('SharedLinkService');
        $data['failed_ids'] = array();
        foreach ($data['all_ids'] as $key => $sharedLinkId) {
            $sharedLink = $sharedLinkService->getSharedLink($sharedLinkId);

            if (!$sharedLink) {
                unset($data['all_ids'][$key]);
                array_push($data['failed_ids'], $sharedLinkId);
            } else {
                $role = new UserRole($auth->getIdentity());
                if (!$acl->isAllowed($role, $sharedLink->getFile(), FilePrivilege::PRIVILEGE_CREATE_SHARED_LINK)) {
                    unset($data['all_ids'][$key]);
                    array_push($data['failed_ids'], $sharedLinkId);
                }
            }
        }
        if (count($data['all_ids']) == 1) {
            $args['shared_link_id'] = $data['all_ids'][0];
            return $this->sendSharedLinkNotificationAction($request, $response, $args);
        }
        $data['successful_emails'] = array();
        $data['failed_emails'] = array();

        $userService = $this->getContainer()->get('UserService');
        foreach ($data['emails'] as $email) {
            $selIds = array();
            foreach ($data['all_ids'] as $key => $sharedLinkId) {
                $sharedLink = $sharedLinkService->getSharedLink($sharedLinkId);
                $user = $userService->getUserByEmail($email);
                $role = $this->getRole($request, $sharedLink->getToken(), $user);
                if ($acl->isAllowed($role, $sharedLink->getFile(), FilePrivilege::PRIVILEGE_READ)) {
                    array_push($selIds, $sharedLink->getId());
                }
            }
            if (count($selIds) == count($data['all_ids'])) {
                array_push($data['successful_emails'], $email);
            } else {
                array_push($data['failed_emails'], $email);
            }
        }
        $emailNotification = $sharedLinkService->sendMultipleSharedLinkNotification(
            $data,
            $userService->getUser($auth->getIdentity())
        );

        return $response->withJson($emailNotification, self::STATUS_CREATED);
    }
}
