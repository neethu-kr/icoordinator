<?php

namespace iCoordinator\File\Storage;

use phpseclib\Net\SFTP\Stream;
use Upload\Exception;

class SFTP implements StorageInterface
{
    /**
     * Path to upload destination directory (with trailing slash)
     * @var string
     */
    protected $directory;

    /**
     * @var string
     */
    protected $host;
    /**
     * @var int
     */
    protected $port = 22;
    /**
     * @var string
     */
    protected $username;
    /**
     * @var string
     */
    protected $password;
    protected $streamFilter = null;
    protected $streamFilterOptions = array();
    /**
     * @var \phpseclib\Net\SFTP
     */
    private $sftp;

    /**
     * @param array $connectionParams
     * @throws Exception
     */
    public function __construct(array $connectionParams)
    {
        $sftp = new \phpseclib\Net\SFTP($connectionParams['host'], $connectionParams['port'], 20);
        try {
            $sftp->login($connectionParams['username'], $connectionParams['password']);
        } catch (\Exception $e) {
            throw new Exception("Connection to SFTP failed");
        }

        $this->sftp = $sftp;
        $this->directory = rtrim($connectionParams['path'], '/') . DIRECTORY_SEPARATOR;
        $this->host = $connectionParams['host'];
        $this->port = $connectionParams['port'];
        $this->username = $connectionParams['username'];
        $this->password = $connectionParams['password'];

        Stream::register('sftp');
    }

    public function upload(\Upload\FileInfoInterface $fileInfo)
    {
        throw new Exception("Use iCoordinator\\File\\Uploader instead", $fileInfo);
    }

    public function download($filePath, $localFile = false, $offset = 0, $length = -1)
    {
        $destinationFile = $this->directory  . $filePath;

        return $this->sftp->get($destinationFile, $localFile, $offset, $length);
    }

    public function rename($oldName, $newName)
    {
        $from = $this->directory . $oldName;
        $to = $this->directory . $newName;
        return $this->sftp->rename($from, $to);
    }

    public function copy($originalFile, $copyFile)
    {
        $sourceFile = $originalFile->getVersion()->getStoragePath();
        $targetFile = $copyFile->getVersion()->getStoragePath();
        if ($sourceFile == $targetFile) {
            throw new \Exception('source and destination file names should be different');
        }

        $source = $this->directory . $sourceFile;
        $target = $this->directory . $targetFile;
        $this->sftp->exec('cp -r ' . $source . ' ' . $target);
    }

    public function clear()
    {
        $files = $this->sftp->nlist($this->directory);
        if ($files) {
            foreach ($files as $file) {
                if ($file != '.' && $file != '..') {
                    $this->delete($file);
                }
            }
        }
    }

    public function delete($fileName)
    {
        $destinationFile = $this->directory . $fileName;

        return $this->sftp->delete($destinationFile, true);
    }

    public function fopen($filename, $mode)
    {
        $streamUrl = $this->getStreamUrl($filename);
        mkdir(dirname($streamUrl), 0777, true);
        $stream = fopen($streamUrl, $mode);
        if ($this->streamFilter) {
            stream_filter_append($stream, $this->streamFilter, STREAM_FILTER_ALL, $this->streamFilterOptions);
        }
        return $stream;
    }

    public function getStreamUrl($fileName)
    {
        return 'sftp://' . $this->username . ':' . $this->password .
        '@' . $this->host . ':' . $this->port . $this->directory . $fileName;
    }

    public function setStreamFilter($filterName, $options)
    {
        $this->streamFilter = $filterName;
        $this->streamFilterOptions = $options;
    }
}
