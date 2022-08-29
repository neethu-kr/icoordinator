<?php

namespace iCoordinator\Config;

use Slim\App;
use Slim\Container;
use Slim\Http\Request;

class DiConfig extends AbstractConfig
{
    public function configure(App $app)
    {
        /** @var Container $c */
        $c = $app->getContainer();

        // Add media type parser to request
        $c->extend('request', function (Request $request, $c) {
            $request->registerMediaTypeParser('application/json', function ($input) {
                return json_decode($input, true);
            });
            return $request;
        });
    }
}
