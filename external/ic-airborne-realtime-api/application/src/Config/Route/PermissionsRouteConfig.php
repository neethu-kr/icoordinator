<?php

namespace iCoordinator\Config\Route;

use iCoordinator\Config\AbstractConfig;
use Slim\App;

class PermissionsRouteConfig extends AbstractConfig
{
    const ROUTE_PERMISSION_GET = 'getPermissionEndpoint';
    const ROUTE_PERMISSION_UPDATE = 'updatePermissionEndpoint';
    const ROUTE_PERMISSION_DELETE = 'deletePermissionEndpoint';

    public function configure(App $app)
    {
        $app->get('/permissions/{permission_id}', 'PermissionsController:getPermissionAction')
            ->setName(self::ROUTE_PERMISSION_GET);

        $app->put('/permissions/{permission_id}', 'PermissionsController:updatePermissionAction')
            ->setName(self::ROUTE_PERMISSION_UPDATE);

        $app->delete('/permissions/{permission_id}', 'PermissionsController:deletePermissionAction')
            ->setName(self::ROUTE_PERMISSION_DELETE);
    }
}
