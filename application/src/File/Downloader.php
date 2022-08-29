<?php

namespace iCoordinator\File;

use iCoordinator\Entity\FileVersion;
use iCoordinator\File\Downloader\Exception\RequestedRangeNotSatisfiableException;
use iCoordinator\File\Storage\StorageInterface;
use iCoordinator\Service\FileService;
use Slim\Http\Stream;

class Downloader
{
    const DEFAULT_MIME_TYPE = 'application/octet-stream';

    const CHUNK_SIZE = 1048576;
    /**
     * @var Storage\StorageInterface
     */
    protected $storage;

    /**
     * @var FileVersion
     */
    protected $fileVersion;

    protected $range = '';

    protected $fileSize = null;

    protected $seekStart = 0;

    protected $seekEnd = 0;

    protected $statusCode = 200;

    protected $stream = false;

    /**
     * @var Encryptor|null
     */
    protected $encryptor = null;

    /**
     * @var \ArrayObject
     */
    protected $headers;

    /**
     * @var FileService
     */
    protected $fileService;

    protected $openStyle;

    public function __construct(StorageInterface $storage, $fileService, $openStyle, $options = array())
    {
        $this->storage = $storage;
        $this->headers = new \ArrayObject();
        $this->fileService = $fileService;
        if (isset($options['stream'])) {
            $this->stream = (bool)$options['stream'];
        }
        if (isset($options['httpRange'])) {
            $this->setHttpRange($options['httpRange']);
        }
        if (isset($options['encryptor'])) {
            $this->setEncryptor($options['encryptor']);
        }
        $this->openStyle = $openStyle;
    }

    public function setEncryptor(Encryptor $encryptor)
    {
        $this->encryptor = $encryptor;
    }

    public function setHttpRange($httpRange)
    {
        if (empty($httpRange)) {
            return $this;
        }
        list($size_unit, $range_orig) = explode('=', $httpRange, 2);
        if ($size_unit == 'bytes') {
            //multiple ranges could be specified at the same time, but for simplicity only serve the first range
            //http://tools.ietf.org/id/draft-ietf-http-range-retrieval-00.txt
            list($this->range) = explode(',', $range_orig, 2);
            return $this;
        } else {
            throw new RequestedRangeNotSatisfiableException();
        }
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function getStream()
    {
        // Set maximum time dowmload script is allowed to run
        $maxExecutionTime = getenv('DOWNLOAD_MAX_EXECUTION_TIME') ? getenv('DOWNLOAD_MAX_EXECUTION_TIME') : 600;
        set_time_limit($maxExecutionTime);

        //adding support to old versions
        if (!$this->getFileVersion()->getIv()) {
            $tmpFileName = tempnam(sys_get_temp_dir(), 'downloading_');

            $this->storage->download($this->getFileVersion()->getStoragePath(), $tmpFileName);
            if ($this->encryptor) {
                $this->encryptor->decrypt($tmpFileName);
            }
            $resource = fopen($tmpFileName, 'rb');
            stream_set_chunk_size($resource, self::CHUNK_SIZE);
            return new Stream($resource);
        } else {
            if ($this->encryptor) {
                $this->encryptor->setIv($this->getFileVersion()->getIv());
                $this->encryptor->setStreamFilterForStorage($this->storage, Encryptor::DECRYPT_STREAM_FILTER);
            }
            $resource = $this->storage->fopen($this->getFileVersion()->getStoragePath(), 'rb');
            if ($resource) {
                stream_set_chunk_size($resource, self::CHUNK_SIZE);
                return new Stream($resource);
            } else {
                return null;
            }
        }
    }

    public function getChunkSize()
    {
        return self::CHUNK_SIZE;
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

        $this->setDefaultHeaders($fileVersion);

        $this->fileSize = $fileVersion->getSize();


        //figure out download piece from range (if set)
        $rangeParts = explode('-', $this->range, 2);
        if (count($rangeParts) == 2) {
            list($this->seekStart, $this->seekEnd) = $rangeParts;
        }

        //set start and end based on range (if set), else set defaults
        //also check for invalid ranges.
        $this->seekEnd = (empty($this->seekEnd)) ? ($this->fileSize - 1) :
            min(abs(intval($this->seekEnd)), ($this->fileSize - 1));
        $this->seekStart = (empty($this->seekStart) || $this->seekEnd < abs(intval($this->seekStart))) ? 0 :
            max(abs(intval($this->seekStart)), 0);

        $this->setContentHeaders($this->fileSize, $this->seekStart, $this->seekEnd);
    }

    protected function setDefaultHeaders(FileVersion $fileVersion)
    {
        $this->headers->offsetSet('Pragma', 'public');
        $this->headers->offsetSet('Expires', '-1');
        $this->headers->offsetSet('Cache-Control', 'public, must-revalidate, post-check=0, pre-check=0');
        $this->headers->offsetSet('Accept-Ranges', 'bytes');

        if ($this->stream) {
            $this->headers->offsetSet('Content-Disposition', 'inline');
            $this->headers->offsetSet('Content-Transfer-Encoding', 'binary');
        } else {
            $file = $fileVersion->getFile();
            if ($fileVersion != $file->getVersion()) {
                $paginator = $this->fileService->getFileVersions($file->getId(), 10000); // Default limit is 100
                $versions = $paginator->getIterator()->getArrayCopy();
                $key = array_search($fileVersion, $versions);
                if ($key !== false) {
                    $this->headers->offsetSet(
                        'Content-Disposition',
                        $this->openStyle.'; filename="' . 'V' . ($key + 1) . '_' .$file->getName() . '"'
                    );
                } else {
                    $this->headers->offsetSet(
                        'Content-Disposition',
                        $this->openStyle.'; filename="' . $file->getName() . '"'
                    );
                }
            } else {
                $this->headers->offsetSet(
                    'Content-Disposition',
                    $this->openStyle.'; filename="' . $file->getName() . '"'
                );
            }
        }

        $mimeType = $fileVersion->getFile()->getMimeType();
        if (!$mimeType) {
            $mimeType = self::DEFAULT_MIME_TYPE;
        }
        $this->headers->offsetSet('Content-Type', $mimeType);
    }

    protected function setContentHeaders($fileSize, $seekStart, $seekEnd)
    {
        //Only send partial content header if downloading a piece of the file (IE workaround)
        if ($seekStart > 0 || $seekEnd < ($fileSize - 1)) {
            $this->statusCode = 206;
            $this->headers->offsetSet('Content-Range', 'bytes ' . $seekStart . '-' . $seekEnd . '/' . $fileSize);
            $this->headers->offsetSet('Content-Length', ($seekEnd - $seekStart + 1));
        } else {
            $this->headers->offsetSet('Content-Length', $fileSize);
        }
    }

    private function flush($stream, $tmpFileName = null)
    {
        fclose($stream);
        if ($tmpFileName) {
            unlink($tmpFileName);
        }
    }
}
