<?php

namespace iCoordinator\Config\Route;

use iCoordinator\Config\AbstractConfig;
use iCoordinator\Permissions\Privilege\FilePrivilege;
use iCoordinator\Route\Middleware\SmartFolderMiddleware;
use Slim\App;
use Slim\CallableResolverAwareTrait;

class SmartFoldersRouteConfig extends AbstractConfig
{
    use CallableResolverAwareTrait;

    //enpoint names
    const ROUTE_SMART_FOLDER_GET = 'getSmartFolderEndpoint';
    const ROUTE_SMART_FOLDER_ADD = 'addSmartFolderEndpoint';
    const ROUTE_SMART_FOLDER_DELETE = 'deleteSmartFolderEndpoint';
    const ROUTE_SMART_FOLDER_DELETE_PERMANENTLY = 'deleteSmartFolderPermanentlyEndpoint';
    const ROUTE_SMART_FOLDER_UPDATE = 'updateSmartFolderEndpoint';
    const ROUTE_SMART_FOLDER_RESTORE = 'restoreSmartFolderEndpoint';
    const ROUTE_SMART_FOLDER_CHILDREN_GET = 'getSmartFolderChildrenEndpoint';
    const ROUTE_SMART_FOLDER_PERMISSIONS_LIST = 'listSmartFolderPermissionsEndpoint';
    const ROUTE_SMART_FOLDER_PERMISSION_ADD = 'addSmartFolderPermissionEndpoint';
    const ROUTE_SMART_FOLDER_META_FIELDS_VALUES_GET_LIST = 'getSmartFolderMetaFieldsValuesListEndpoint';
    const ROUTE_SMART_FOLDER_META_FIELD_VALUE_ADD = 'addSmartFolderMetaFieldValueEndpoint';
    const ROUTE_SMART_FOLDER_META_FIELD_CRITERIA_GET_LIST = 'getSmartFolderMetaFieldCriteriaListEndpoint';
    const ROUTE_SMART_FOLDER_META_FIELD_CRITERION_ADD = 'addSmartFolderMetaFieldCriterionEndpoint';
    const ROUTE_SMART_FOLDER_META_FIELD_CRITERION_UPDATE = 'updateSmartFolderMetaFieldCriterionEndpoint';
    const ROUTE_SMART_FOLDER_META_FIELD_CRITERION_DELETE = 'deleteSmartFolderMetaFieldCriterionEndpoint';

    //available route arguments
    const ARGUMENT_PRIVILEGE = 'privilege';
    
