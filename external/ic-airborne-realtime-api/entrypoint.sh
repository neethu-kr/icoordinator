#!/usr/bin/env bash
/app/bin/doctrine migrations:migrate
/app/bin/doctrine orm:generate:proxies
service nginx start
php-fpm