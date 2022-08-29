<?php

namespace iCoordinator\Permissions;

use iCoordinator\Config\Route\AuthRouteConfig;
use iCoordinator\Config\Route\ChargifyRouteConfig;
use iCoordinator\Config\Route\DefaultRouteConfig;
use iCoordinator\Config\Route\FilesRouteConfig;
use iCoordinator\Config\Route\FoldersRouteConfig;
use iCoordinator\Config\Route\InboundEmailsRouteConfig;
use iCoordinator\Config\Route\SharedLinkRouteConfig;
use iCoordinator\Config\Route\SignUpRouteConfig;
use iCoordinator\Config\Route\UsersRouteConfig;
use iCoordinator\Entity\Acl\AclPermission;
use iCoordinator\Entity\File;
use iCoordinator\Entity\Group;
use iCoordinator\Entity\GroupMembership;
use iCoordinator\Entity\MetaField;
use iCoordinator\Entity\MetaFieldCriterion;
use iCoordinator\Entity\MetaFieldValue;
use iCoordinator\Entity\Portal;
use iCoordinator\Entity\SharedLink;
use iCoordinator\Entity\User;
use iCoordinator\Entity\Workspace;
use iCoordinator\Permissions\Assertion\FileAssertion;
use iCoordinator\Permissions\Assertion\GroupAssertion;
use iCoordinator\Permissions\Assertion\GroupMembershipAssertion;
use iCoordinator\Permissions\Assertion\MetaFieldAssertion;
use iCoordinator\Permissions\Assertion\MetaFieldCriterionAssertion;
use iCoordinator\Permissions\Assertion\MetaFieldValueAssertion;
use iCoordinator\Permissions\Assertion\PermissionAssertion;
use iCoordinator\Permissions\Assertion\PortalAssertion;
use iCoordinator\Permissions\Assertion\SystemAssertion;
use iCoordinator\Permissions\Assertion\UserAssertion;
use iCoordinator\Permissions\Assertion\WorkspaceAssertion;
use iCoordinator\Permissions\Resource\RouteResource;
use iCoordinator\Permissions\Resource\SystemResource;
use iCoordinator\Permissions\Role\GroupRole;
use iCoordinator\Permissions\Role\GuestRole;
use iCoordinator\Permissions\Role\UserRole;
use iCoordinator\Service\PermissionService;
use Laminas\Permissions\Acl\Acl as ZendAcl;
use Psr\Container\ContainerInterface;
use Slim\Router;