    public function configure(App $app)
    {
        $this->container = $app->getContainer();

        $smartFolderMiddleware = $this->resolveCallable('SmartFoldersController:preDispatchSmartFolderMiddleware');

        $app->get('/smart-folders/{smart_folder_id}', 'SmartFoldersController:getSmartFolderAction')
            ->add($smartFolderMiddleware)
            ->setArgument(self::ARGUMENT_PRIVILEGE, FilePrivilege::PRIVILEGE_READ)
            ->setName(self::ROUTE_SMART_FOLDER_GET);

        $app->get('/smart-folders/{smart_folder_id}/children', 'SmartFoldersController:getSmartFolderChildrenAction')
            ->add($smartFolderMiddleware)
            ->setName(self::ROUTE_SMART_FOLDER_CHILDREN_GET);

        $app->post('/workspaces/{workspace_id}/smart-folders', 'SmartFoldersController:addSmartFolderAction')
            ->setName(self::ROUTE_SMART_FOLDER_ADD);

        $app->delete('/smart-folders/{smart_folder_id}', 'SmartFoldersController:trashSmartFolderAction')
            ->add($smartFolderMiddleware)
            ->setArgument(self::ARGUMENT_PRIVILEGE, FilePrivilege::PRIVILEGE_DELETE)
            ->setName(self::ROUTE_SMART_FOLDER_DELETE);

        $app->post('/smart-folders/{smart_folder_id}', 'SmartFoldersController:restoreSmartFolderAction')
            ->add($smartFolderMiddleware)
            ->setArgument(self::ARGUMENT_PRIVILEGE, FilePrivilege::PRIVILEGE_MODIFY)
            ->setName(self::ROUTE_SMART_FOLDER_RESTORE);

        $app->delete('/smart-folders/{smart_folder_id}/trash', 'SmartFoldersController:deleteSmartFolderAction')
            ->add($smartFolderMiddleware)
            ->setArgument(self::ARGUMENT_PRIVILEGE, FilePrivilege::PRIVILEGE_DELETE)
            ->setName(self::ROUTE_SMART_FOLDER_DELETE_PERMANENTLY);

        $app->put('/smart-folders/{smart_folder_id}', 'SmartFoldersController:updateSmartFolderAction')
            ->add($smartFolderMiddleware)
            ->setArgument(self::ARGUMENT_PRIVILEGE, FilePrivilege::PRIVILEGE_MODIFY)
            ->setName(self::ROUTE_SMART_FOLDER_UPDATE);

        //permissions management

        $app->get(
            '/smart-folders/{smart_folder_id}/permissions',
            'PermissionsController:getSmartFolderPermissionsAction'
        )->add($smartFolderMiddleware)
            ->setArgument(self::ARGUMENT_PRIVILEGE, FilePrivilege::PRIVILEGE_READ_PERMISSIONS)
            ->setName(self::ROUTE_SMART_FOLDER_PERMISSIONS_LIST);


        $app->post(
            '/smart-folders/{smart_folder_id}/permissions',
            'PermissionsController:addSmartFolderPermissionAction'
        )->add($smartFolderMiddleware)
            ->setName(self::ROUTE_SMART_FOLDER_PERMISSION_ADD);

        //meta values management

        $app->get(
            '/smart-folders/{smart_folder_id}/meta-fields-values',
            'MetaFieldsController:getMetaFieldsValuesAction'
        )->add($smartFolderMiddleware)
            ->setArgument(self::ARGUMENT_PRIVILEGE, FilePrivilege::PRIVILEGE_READ_META_FIELDS_VALUES)
            ->setName(self::ROUTE_SMART_FOLDER_META_FIELDS_VALUES_GET_LIST);

        $app->post(
            '/smart-folders/{smart_folder_id}/meta-fields-values',
            'MetaFieldsController:addMetaFieldValueAction'
        )->add($smartFolderMiddleware)
            ->setArgument(self::ARGUMENT_PRIVILEGE, FilePrivilege::PRIVILEGE_ADD_META_FIELDS_VALUES)
            ->setName(self::ROUTE_SMART_FOLDER_META_FIELD_VALUE_ADD);

        //criteria management

        $app->get(
            '/smart-folders/{smart_folder_id}/meta-fields-criteria',
            'SmartFoldersController:getSmartFolderCriteriaAction'
        )->add($smartFolderMiddleware)
            ->setArgument(self::ARGUMENT_PRIVILEGE, FilePrivilege::PRIVILEGE_READ_META_FIELDS_CRITERIA)
            ->setName(self::ROUTE_SMART_FOLDER_META_FIELD_CRITERIA_GET_LIST);

        $app->post(
            '/smart-folders/{smart_folder_id}/meta-fields-criteria',
            'SmartFoldersController:addSmartFolderCriterionAction'
        )->add($smartFolderMiddleware)
            ->setArgument(self::ARGUMENT_PRIVILEGE, FilePrivilege::PRIVILEGE_ADD_META_FIELDS_CRITERIA)
            ->setName(self::ROUTE_SMART_FOLDER_META_FIELD_CRITERION_ADD);

        $app->put(
            '/meta-fields-criteria/{meta_field_criterion_id}',
            'SmartFoldersController:updateSmartFolderCriterionAction'
        )->setName(self::ROUTE_SMART_FOLDER_META_FIELD_CRITERION_UPDATE);

        $app->delete(
            '/meta-fields-criteria/{meta_field_criterion_id}',
            'SmartFoldersController:deleteSmartFolderCriterionAction'
        )->setName(self::ROUTE_SMART_FOLDER_META_FIELD_CRITERION_DELETE);
    }
}
