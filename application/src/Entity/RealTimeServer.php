<?php

namespace iCoordinator\Entity;

use Laminas\Stdlib\JsonSerializable;

class RealTimeServer implements JsonSerializable
{
    /**
     * @var string
     */
    private $url;

    /**
     * @var integer
     */
    private $ttl = 600;

    /**
     * @var int
     */
    private $max_retries = 10;

    /**
     * @var int
     */
    private $retry_timeout = 610;

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     * @return DownloadServer
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @return int
     */
    public function getTtl()
    {
        return $this->ttl;
    }

    /**
     * @param int $ttl
     * @return DownloadServer
     */
    public function setTtl($ttl)
    {
        $this->ttl = $ttl;
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxRetries()
    {
        return $this->max_retries;
    }

    /**
     * @param int $max_retries
     * @return RealTimeServer
     */
    public function setMaxRetries($max_retries)
    {
        $this->max_retries = $max_retries;
        return $this;
    }

    /**
     * @return int
     */
    public function getRetryTimeout()
    {
        return $this->retry_timeout;
    }

    /**
     * @param int $retry_timeout
     * @return RealTimeServer
     */
    public function setRetryTimeout($retry_timeout)
    {
        $this->retry_timeout = $retry_timeout;
        return $this;
    }

    public function jsonSerialize()
    {
        return array(
            'entity_type' => 'realtime_server',
            'url' => $this->getUrl(),
            'ttl' => $this->getTtl(),
            'max_retries' => $this->getMaxRetries(),
            'retry_timeout' => $this->getRetryTimeout()
        );
    }
}
