<?php

namespace iCoordinator\File\Storage;

class StorageFactory
{
    /**
     * @param $storageConfig
     * @return StorageInterface
     * @throws \Exception
     */
    public static function createStorage($storageConfig)
    {
        switch (strtoupper($storageConfig['type'])) {
            case 'SFTP':
                $storage = new SFTP($storageConfig);
                break;
            case 'FILESYSTEM':
                //$storage = FileSystem::createFromFactory($storageConfig['path']);
                $storage = new FileSystem($storageConfig);
                break;
            case 'S3':
                $storage = new S3($storageConfig);
                break;
            default:
                throw new \Exception("File Storage is not defined");
        }

        return $storage;
    }
}
