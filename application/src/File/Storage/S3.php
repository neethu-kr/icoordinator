<?php

namespace iCoordinator\File\Storage;

use Aws\S3\S3Client;
use Upload\Exception;

class S3 implements StorageInterface
{
    protected $streamFilter = null;
    protected $streamFilterOptions = array();
    /**
     * @var S3Client
     */
    private $s3Client;
    /**
     * @var string
     */
    private $bucket;

    /**
     * @param array $connectionParams
     * @throws Exception
     */
    public function __construct(array $connectionParams)
    {
        $this->s3Client = S3Client::factory(array(
            'key'    => $connectionParams['access']['key'],
            'secret' => $connectionParams['access']['secret'],
            'region' => $connectionParams['region'],
            'version' => '2006-03-01',
            'curl.options' => [
                'CURLOPT_TIMEOUT' => 0
            ]
        ));

        $this->bucket = $connectionParams['bucket'];

        $this->s3Client->registerStreamWrapper();
    }

    public function upload(\Upload\FileInfoInterface $fileInfo)
    {
        throw new Exception("Use iCoordinator\\File\\Uploader instead", $fileInfo);
    }

    public function download($filePath, $localFile = false, $offset = 0, $length = -1)
    {
        $result = $this->s3Client->getObject(array(
            'Bucket' => $this->bucket,
            'Key'    => $filePath,
            'SaveAs' => $localFile
        ));

        return $result;
    }

    public function rename($oldName, $newName)
    {
        $this->copy($oldName, $newName);
        $this->delete($oldName);

        return true;
    }

    public function copy($originalFile, $copyFile)
    {
        $sourceFile = $originalFile->getId();
        $targetFile = $copyFile->getId();
        if ($sourceFile == $targetFile) {
            throw new \Exception('source and destination file names should be different');
        }

        $iterator = $this->s3Client->getIterator('ListObjects', array(
            'Bucket' => $this->bucket,
            'Prefix' => $sourceFile
        ));

        foreach ($iterator as $object) {
            $this->s3Client->copyObject(array(
                'Bucket'     => $this->bucket,
                'Key'        => preg_replace(
                    '/^' . preg_quote($this->directory . $sourceFile, '/') . '/',
                    $targetFile,
                    $object['Key']
                ),
                'CopySource' => $this->bucket . '/' . $object['Key'],
            ));
        }
    }

    public function delete($fileName)
    {
        try {
            $result = $this->s3Client->deleteObject(array(
                'Bucket' => $this->bucket,
                'Key' => $fileName
            ));
        } catch (S3Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }
        return $result;
    }

    public function clear()
    {
        $keys = array();
        try {
            $results = $this->s3Client->getPaginator('ListObjects', [
                'Bucket' => $this->bucket
            ]);
            foreach ($results as $result) {
                if (isset($result['Contents'])) {
                    foreach ($result['Contents'] as $object) {
                        $keys[] = $object['Key'];
                    }
                }
            }
        } catch (S3Exception $e) {
            //echo $e->getMessage() . PHP_EOL;
        }

// 3. Delete the objects.
        if (count($keys)) {
            $this->s3Client->deleteObjects([
                'Bucket' => $this->bucket,
                'Delete' => [
                    'Objects' => array_map(function ($key) {
                        return ['Key' => $key];
                    }, $keys)
                ],
            ]);
        }
        //$this->s3Client->clearBucket($this->bucket);
    }

    public function fopen($filename, $mode)
    {
        $context = stream_context_create(array(
            's3' => array(
                'seekable' => true
            )
        ));
        $url = $this->getStreamUrl($filename);
        $stream = fopen($url, $mode, null, $context);
        if ($this->streamFilter) {
            stream_filter_append($stream, $this->streamFilter, STREAM_FILTER_ALL, $this->streamFilterOptions);
        }

        return $stream;
    }

    public function getStreamUrl($filePath)
    {
        return 's3://' . $this->bucket . DIRECTORY_SEPARATOR . $filePath;
    }

    public function setStreamFilter($filterName, $options)
    {
        $this->streamFilter = $filterName;
        $this->streamFilterOptions = $options;
    }
}
