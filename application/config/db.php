<?php

$url = getenv('DATABASE_URL');

if ($url) {
    return array(
        'db' => array(
            'database_url' => $url,
            'charset' => 'utf8'
        )
    );
} else {
    return array(
        'db' => array(
            'driver' => getenv('DB_PDO_DRIVER'),
            'host' => (getenv('DB_HOST')) ? getenv('DB_HOST') : 'localhost',
            'dbname' => getenv('DB_NAME'),
            'user' => getenv('DB_USER'),
            'password' => (getenv('DB_PASSWORD')) ? getenv('DB_PASSWORD') : "",
            'port' => (getenv('DB_PORT')) ? getenv('DB_PORT') : '3306',
            'charset' => 'utf8'
        )
    );
}
