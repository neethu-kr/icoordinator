<?php

namespace iCoordinator\Config;

use Slim\App;

class DateTimeConfig extends AbstractConfig
{
    public function configure(App $app)
    {
        date_default_timezone_set('UTC');
    }
}
