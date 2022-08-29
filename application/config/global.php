<?php

return array(
    'api_base_url' => (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . getenv('VIRTUAL_HOST'),
    'web_base_url' => getenv('WEB_BASE_URL'),
    'token_aes_key' => getenv('TOKEN_AES_KEY'),
    'realtime_server_url' => getenv('REALTIME_SERVER_URL'),
    'mode' => getenv('APPLICATION_ENV'),
    'localization.default_locale' => 'en_US',
    'default_timezone' => 'UTC',
    'determineRouteBeforeAppMiddleware' => true,
    'migrations' => array(
        'name' => 'iCoordinator Migrations',
        'directory' => 'application/data/migrations',
        'tableName' => 'migrations',
        'namespace' => 'iCoordinator\Migration',
        #'filter_expression' => '/^(?!rbac_)/'
    )
);
