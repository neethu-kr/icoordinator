<?php

namespace iCoordinator\Config;

use Predis\Client;
use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Container;

class RedisConfig extends AbstractConfig
{
    public function configure(App $app)
    {
        /** @var Container $c */
        $c = $app->getContainer();

        $c['redis'] = function (ContainerInterface $c) {
            $redisSettings = $c->get('settings')['redis'];
            return new Client($redisSettings);
        };
    }
}
