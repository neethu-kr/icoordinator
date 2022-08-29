<?php

namespace iCoordinator\Config;

use iCoordinator\Controller\AbstractRestController;
use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

class CORSConfig extends AbstractConfig
{
    public function configure(App $app)
    {
        $app->add(function (Request $request, Response $response, callable $next) {
            if (true) {
                $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
                if ($request->hasHeader('Origin')) {
                    $response = $response->withHeader('Access-Control-Allow-Origin', $request->getHeader('Origin'));
                } else {
                    $response = $response->withHeader('Access-Control-Allow-Origin', '*');
                }
                $response = $response->withHeader('Access-Control-Allow-Headers', implode(', ', [
                    'X-Requested-With',
                    'Content-Type',
                    'Content-Length',
                    'Authorization',
                    AbstractRestController::HEADER_SHARED_LINK_TOKEN
                ]));

                if ($request->isOptions() &&
                    (
                        $request->hasHeader('Access-Control-Request-Headers') ||
                        $request->hasHeader('Access-Control-Request-Method')
                    )
                ) {
                    return $response->withHeader('Access-Control-Allow-Methods', 'OPTIONS,GET,POST,PUT,DELETE');
                }

                return $next($request, $response);
            } else {
                return $response->withStatus(AbstractRestController::STATUS_METHOD_NOT_ALLOWED);
            }
        });
    }
}
