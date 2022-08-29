<?php

namespace iCoordinator\Config;

use iCoordinator\OAuth2\Storage\Doctrine;
use iCoordinator\Permissions\Acl;
use iCoordinator\Permissions\Resource\RouteResource;
use iCoordinator\Permissions\Role\GuestRole;
use OAuth2\Server;
use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use Laminas\Authentication\AuthenticationService;
use Laminas\Authentication\Storage\NonPersistent;

class AccessConfig extends AbstractConfig
{
    public function configure(App $app)
    {
        $c = $app->getContainer();
        $c['OAuthServer'] = function (ContainerInterface $c) {
            $accessLifetime = getenv('ACCESS_LIFETIME') ? getenv('ACCESS_LIFETIME') : 3600;
            $storage = new Doctrine($c['entityManager']);
            $server = new Server($storage, array(
                'enforce_state' => false,
                'access_lifetime' => $accessLifetime,
                'always_issue_new_refresh_token' => true,
                'unset_refresh_token_after_use' => true
            ));

            return $server;
        };

        $c['auth'] = function () {
            $auth = new AuthenticationService();
            $auth->setStorage(new NonPersistent());
            return $auth;
        };

        $c['acl'] = function ($c) {
            return new Acl($c);
        };

        /**
         * Access check middleware
         */
        $app->add(function (Request $request, Response $response, $next) use ($c) {
            if ($request->getAttribute('route')) {
                $routeResource = new RouteResource($request->getAttribute('route'));

                /** @var AuthenticationService $auth */
                $auth = $c->get('auth');

                if (!$auth->hasIdentity()) {
                    /** @var Acl $acl */
                    $acl = $c->get('acl');
                    if (!$acl->isAllowed(GuestRole::ROLE_ID, $routeResource)) {
                        $response = $response->withStatus(401);
                        return $response;
                    }
                }
            }

            return $next($request, $response);
        });

        /**
         * Authorization middleware
         */
        $app->add(function (Request $request, Response $response, $next) use ($c) {
            /** @var AuthenticationService $auth */
            $auth = $c->get('auth');

            /** @var Server $server */
            $server = $c->get('OAuthServer');

            /** @var \OAuth2\Request */
            $oReq = \OAuth2\Request::createFromGlobals();

            if ($server->verifyResourceRequest($oReq)) {
                $token = $server->getAccessTokenData($oReq, null);
                if (isset($token['user_id'])) {
                    $auth->getStorage()->write($token['user_id']);
                }
            }

            return $next($request, $response);
        });
    }
}
