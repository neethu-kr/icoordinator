<?php

namespace iCoordinator\Config\Route\CustomerSpecific\Norway;

use iCoordinator\Config\AbstractConfig;
use Slim\App;

class FDVRouteConfig extends AbstractConfig
{
    const ROUTE_ENTRIES_GET = 'getEntriesEndpoint';
    const ROUTE_ENTRY_ADD = 'addEntryEndpoint';
    const ROUTE_ENTRY_UPDATE = 'updateEntryEndpoint';
    const ROUTE_PORTAL_LICENSE_GET = 'getPortalLicenseEndpoint';
    const ROUTE_PORTAL_EXPORT_GET = 'getPortalExportEndpoint';

    public function configure(App $app)
    {
        $app->get('/fdv', 'CustomerSpecific\\Norway\\FDVController:getEntriesAction')
            ->setName(self::ROUTE_ENTRIES_GET);

        $app->post('/fdv', 'CustomerSpecific\\Norway\\FDVController:addEntryAction')
            ->setName(self::ROUTE_ENTRY_ADD);

        $app->put('/fdv/{fdv_id}', 'CustomerSpecific\\Norway\\FDVController:updateEntryAction')
            ->setName(self::ROUTE_ENTRY_UPDATE);

        $app->get('/fdv/{portal_id}/license', 'CustomerSpecific\\Norway\\FDVController:getLicenseAction')
            ->setName(self::ROUTE_PORTAL_LICENSE_GET);

        $app->get('/fdv/{portal_id}/export', 'CustomerSpecific\\Norway\\FDVController:exportEntriesAction')
            ->setName(self::ROUTE_PORTAL_EXPORT_GET);
    }
}
