<?php

namespace iCoordinator\Config;

use iCoordinator\Config\Route\AuthRouteConfig;
use iCoordinator\Config\Route\ChargifyRouteConfig;
use iCoordinator\Config\Route\CustomerSpecific\Norway\FDVRouteConfig;
use iCoordinator\Config\Route\DefaultRouteConfig;
use iCoordinator\Config\Route\EventsRouteConfig;
use iCoordinator\Config\Route\FilesRouteConfig;
use iCoordinator\Config\Route\FoldersRouteConfig;
use iCoordinator\Config\Route\GroupsRouteConfig;
use iCoordinator\Config\Route\HistoryEventsRouteConfig;
use iCoordinator\Config\Route\InboundEmailsRouteConfig;
use iCoordinator\Config\Route\MetaFieldsRouteConfig;
use iCoordinator\Config\Route\PermissionsRouteConfig;
use iCoordinator\Config\Route\PortalsRouteConfig;
use iCoordinator\Config\Route\SearchRouteConfig;
use iCoordinator\Config\Route\SharedLinkRouteConfig;
use iCoordinator\Config\Route\SignUpRouteConfig;
use iCoordinator\Config\Route\SmartFoldersRouteConfig;
use iCoordinator\Config\Route\StateRouteConfig;
use iCoordinator\Config\Route\UsersRouteConfig;
use iCoordinator\Config\Route\WorkspacesRouteConfig;

class RoutesConfig extends AbstractConfig
{
    public function __construct()
    {
        $this->add(new AuthRouteConfig())
            ->add(new EventsRouteConfig())
            ->add(new FilesRouteConfig())
            ->add(new FoldersRouteConfig())
            ->add(new GroupsRouteConfig())
            ->add(new HistoryEventsRouteConfig())
            ->add(new InboundEmailsRouteConfig())
            ->add(new MetaFieldsRouteConfig())
            ->add(new PermissionsRouteConfig())
            ->add(new PortalsRouteConfig())
            ->add(new SharedLinkRouteConfig())
            ->add(new SmartFoldersRouteConfig())
            ->add(new UsersRouteConfig())
            ->add(new WorkspacesRouteConfig())
            ->add(new SearchRouteConfig())
            ->add(new StateRouteConfig())
            ->add(new DefaultRouteConfig())
            ->add(new SignUpRouteConfig())
            ->add(new ChargifyRouteConfig())
            ->add(new FDVRouteConfig());
    }
}
