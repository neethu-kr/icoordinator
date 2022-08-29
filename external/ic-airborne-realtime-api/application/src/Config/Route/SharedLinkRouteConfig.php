<?php

namespace iCoordinator\Config\Route;

use iCoordinator\Config\AbstractConfig;
use Slim\App;

class SharedLinkRouteConfig extends AbstractConfig
{
    const ROUTE_SHARED_LINK_GET = 'getSharedLinkEndpoint';
    const ROUTE_SHARED_LINK_URL_GET = 'getSharedLinkUrlEndpoint';
    const ROUTE_SHARED_LINK_SEND_NOTIFICATION = 'sendSharedLinkNotification';
    const ROUTE_SHARED_LINK_MULTI_SEND_NOTIFICATION = 'sendSharedLinkMultiNotification';

    public function configure(App $app)
    {
        $app->get('/shared-links/{token}', 'SharedLinksController:getSharedLinkAction')
            ->setName(self::ROUTE_SHARED_LINK_GET);

        $app->get('/shared-links/{shared_link_id}/url', 'SharedLinksController:getSharedLinkUrlAction')
            ->setName(self::ROUTE_SHARED_LINK_URL_GET);

        $app->post(
            '/shared-links/{shared_link_id}/email-notifications',
            'SharedLinksController:sendSharedLinkNotificationAction'
        )->setName(self::ROUTE_SHARED_LINK_SEND_NOTIFICATION);
        $app->post(
            '/shared-links/multi-email-notifications',
            'SharedLinksController:sendMultipleSharedLinkNotificationAction'
        )->setName(self::ROUTE_SHARED_LINK_MULTI_SEND_NOTIFICATION);
    }
}