class Acl extends ZendAcl
{
    /**
     * @var PermissionService
     */
    private $permissionManager;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $c)
    {
        $this->permissionManager = $c->get('PermissionService');
        $this->container = $c;

        //adding roles
        $this->addRole(GuestRole::ROLE_ID)
            ->addRole(UserRole::ROLE_ID)
            ->addRole(GroupRole::ROLE_ID);

        /** @var Router $router */
        $router = $c->get('router');

        //adding resources
        foreach ($router->getRoutes() as $route) {
            $this->addResource(new RouteResource($route));
        }
        $this->addResource(SystemResource::RESOURCE_ID)
            ->addResource(Portal::RESOURCE_ID)
            ->addResource(Workspace::RESOURCE_ID)
            ->addResource(File::RESOURCE_ID)
            ->addResource(User::RESOURCE_ID)
            ->addResource(MetaField::RESOURCE_ID)
            ->addResource(MetaFieldValue::RESOURCE_ID)
            ->addResource(MetaFieldCriterion::RESOURCE_ID)
            ->addResource(SharedLink::RESOURCE_ID)
            ->addResource(Group::RESOURCE_ID)
            ->addResource(GroupMembership::RESOURCE_ID)
            ->addResource(AclPermission::RESOURCE_ID);

        //RouteResource rules
        $this->allow(
            GuestRole::ROLE_ID,
            new RouteResource($router->getNamedRoute(UsersRouteConfig::ROUTE_USER_RESET_PASSWORD))
        );
        $this->allow(
            GuestRole::ROLE_ID,
            new RouteResource($router->getNamedRoute(AuthRouteConfig::ROUTE_AUTH_AUTHORIZE))
        );
        $this->allow(
            GuestRole::ROLE_ID,
            new RouteResource($router->getNamedRoute(AuthRouteConfig::ROUTE_AUTH_TOKEN))
        );
        $this->allow(
            GuestRole::ROLE_ID,
            array(
                new RouteResource($router->getNamedRoute(DefaultRouteConfig::ROUTE_OPTIONS)),
                new RouteResource($router->getNamedRoute(DefaultRouteConfig::EXAMPLE_ERROR)),
                new RouteResource($router->getNamedRoute(DefaultRouteConfig::PING))
            )
        );
        $this->allow(
            GuestRole::ROLE_ID,
            array(
                new RouteResource($router->getNamedRoute(InboundEmailsRouteConfig::ROUTE_INBOUND_EMAIL_PROCESS)),
                new RouteResource($router->getNamedRoute(InboundEmailsRouteConfig::ROUTE_INBOUND_EMAIL_PING))
            )
        );
        $this->allow(
            GuestRole::ROLE_ID,
            array(
                new RouteResource($router->getNamedRoute(SharedLinkRouteConfig::ROUTE_SHARED_LINK_GET)),
                new RouteResource($router->getNamedRoute(FilesRouteConfig::ROUTE_FILE_GET)),
                new RouteResource($router->getNamedRoute(FilesRouteConfig::ROUTE_FILE_GET_CONTENT)),
                new RouteResource($router->getNamedRoute(FilesRouteConfig::ROUTE_FILE_GET_CONTENT_WITH_TOKEN)),
                new RouteResource($router->getNamedRoute(FoldersRouteConfig::ROUTE_FOLDER_GET)),
                new RouteResource($router->getNamedRoute(FoldersRouteConfig::ROUTE_FOLDER_CHILDREN_GET))
            )
        );
        $this->allow(
            GuestRole::ROLE_ID,
            array(
                new RouteResource($router->getNamedRoute(SignUpRouteConfig::ROUTE_SIGN_UP_CHECK_EMAIL)),
                new RouteResource($router->getNamedRoute(SignUpRouteConfig::ROUTE_SIGN_UP_SECURE_FIELDS_GET)),
                new RouteResource($router->getNamedRoute(SignUpRouteConfig::ROUTE_SIGN_UP)),
                new RouteResource($router->getNamedRoute(SignUpRouteConfig::ROUTE_SIGN_UP_CONFIRM_EMAIL)),
                new RouteResource($router->getNamedRoute(SignUpRouteConfig::ROUTE_SIGN_UP_ACCEPT_INVITATION))
            )
        );
        $this->allow(
            GuestRole::ROLE_ID,
            array(
                new RouteResource($router->getNamedRoute(ChargifyRouteConfig::ROUTE_CHARGIFY_WEBHOOK)),
            )
        );

        //System resource rules
        $this->allow(
            UserRole::ROLE_ID,
            SystemResource::RESOURCE_ID,
            null,
            new SystemAssertion()
        );

        //Portal resource rules
        $this->allow(
            [UserRole::ROLE_ID, GroupRole::ROLE_ID],
            Portal::RESOURCE_ID,
            null,
            new PortalAssertion()
        );

        //Workspace resource rules
        $this->allow(
            [UserRole::ROLE_ID, GroupRole::ROLE_ID],
            Workspace::RESOURCE_ID,
            null,
            new WorkspaceAssertion()
        );

        //File resource rules
        $this->allow(
            [GuestRole::ROLE_ID, UserRole::ROLE_ID, GroupRole::ROLE_ID],
            File::RESOURCE_ID,
            null,
            new FileAssertion()
        );

        //AclPermission resource rules
        $this->allow(
            UserRole::ROLE_ID,
            AclPermission::RESOURCE_ID,
            null,
            new PermissionAssertion()
        );

        //User resource rules
        $this->allow(
            UserRole::ROLE_ID,
            User::RESOURCE_ID,
            null,
            new UserAssertion()
        );

        //MetaField resource rules
        $this->allow(
            UserRole::ROLE_ID,
            MetaField::RESOURCE_ID,
            null,
            new MetaFieldAssertion()
        );

        //MetaFieldValue resource rules
        $this->allow(
            UserRole::ROLE_ID,
            MetaFieldValue::RESOURCE_ID,
            null,
            new MetaFieldValueAssertion()
        );

        //MetaFieldValue resource rules
        $this->allow(
            UserRole::ROLE_ID,
            MetaFieldCriterion::RESOURCE_ID,
            null,
            new MetaFieldCriterionAssertion()
        );

        //SharedLink resource rules
        $this->allow(
            array(UserRole::ROLE_ID, GuestRole::ROLE_ID),
            SharedLink::RESOURCE_ID,
            null,
            new FileAssertion()
        );

        //Group resource rules
        $this->allow(
            UserRole::ROLE_ID,
            Group::RESOURCE_ID,
            null,
            new GroupAssertion()
        );

        //GroupMembership resource rules
        $this->allow(
            UserRole::ROLE_ID,
            GroupMembership::RESOURCE_ID,
            null,
            new GroupMembershipAssertion()
        );
    }

    public function clearCache()
    {
        $this->getPermissionManager()->clearCache();
    }

    /**
     * @return PermissionService
     */
    public function getPermissionManager()
    {
        return $this->permissionManager;
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }
}
