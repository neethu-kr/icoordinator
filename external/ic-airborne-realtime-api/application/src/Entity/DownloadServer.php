<?php

namespace iCoordinator\Entity;

use Laminas\Stdlib\JsonSerializable;

class DownloadServer implements JsonSerializable
{
    /**
     * @var string
     */
    private $url;

    /**
     * @var integer
     */
    private $ttl;

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


    public function jsonSerialize()
    {
        return array(
            'entity_type' => 'download_server',
            'url' => $this->getUrl(),
            'ttl' => $this->getTtl()
        );
    }
}
