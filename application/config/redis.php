<?php

$url = getenv('REDIS_URL');

if ($url) {
    if (str_starts_with($url, "rediss") || str_starts_with($url, "tls")) {
        $url = $url . "?ssl[verify_peer_name]=0&ssl[verify_peer]=0";
    }
    return [
        'redis' => $url
    ];
} else {
    return [
        'redis' => [
            'scheme' => 'tcp',
            'host' => getenv('REDIS_PORT_6379_TCP_ADDR') ?: getenv('REDIS_HOST'),
            'port' => getenv('REDIS_PORT_6379_TCP_PORT') ?: getenv('REDIS_PORT')
        ]
    ];
}
