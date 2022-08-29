<?php

namespace iCoordinator\Entity;

use Carbon\Carbon;
use Datetime;

/**
 * @Entity
 * @Table(name="download_tokens", options={"collate"="utf8_general_ci"})
 * @HasLifecycleCallbacks
 */

class DownloadToken extends AbstractEntity
{
    const ENTITY_NAME = 'entity:DownloadToken';

    const RESOURCE_ID = 'download_token';

    /**
     * @var File
     * @ManyToOne(targetEntity="File")
     * @JoinColumn(name="file_id", referencedColumnName="id")
     */
    protected $file;

    /**
     * @var User
     * @ManyToOne(targetEntity="User")
     * @JoinColumn(name="created_by", referencedColumnName="id", nullable=true)
     */
    protected $created_by = null;

    /**
     * @var Carbon
     * @Column(type="datetime")
     */
    protected $expires_at;

    /**
     * @var string
     * @Column(type="string", unique=true, length=32, nullable=false)
     */
    protected $token;

    public static function getEntityName()
    {
        return self::ENTITY_NAME;
    }

    public function getResourceId()
    {
        return self::RESOURCE_ID;
    }

    public function jsonSerialize()
    {
        return array(
            'entity_type' => self::RESOURCE_ID,
            'id' => $this->getId(),
            'file' => $this->getFile()->jsonSerialize(true),
            'expires_at' => ($this->getExpiresAt()) ? $this->getExpiresAt()->format(DateTime::ISO8601) : null,
            'created_by' => $this->getCreatedBy(),
            'token' => $this->getToken()
        );
    }

    /**
     * @return File
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param $file
     * @return $this
     */
    public function setFile($file)
    {
        $this->file = $file;
        return $this;
    }

    /**
     * @return Carbon
     */
    public function getExpiresAt()
    {
        return $this->expires_at;
    }

    /**
     * @param Carbon $expires_at
     * @return $this
     */
    public function setExpiresAt($expires_at)
    {
        $this->expires_at = $expires_at;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCreatedBy()
    {
        return $this->created_by;
    }

    /**
     * @param mixed $created_by
     * @return $this
     */
    public function setCreatedBy($created_by)
    {
        $this->created_by = $created_by;
        return $this;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param $token
     * @return $this
     */
    public function setToken($token)
    {
        $this->token = $token;
        return $this;
    }
}
