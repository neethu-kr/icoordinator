<?php

namespace iCoordinator\File\Storage;

class FileSystem extends \Upload\Storage\FileSystem implements StorageInterface
{
    /**
     * Factory method that returns new instance of \FileInfoInterface
     * @var callable
     */
    protected static $factory;
    protected $streamFilter = null;
    protected $streamFilterOptions = array();

    private function deleteDirectory($dir)
    {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }
        return rmdir($dir);
    }
    /**
     * @param array $connectionParams
     * @throws Exception
     */
    public function __construct(array $connectionParams)
    {
        $this->directory = rtrim($connectionParams['path'], '/') . DIRECTORY_SEPARATOR;
    }

    public static function setFactory($callable)
    {
        if (is_object($callable) === false || method_exists($callable, '__invoke') === false) {
            throw new \InvalidArgumentException('Callback is not a Closure or invokable object.');
        }

        static::$factory = $callable;
    }


    /**
     * Factory
     *
     * @param  string                    $directory Relative or absolute path to upload directory
     * @param  bool                      $overwrite Should this overwrite existing files?
     * @throws \RuntimeException
     * @return FileSystem
     */
    public static function createFromFactory($directory, $overwrite = false)
    {
        if (isset(static::$factory) === true) {
            $result = call_user_func_array(static::$factory, array($directory, $overwrite));

            if ($result instanceof FileSystem === false) {
                throw new \RuntimeException(
                    'FileInfo factory must return instance of \iCoordinator\Upload\Storage\FileSystem.'
                );
            }

            return $result;
        }

        return new static($directory, $overwrite);
    }

    public function upload(\Upload\FileInfoInterface $fileInfo)
    {
        throw new Exception("Use iCoordinator\\File\\Uploader instead", $fileInfo);
    }
    public function download($filePath, $localFile = false, $offset = 0, $length = -1)
    {
        $destinationFile = $this->directory  . $filePath;

        return copy($destinationFile, $localFile);
    }

    public function rename($oldName, $newName)
    {
        $from = $this->directory . $oldName;
        $to = $this->directory . $newName;
        return rename($from, $to);
    }

    public function copy($originalFile, $copyFile)
    {
        $sourceFile = $originalFile->getVersion()->getStoragePath();
        $targetFile = $copyFile->getVersion()->getStoragePath();
        if ($originalFile->getId() == $copyFile->getId()) {
            throw new \Exception('source and destination file names should be different');
        }

        $source = $this->directory . $sourceFile;
        $target = $this->directory . $targetFile;
        copy($source, $target);
    }

    public function clear()
    {

        $files = scandir($this->directory);
        if ($files) {
            foreach ($files as $file) {
                if ($file != '.' && $file != '..' && is_file($file)) {
                    $this->deleteDirectory($this->directory . $file);
                    //unlink($this->directory . $file);
                }
            }
        }
    }

    public function delete($fileName)
    {
        $destinationFile = $this->directory . $fileName;

        return unlink($destinationFile);
    }

    public function fopen($filename, $mode)
    {
        $filePath = $this->getStreamUrl($filename);
        $dirPath = dirname($filePath);
        if (!file_exists($dirPath)) {
            system("mkdir -p " . $dirPath);
        }
        $stream = fopen($filePath, $mode);
        if ($this->streamFilter) {
            stream_filter_append($stream, $this->streamFilter, STREAM_FILTER_ALL, $this->streamFilterOptions);
        }

        return $stream;
    }

    public function getStreamUrl($fileName)
    {
        return $this->directory . $fileName;
    }

    public function setStreamFilter($filterName, $options)
    {
        $this->streamFilter = $filterName;
        $this->streamFilterOptions = $options;
    }
}
