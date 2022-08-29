<?php
namespace iCoordinator\Entity\DownloadZipToken;

use iCoordinator\Entity\AbstractEntity;
use iCoordinator\Entity\DownloadZipToken;

/**
 * @Entity
 * @Table(name="download_zip_token_files", options={"collate"="utf8_general_ci"})
 * @HasLifecycleCallbacks
 */
class DownloadZipTokenFile extends AbstractEntity
{
    const ENTITY_NAME = 'entity:DownloadZipToken\DownloadZipTokenFile';

    const RESOURCE_ID = 'downloadziptokenfile';

    /**
     * @var \iCoordinator\Entity\File
     * @ManyToOne(targetEntity="\iCoordinator\Entity\File")
     * @JoinColumn(name="file_id", referencedColumnName="id")
     */
    protected $file = null;

    /**
     * @var \iCoordinator\Entity\DownloadZipToken
     * @ManyToOne(targetEntity="\iCoordinator\Entity\DownloadZipToken", cascade={"persist"})
     * @JoinColumn(name="download_zip_token_id", referencedColumnName="id")
     */
    protected $downloadZipToken;

    /**
     * Implementing Zend ACL ResourceInterface
     * @return string
     */
    public function getResourceId()
    {
        return self::RESOURCE_ID;
    }

    public function jsonSerialize()
    {
        return array(
            'entity_type' => self::RESOURCE_ID,
            'id' => $this->getId(),
            'file' => $this->getFile(),
            'download_zip_token' => $this->getDownloadZipToken()
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
     * @param File $file
     * @return $this
     */
    public function setFile($file)
    {
        $this->file = $file;
        return $this;
    }

    /**
     * @return DownloadZipToken
     */
    public function getDownloadZipToken()
    {
        return $this->downloadZipToken;
    }

    /**
     * @param DownloadZipToken $downloadZipToken
     * @return $this
     */
    public function setDownloadZipToken($downloadZipToken)
    {
        $this->downloadZipToken = $downloadZipToken;
        return $this;
    }
}
