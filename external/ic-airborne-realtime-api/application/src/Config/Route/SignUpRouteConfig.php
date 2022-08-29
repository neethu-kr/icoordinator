<?php

namespace iCoordinator\Config\Route;

use iCoordinator\Config\AbstractConfig;
use Slim\App;

class SignUpRouteConfig extends AbstractConfig
{
    const ROUTE_SIGN_UP_CHECK_EMAIL         = 'checkSignUpEmailEndpoint';
    const ROUTE_SIGN_UP_SECURE_FIELDS_GET   = 'getSignUpSecureFieldsEndpoint';
    const ROUTE_SIGN_UP                     = 'signUpEndpoint';
    const ROUTE_SIGN_UP_CONFIRM_EMAIL       = 'signUpConfirmEmailEndpoint';
    const ROUTE_SIGN_UP_USER_INVITE         = 'inviteUserEndpoint';
    const ROUTE_SIGN_UP_ACCEPT_INVITATION   = 'acceptInvitationEndpoint';
    const ROUTE_SIGN_UP_PORTAL_INVITATIONS_GET = 'getPortalInvitationsEndpoint';
    const ROUTE_SIGN_UP_INVITATION_RESEND   = 'resendInvitationEndpoint';
    const ROUTE_SIGN_UP_INVITATION_DELETE   = 'deleteInvitationEndpoint';

    public function configure(App $app)
    {
        $app->post('/sign-up/check-email', 'SignUpController:checkEmailAction')
            ->setName(self::ROUTE_SIGN_UP_CHECK_EMAIL);

        $app->get('/sign-up/secure-fields', 'SignUpController:getSecureFieldsAction')
            ->setName(self::ROUTE_SIGN_UP_SECURE_FIELDS_GET);

        $app->post('/sign-up', 'SignUpController:signUpAction')
            ->setName(self::ROUTE_SIGN_UP);

        $app->map(['GET', 'POST'], '/sign-up/{token}/confirm-email', 'SignUpController:signUpConfirmEmailAction')
            ->setName(self::ROUTE_SIGN_UP_CONFIRM_EMAIL);

        $app->map(['GET', 'POST'], '/invitations/{token}/accept', 'SignUpController:acceptInvitationAction')
            ->setName(self::ROUTE_SIGN_UP_ACCEPT_INVITATION);

        $app->post('/invitations', 'SignUpController:inviteUserAction')
            ->setName(self::ROUTE_SIGN_UP_USER_INVITE);

        $app->post('/invitations/{invitation_id}/resend', 'SignUpController:resendInvitationAction')
            ->setName(self::ROUTE_SIGN_UP_INVITATION_RESEND);

        $app->delete('/invitations/{invitation_id}', 'SignUpController:deleteInvitationAction')
            ->setName(self::ROUTE_SIGN_UP_INVITATION_DELETE);

        $app->get('/portals/{portal_id}/invitations', 'SignUpController:getPortalInvitationsAction')
            ->setName(self::ROUTE_SIGN_UP_PORTAL_INVITATIONS_GET);
    }
}
