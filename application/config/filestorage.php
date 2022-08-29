<?php

switch (getenv('FILE_STORAGE_TYPE')) {
    case 'S3':
        return array(
            'fileStorage' => array(
                'type' => 'S3',
                'bucket' => getenv('FILE_STORAGE_S3_BUCKET_NAME'),
                'access' => array(
                    'key'    => getenv('AWS_ACCESS_KEY_ID'),
                    'secret' => getenv('AWS_SECRET_ACCESS_KEY')
                ),
                'region' => getenv('S3_REGION')
            )/*,
            'uploadsStorage' => array(
                'type' => 'FILESYSTEM',
                'path' => '/tmp'
            )*/,
            'uploadsStorage' => array(
                'type' => 'S3',
                'bucket' => getenv('UPLOADS_STORAGE_S3_BUCKET_NAME'),
                'access' => array(
                    'key'    => getenv('AWS_ACCESS_KEY_ID'),
                    'secret' => getenv('AWS_SECRET_ACCESS_KEY')
                ),
                'region' => getenv('S3_REGION')
            ),
            'fileEncryptionKey' => getenv('FILE_STORAGE_AES_KEY')
        );
        break;
    case 'SFTP':
        return array(
            'fileStorage' => array(
                'type' => 'SFTP',
                'host' => getenv('SFTP_HOST'),
                'port' => getenv('SFTP_PORT'),
                'username' => getenv('SFTP_USERNAME'),
                'password' => getenv('SFTP_PASSWORD'),
                'path' => getenv('SFTP_PATH')
            ),
            'uploadsStorage' => array(
                'type' => 'FILESYSTEM',
                'path' => '/tmp'
            )/*,
            'uploadsStorage' => array(
                'type' => 'SFTP',
                'host' => getenv('SFTP_HOST'),
                'port' => getenv('SFTP_PORT'),
                'username' => getenv('SFTP_USERNAME'),
                'password' => getenv('SFTP_PASSWORD'),
                'path' => getenv('SFTP_UPLOADS_PATH')
            )*/,
            'fileEncryptionKey' => getenv('FILE_STORAGE_AES_KEY')
        );
        break;
    case 'FILESYSTEM':
        return array(
            'fileStorage' => array(
                'type' => 'FILESYSTEM',
                'path' => getenv('FILESYSTEM_PATH')
            ),
            'uploadsStorage' => array(
                'type' => 'FILESYSTEM',
                'path' => '/tmp'
            ),
            'fileEncryptionKey' => getenv('FILE_STORAGE_AES_KEY')
        );
    break;
    default:
        throw new \Exception('File storage config is not defined');
        break;
}
