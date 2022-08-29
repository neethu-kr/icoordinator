<?php

namespace iCoordinator\Config;

use iCoordinator\ContainerAwareTrait;
use Slim\App;
use Slim\Container;

class ControllersConfig extends AbstractConfig
{
    use ContainerAwareTrait;

    private $controllersNamespace = 'iCoordinator\\Controller\\';

    public function configure(App $app)
    {
        $this->container = $app->getContainer();
        $this->addController('DefaultController')
            ->addController('AuthController')
            ->addController('PortalsController')
            ->addController('UsersController')
            ->addController('EventsController')
            ->addController('FilesController')
            ->addController('FoldersController')
            ->addController('HistoryEventsController')
            ->addController('SmartFoldersController')
            ->addController('GroupsController')
            ->addController('FileEmailOptionsController')
            ->addController('InboundEmailsController')
            ->addController('MetaFieldsController')
            ->addController('PermissionsController')
            ->addController('SelectiveSyncController')
            ->addController('SharedLinksController')
            ->addController('WorkspacesController')
            ->addController('SearchController')
            ->addController('StateController')
            ->addController('SignUpController')
            ->addController('ChargifyController')
            ->addController('CustomerSpecific\\Norway\\FDVController');
    }

    /**
     * @param $controllerName
     * @return $this
     */
    private function addController($controllerName)
    {
        /** @var Container $c */
        $c = $this->getContainer();
        $c[$controllerName] = function ($c) use ($controllerName) {
            $controllerClass = $this->controllersNamespace . $controllerName;
            return new $controllerClass($c);
        };

        return $this;
    }
}
