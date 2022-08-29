<?php

namespace iCoordinator\Controller;

use Slim\Http\Request;
use Slim\Http\Response;

class DefaultController extends AbstractRestController
{

    public function optionsAction(Request $request, Response $response, $args)
    {
        $response = $response->withHeader('Access-Control-Allow-Methods', implode(',', array(
            'OPTIONS', 'HEAD', 'GET', 'POST', 'PUT', 'DELETE'
        )));

        $response = $response->withHeader('Access-Control-Allow-Headers', implode(', ', array(
                AbstractRestController::HEADER_SHARED_LINK_TOKEN,
                'Authorization',
                'Accept',
                'Range',
                'X-Requested-With',
                'Content-Type',
                'Content-Range',
                'Content-Disposition',
                'Content-Description',
                'Client-Version'
        )));

        $response = $response->withHeader('Access-Control-Max-Age', '10');

        return $response->withStatus(self::STATUS_OK);
    }

    public function exampleErrorAction(Request $request, Response $response, $args)
    {
        throw new \Exception('Example error');
    }

    public function pingAction(Request $request, Response $response, $args)
    {
        return $response->withStatus(self::STATUS_OK);
    }
}
