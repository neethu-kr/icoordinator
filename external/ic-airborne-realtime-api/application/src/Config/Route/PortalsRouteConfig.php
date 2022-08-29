<?php

namespace iCoordinator\Config\Route;

use iCoordinator\Config\AbstractConfig;
use Slim\App;

class PortalsRouteConfig extends AbstractConfig
{
    const ROUTE_PORTALS_LIST = 'listPortalsEndpoint';
    const ROUTE_PORTAL_CREATE = 'createPortalEndpoint';
    const ROUTE_PORTAL_USERS_LIST = 'listPortalUsersEndpoint';
    const ROUTE_PORTAL_PERMISSIONS_LIST = 'listPortalPermissionsEndpoint';
    const ROUTE_PORTAL_PERMISSION_ADD = 'addPortalPermissionEndpoint';
    const ROUTE_PORTAL_ALLOWED_CLIENTS_LIST = 'listPortalAllowedClientsEndpoint';
    const ROUTE_PORTAL_ALLOWED_CLIENTS_ADD = 'addPortalAllowedClientsEndpoint';
    const ROUTE_PORTAL_ALLOWED_CLIENTS_UPDATE = 'updatePortalAllowedClientsEndpoint';
    const ROUTE_PORTAL_GET_INFO = 'getPortalInfo';

    public function configure(App $app)
    {
        $app->get('/portals', 'PortalsController:getPortalsListAction')
            ->setName(self::ROUTE_PORTALS_LIST);

        $app->post('/portals', 'PortalsController:createPortalAction')
            ->setName(self::ROUTE_PORTAL_CREATE);

        $app->get('/portals/{portal_id}/users', 'UsersController:getPortalUsersListAction')
            ->setName(self::ROUTE_PORTAL_USERS_LIST);

        $app->get('/portals/{portal_id}/permissions', 'PermissionsController:getPortalPermissionsAction')
            ->setName(self::ROUTE_PORTAL_PERMISSIONS_LIST);

        $app->post('/portals/{portal_id}/permissions', 'PermissionsController:addPortalPermissionAction')
            ->setName(self::ROUTE_PORTAL_PERMISSION_ADD);

        $app->get('/portals/{portal_id}/allowedclients', 'PortalsController:getAllowedClientsAction')
            ->setName(self::ROUTE_PORTAL_ALLOWED_CLIENTS_LIST);

        $app->post('/portals/{portal_id}/allowedclients', 'PortalsController:setAllowedClientsAction')
            ->setName(self::ROUTE_PORTAL_ALLOWED_CLIENTS_ADD);

        $app->put(
            '/portals/{portal_id}/allowedclients/{allowed_client_id}',
            'PortalsController:updateAllowedClientsAction'
        )->setName(self::ROUTE_PORTAL_ALLOWED_CLIENTS_UPDATE);

        $app->get('/portals/{portal_id}/info', 'PortalsController:getPortalInfoAction')
            ->setName(self::ROUTE_PORTAL_GET_INFO);
    }
}
