<?php

namespace iCoordinator\File;

use iCoordinator\Entity\FileVersion;
use iCoordinator\File\Storage\StorageInterface;
use Upload\FileInfo;

class Uploader extends \Upload\File
{
    const CHUNK_SIZE = 1048576;

    /**
     * @var StorageInterface
     */
    protected $storage;

    /**
     * @var FileVersion
     */
    protected $fileVersion;

    /**
     * @var Encryptor|null
     */
    protected $encryptor = null;

    private function microTimeFloat()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }
    /**
     * @param StorageInterface $storage
     * @param array $options
     */
    public function __construct(StorageInterface $storage, $options = array())
    {
        if (!empty($_FILES)) {
            if (count($_FILES) > 1) {
                throw new \InvalidArgumentException('Only one file can be uploaded at a time');
            }

            reset($_FILES);
            parent::__construct(key($_FILES), $storage);

            if (count($this->objects) > 1) {
                throw new \InvalidArgumentException('Only one file can be uploaded at a time');
            }
        } else {
            $this->storage = $storage;
        }

        if (isset($options['encryptor'])) {
            $this->setEncryptor($options['encryptor']);
        }
    }

    public function setEncryptor(Encryptor $encryptor)
    {
        $this->encryptor = $encryptor;
    }

    /**
     * @return FileVersion
     */
    public function getFileVersion()
    {
        return $this->fileVersion;
    }

    /**
     * @param FileVersion $fileVersion
     */
    public function setFileVersion(FileVersion $fileVersion)
    {
        $this->fileVersion = $fileVersion;
    }

    /**
     * @return FileInfo|null
     */
    public function getFileInfo($index = 0)
    {
        if ($this->count()) {
            return $this->offsetGet($index);
        }

        return null;
    }

    /**
     * @param FileInfo $fileInfo
     */
    public function setFileInfo(FileInfo $fileInfo, $index = 0)
    {
        $this->offsetSet($index, $fileInfo);
    }

    public function getFileSize()
    {
        if ($this->count()) {
            if ($this->count() > 1) {
                $size = 0;
                foreach ($this->objects as $object) {
                    $size += $object->getSize();
                }
                return $size;
            } else {
                return $this->offsetGet(0)->getSize();
            }
        }
    }
    /**
     * Is this collection valid and without errors?
     *
     * @return bool
     */
    public function isValid()
    {
        foreach ($this->objects as $fileInfo) {
            // Before validation callback
            $this->applyCallback('beforeValidation', $fileInfo);

            // Apply user validations
            foreach ($this->validations as $validation) {
                try {
                    $validation->validate($fileInfo);
                } catch (\Upload\Exception $e) {
                    $this->errors[] = sprintf(
                        '%s: %s',
                        $fileInfo->getNameWithExtension(),
                        $e->getMessage()
                    );
                }
            }

            // After validation callback
            $this->applyCallback('afterValidation', $fileInfo);
        }

        return empty($this->errors);
    }

    /**
     * @return bool
     */
    public function upload()
    {
        if (!$this->fileVersion) {
            throw new \Upload\Exception('File version is not set');
        }

        if ($this->isValid() === false) {
            throw new \Upload\Exception('File validation failed');
        }

        if ($this->encryptor !== null) {
            $this->encryptor->setIv($this->getFileVersion()->getIv());
            $this->encryptor->setStreamFilterForStorage($this->getStorage(), Encryptor::ENCRYPT_STREAM_FILTER);
        }

        if ($this->getFileSize() < 16) {
            $fileInfo = $this->offsetGet(0);
            $this->applyCallback('beforeUpload', $fileInfo);
            $fromStream = fopen($fileInfo->getPathname(), 'r');
            $fileSize = filesize($fileInfo->getPathname());
            $toStream = $this->storage->fopen($this->fileVersion->getStoragePath(), 'wb');
            $content = fread($fromStream, self::CHUNK_SIZE);
            $hash = hash('sha256', $content);
            for ($i = 0; $i <= (16 - $fileSize); $i++) {
                $content = $content . chr(0);
            }
            fwrite($toStream, $content);
            fclose($fromStream);
            fclose($toStream);
            unlink($fileInfo->getPathname());
            $this->applyCallback('afterUpload', $fileInfo);
        } else {
            $ctx = hash_init('sha256');
            $toStream = $this->storage->fopen($this->fileVersion->getStoragePath(), 'wb');
            foreach ($this->objects as $chunk) {
                $fromStream = fopen($chunk->getPathname(), 'r');
                while (!feof($fromStream)) {
                    $content = fread($fromStream, self::CHUNK_SIZE);
                    if (fwrite($toStream, $content) === false) {
                        // Error
                    } else {
                        hash_update($ctx, $content);
                    }
                }
                fclose($fromStream);
                unlink($chunk->getPathname());
            }
            fclose($toStream);
            $hash = hash_final($ctx, false);
        }
        /*$fileInfo = $this->offsetGet(0);

        $this->applyCallback('beforeUpload', $fileInfo);
        $fromStream = fopen($fileInfo->getPathname(), 'r');
        $fileSize = filesize($fileInfo->getPathname());
        stream_set_chunk_size($fromStream, self::CHUNK_SIZE);
        $toStream = $this->storage->fopen($this->fileVersion->getStoragePath(), 'wb');
        stream_set_chunk_size($toStream, self::CHUNK_SIZE);
        // Set maximum time upload script is allowed to run
        $maxExecutionTime = getenv('UPLOAD_MAX_EXECUTION_TIME') ? getenv('UPLOAD_MAX_EXECUTION_TIME') : 300;
        set_time_limit($maxExecutionTime);
        // Renice process priority to reduce server load and improve responsiveness
        $processId = getmypid();
        $tmp = shell_exec("renice 19 -p $processId");
        //TODO: implement chunked upload
        if ($fileSize < 16) {
            $content = fread($fromStream, self::CHUNK_SIZE);
            for ($i = 0; $i <= (16 - $fileSize); $i++) {
                $content = $content . chr(0);
            }
            fwrite($toStream, $content);
        } else {
            while (!feof($fromStream)) {
                fwrite($toStream, fread($fromStream, self::CHUNK_SIZE));
            }
        }
        fclose($fromStream);
        fclose($toStream);

        unlink($fileInfo->getPathname());

        $this->applyCallback('afterUpload', $fileInfo);*/

        return $hash;
    }

    /**
     * @return StorageInterface
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     *
     * @return bool
     */
    public function delete()
    {
        return $this->storage->delete($this->fileVersion->getStoragePath());
    }
}
