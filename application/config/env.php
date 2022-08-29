<?php

return array(
    'DEVELOPMENT' => 1,
    'VIRTUAL_HOST' => 'docker.for.windows.localhost:8080',
    'WEB_BASE_URL' => 'http://dev.icoordinator.com',
    'REALTIME_SERVER_URL' => 'http://ic.local:8081',

    'BRAND' => 'icoordinator', // bimcontact, next
    'BRAND_NAME' => 'iCoordinator', // BIMContact, NEXT DocOnline
    'BRAND_IMAGE_URL' => 'https://apps.icoordinator.com/resources/images/logos/icoordinator/logo.svg',
    'COPYRIGHT_NAME' => 'Designtech Solutions AB', // BIMcontact AB, Designtech Solutions AB

    'DEFAULT_FROM_EMAIL' => 'noreply@icoordinator.com', // noreply@bimcontact.com, noreply@icoordinator.com

    'DB_PDO_DRIVER' => 'pdo_mysql',
    'DB_HOST' => 'db',
    'DB_NAME' => 'bimcontact',
    'DB_USER' => 'bimuser',
    'DB_PASSWORD' => 'test',
    'DB_PORT' => '3306',

    'FILE_STORAGE_TYPE' => 'FILESYSTEM',
    'FILE_STORAGE_AES_KEY' => '',
    'AWS_ACCESS_KEY_ID' => '',
    'AWS_SECRET_ACCESS_KEY' => '',
    'TOKEN_AES_KEY' => '',
    'FILE_STORAGE_S3_BUCKET_NAME' => '',
    'UPLOADS_STORAGE_S3_BUCKET_NAME' => '',
    'S3_REGION' => 'eu-west-1',

    'SFTP_HOST' => '',
    'SFTP_PORT' => '',
    'SFTP_USERNAME' => 'sftp',
    'SFTP_PASSWORD' => 'sftp',
    'SFTP_PATH' => '/data',
    'SFTP_UPLOADS_PATH' => '/data',

    'MANDRILL_API_KEY' => '8oCUcV3E5Px5S1-MJWCTkA',
    'INBOUND_EMAIL_HOST' => 'in.icoordinator.com',

    'CHARGIFY_IC_TEST_API_ID' => '0adf2790-4979-0133-3a34-069b181e07e0',
    'CHARGIFY_IC_TEST_API_SECRET' => '8HHB4uqoGLcS4MZt7cL1w75Wiy4v6N7C29Kl98ow8FU',
    'CHARGIFY_IC_TEST_API_PASSWORD' => 'un4OmI1RLmPpHJhu6DIbCdrOO9jbBDU1P1iP9bFNpmw',
    'CHARGIFY_IC_TEST_SHARED_KEY' => 'l81K6lwIPpsFbwO7s1eMTPbCIVDRqILnsmJyrOzoc',

    'CHARGIFY_HOSTNAME' => '',

    'REDIS_URL' => 'redis://redis:6379',
    'REDIS_HOST' => 'redis.bimcontact.com',
    'REDIS_PORT' => '6379',

    'UPLOAD_MAX_EXECUTION_TIME' => 300,
    'ACCESS_LIFETIME' => 86400,
    'SUPERADMIN' => '1'

    //'REDIS_HOST' => '192.168.99.100',
    //'REDIS_PORT' => '32770',
);
