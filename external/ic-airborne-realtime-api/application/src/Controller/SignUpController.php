<?php

namespace iCoordinator\Controller;

use iCoordinator\Entity\Error;
use iCoordinator\Permissions\Privilege\PortalPrivilege;
use iCoordinator\Permissions\Role\UserRole;
use iCoordinator\Service\EmailConfirmationService;
use iCoordinator\Service\PortalService;
use iCoordinator\Service\SignUpService;
use iCoordinator\Service\SubscriptionService;
use iCoordinator\Service\UserService;
use Slim\Http\Request;
use Slim\Http\Response;

class SignUpController extends AbstractRestController
{
    public function checkEmailAction(Request $request, Response $response, $args)
    {
        $email = $request->getParam('email');
        $exists = $this->getUserService()->checkEmailExists($email);

        return $response->withJson(['exists' => $exists]);
    }

    public function getSecureFieldsAction(Request $request, Response $response, $args)
    {
        $websiteId = $request->getParam('website_id');
        $redirectUrl = $request->getParam('redirect_url');

        $secureFields = $this->getSignUpService()->getSecureFields($websiteId, $redirectUrl);

        return $response->withJson($secureFields);
    }

    public function signUpAction(Request $request, Response $response, $args)
    {
        $data = $request->getParsedBody();

        $user = $this->getSignUpService()->signUp($data);

        return $response->withJson($user, self::STATUS_ACCEPTED);
    }

    public function signUpConfirmEmailAction(Request $request, Response $response, $args)
    {
        $token = $args['token'];

        $user = $this->getEmailConfirmationService()->signUpConfirmEmail($token);

        return $response->withJson($user);
    }

    public function inviteUserAction(Request $request, Response $response, $args)
    {
        $acl = $this->getAcl();
        $auth = $this->getAuth();

        $data = $request->getParsedBody();

        if (!isset($data['portal']) || !isset($data['portal']['id'])) {
            return $response->withStatus(self::STATUS_BAD_REQUEST);
        }

        $portal = $this->getPortalService()->getPortal($data['portal']['id']);

        if (!$portal) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $role = new UserRole($auth->getIdentity());
        $privilege = PortalPrivilege::PRIVILEGE_GRANT_ACCESS_PERMISSION;
        if (!$acl->isAllowed($role, $portal, $privilege)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }

        if (!$this->getSubscriptionService()->checkCanInviteUser($portal)) {
            $error = new Error(Error::LICENSE_UPDATE_REQUIRED);
            return $response->withJson($error, self::STATUS_FORBIDDEN);
        }

        //TODO: move to another oAuth scope
        $disableEmailConfirmation = $request->getParam('disable_email_confirmation', false);

        $invitation = $this->getSignUpService()->inviteUser($data, $auth->getIdentity(), !$disableEmailConfirmation);

        if ($disableEmailConfirmation) {
            return $response->withJson($invitation, self::STATUS_CREATED);
        } else {
            return $response->withJson($invitation, self::STATUS_ACCEPTED);
        }
    }

    public function acceptInvitationAction(Request $request, Response $response, $args)
    {
        $token = $args['token'];

        $invitation = $this->getSignUpService()->getInvitationByToken($token);
        if (!$invitation) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }
        if (!$this->getSubscriptionService()->checkCanAddUser($invitation->getPortal())) {
            $error = new Error(Error::LICENSE_UPDATE_REQUIRED);
            return $response->withJson($error, self::STATUS_FORBIDDEN);
        }
        $portal = $this->getSignUpService()->acceptInvitation($token);
        return $response->withJson($portal);
    }

    public function getPortalInvitationsAction(Request $request, Response $response, $args)
    {
        $portalId = $args['portal_id'];

        if (!$portalId) {
            return $response->withStatus(self::STATUS_BAD_REQUEST);
        }

        $portal = $this->getPortalService()->getPortal($portalId);

        if (!$portal) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $acl = $this->getAcl();
        $auth = $this->getAuth();

        $role = new UserRole($auth->getIdentity());
        $privilege = PortalPrivilege::PRIVILEGE_GRANT_ACCESS_PERMISSION;
        if (!$acl->isAllowed($role, $portal, $privilege)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }

        $invitations = $this->getSignUpService()->getPortalInvitations($portal);

        return $response->withJson($invitations);
    }

    public function deleteInvitationAction(Request $request, Response $response, $args)
    {
        $portalId = null;
        $invitationId = $args['invitation_id'];

        $invitation = $this->getSignUpService()->getInvitationById($invitationId);
        if ($invitation) {
            $portalId = $invitation->getPortal();
        }
        if (!$portalId) {
            return $response->withStatus(self::STATUS_BAD_REQUEST);
        }

        $portal = $this->getPortalService()->getPortal($portalId);

        if (!$portal) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $acl = $this->getAcl();
        $auth = $this->getAuth();

        $role = new UserRole($auth->getIdentity());
        $privilege = PortalPrivilege::PRIVILEGE_GRANT_ACCESS_PERMISSION;
        if (!$acl->isAllowed($role, $portal, $privilege)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }

        $this->getSignUpService()->deleteInvitation($invitationId);

        return $response->withStatus(self::STATUS_NO_CONTENT);
    }

    public function resendInvitationAction(Request $request, Response $response, $args)
    {
        $portalId = null;
        $invitationId = $args['invitation_id'];

        $invitation = $this->getSignUpService()->getInvitationById($invitationId);
        if ($invitation) {
            $portalId = $invitation->getPortal();
        }

        if (!$portalId) {
            return $response->withStatus(self::STATUS_BAD_REQUEST);
        }

        $portal = $this->getPortalService()->getPortal($portalId);

        if (!$portal) {
            return $response->withStatus(self::STATUS_NOT_FOUND);
        }

        $acl = $this->getAcl();
        $auth = $this->getAuth();

        $role = new UserRole($auth->getIdentity());
        $privilege = PortalPrivilege::PRIVILEGE_GRANT_ACCESS_PERMISSION;
        if (!$acl->isAllowed($role, $portal, $privilege)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }

        $this->getSignUpService()->resendInvitation($invitationId);

        return $response->withStatus(self::STATUS_NO_CONTENT);
    }
    /**
     * @return SignUpService
     */
    private function getSignUpService()
    {
        return $this->getContainer()->get('SignUpService');
    }

    /**
     * @return EmailConfirmationService
     */
    private function getEmailConfirmationService()
    {
        return $this->getContainer()->get('EmailConfirmationService');
    }

    /**
     * @return UserService
     */
    private function getUserService()
    {
        return $this->getContainer()->get('UserService');
    }

    /**
     * @return PortalService
     */
    private function getPortalService()
    {
        return $this->getContainer()->get('PortalService');
    }

    /**
     * @return SubscriptionService
     */
    private function getSubscriptionService()
    {
        return $this->getContainer()->get('SubscriptionService');
    }
}
