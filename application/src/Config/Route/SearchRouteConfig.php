<?php

namespace iCoordinator\Config\Route;

use iCoordinator\Config\AbstractConfig;
use Slim\App;

class SearchRouteConfig extends AbstractConfig
{
    const ROUTE_SEARCH_LIST = 'listSearchEndpoint';

    public function configure(App $app)
    {
        $app->get('/search', 'SearchController:searchAction')
            ->setName(self::ROUTE_SEARCH_LIST);
    }
}
