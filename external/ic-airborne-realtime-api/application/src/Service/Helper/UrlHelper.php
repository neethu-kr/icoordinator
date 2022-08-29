<?php

namespace iCoordinator\Service\Helper;

use iCoordinator\Entity\Folder;
use iCoordinator\Entity\SharedLink;
use Psr\Container\ContainerInterface;

class UrlHelper
{
    public static function getApiBaseUrl(ContainerInterface $c, $path = '')
    {
        $settings = $c->get('settings');
        return $settings['api_base_url'] . $path;
    }

    public static function getWebApplicationPortalUrl(ContainerInterface $c, $path = '')
    {
        //TODO
    }

    public static function getSharedLinkUrl(ContainerInterface $c, SharedLink $sharedLink)
    {
        if ($sharedLink->getFile() instanceof Folder) {
            return self::getWebApplicationBaseUrl($c, '#/folder/view/' . $sharedLink->getToken());
        } else {
            return self::getWebApplicationBaseUrl($c, '#/file/view/' . $sharedLink->getToken());
        }
    }

    public static function getRealTimeServerUrl(ContainerInterface $c, $channelName)
    {
        $realtimeServerUrl = $c->get('settings')['realtime_server_url'];
        return $realtimeServerUrl . '/channels/' . $channelName;
    }

    public static function getWebApplicationBaseUrl(ContainerInterface $c, $path = '')
    {
        $settings = $c->get('settings');
        return $settings['web_base_url'] . $path;
    }
}
