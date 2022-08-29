<?php

namespace iCoordinator\Entity;

use Carbon\Carbon;
use Datetime;
use Doctrine\Common\Collections\ArrayCollection;
use iCoordinator\Entity\DownloadZipToken\DownloadZipTokenFile;

/**
 * @Entity
 * @Table(name="download_zip_tokens", options={"collate"="utf8_general_ci"})
 * @HasLifecycleCallbacks
 */

class DownloadZipToken extends AbstractEntity
{
    const ENTITY_NAME = 'entity:DownloadZipToken';

    const RESOURCE_ID = 'download_zip_token';

    /**
     * @var ArrayCollection
     * @OneToMany(targetEntity="iCoordinator\Entity\DownloadZipToken\DownloadZipTokenFile",
     *     mappedBy="downloadZipToken", fetch="EXTRA_LAZY", cascade={"persist", "remove"})
     */
    protected $files;

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

    public function __construct()
    {
        $this->files = new ArrayCollection();
    }

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
        $filesArray = array();

        $downloadZipTokenFiles = $this->getFiles();
        foreach ($downloadZipTokenFiles as $downloadZipTokenFile) {
            $filesArray[] = $downloadZipTokenFile->getFile()->jsonSerialize(true);
        }
        return array(
            'entity_type' => self::RESOURCE_ID,
            'id' => $this->getId(),
            'files' => $filesArray,
            'expires_at' => ($this->getExpiresAt()) ? $this->getExpiresAt()->format(DateTime::ISO8601) : null,
            'created_by' => $this->getCreatedBy(),
            'token' => $this->getToken()
        );
    }

    /**
     * @return ArrayCollection
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * @param DownloadZipTokenFile $downloadZipTokenFile
     * @return $this
     */
    public function addFile($downloadZipTokenFile)
    {
        $this->files[] = $downloadZipTokenFile;
        $downloadZipTokenFile->setDownloadZipToken($this);
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
