<?php

namespace iCoordinator\Config\Route;

use iCoordinator\Config\AbstractConfig;
use Slim\App;

class MetaFieldsRouteConfig extends AbstractConfig
{
    const ROUTE_META_FIELDS_LIST = 'listMetaFieldsEndpoint';
    const ROUTE_META_FIELD_GET = 'getMetaFieldEndpoint';
    const ROUTE_META_FIELD_ADD = 'addMetaFieldEndpoint';
    const ROUTE_META_FIELD_DELETE = 'deleteMetaFieldEndpoint';
    const ROUTE_META_FIELD_UPDATE = 'updateMetaFieldEndpoint';
    const ROUTE_META_FIELD_VALUE_DELETE = 'deleteMetaFieldValueEndpoint';
    const ROUTE_META_FIELD_VALUE_UPDATE = 'updateMetaFieldValueEndpoint';

    public function configure(App $app)
    {
        $app->get('/portals/{portal_id}/meta-fields', 'MetaFieldsController:getMetaFieldsListAction')
            ->setName(self::ROUTE_META_FIELDS_LIST);

        $app->get('/meta-fields/{meta_field_id}', 'MetaFieldsController:getMetaFieldAction')
            ->setName(self::ROUTE_META_FIELD_GET);

        $app->post('/portals/{portal_id}/meta-fields', 'MetaFieldsController:addMetaFieldAction')
            ->setName(self::ROUTE_META_FIELD_ADD);

        $app->put('/meta-fields/{meta_field_id}', 'MetaFieldsController:updateMetaFieldAction')
            ->setName(self::ROUTE_META_FIELD_UPDATE);

        $app->delete('/meta-fields/{meta_field_id}', 'MetaFieldsController:deleteMetaFieldAction')
            ->setName(self::ROUTE_META_FIELD_DELETE);

        $app->put('/meta-fields-values/{meta_field_value_id}', 'MetaFieldsController:updateMetaFieldValueAction')
            ->setName(self::ROUTE_META_FIELD_VALUE_UPDATE);

        $app->delete('/meta-fields-values/{meta_field_value_id}', 'MetaFieldsController:deleteMetaFieldValueAction')
            ->setName(self::ROUTE_META_FIELD_VALUE_DELETE);
    }
}
