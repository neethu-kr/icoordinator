<?php

namespace iCoordinator\Entity;

use Carbon\Carbon;
use Datetime;

/**
 * @Entity
 * @Table(name="file_email_options", options={"collate"="utf8_general_ci"})
 * @HasLifecycleCallbacks
 */

class FileEmailOptions extends AbstractEntity
{
    const ENTITY_NAME = 'entity:FileEmailOptions';

    const RESOURCE_ID = 'file_email_options';

    /**
     * @var File
     * @ManyToOne(targetEntity="File", inversedBy="lock")
     * @JoinColumn(name="file_id", referencedColumnName="id")
     */
    protected $file = null;

    /**
     * @var User
     * @ManyToOne(targetEntity="User")
     * @JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    /**
     * @var bool
     * @Column(type="boolean")
     */
    protected $upload_notification = false;

    /**
     * @var bool
     * @Column(type="boolean")
     */
    protected $download_notification = false;

    /**
     * @var bool
     * @Column(type="boolean")
     */
    protected $delete_notification = false;

    /**
     * @var Carbon
     * @Column(type="datetime")
     */
    protected $created_at;

    /**
     * @var Carbon
     * @Column(type="datetime")
     */
    protected $modified_at;

    /**
     * @var bool
     */
    protected $inherited = false;

    public static function getEntityName()
    {
        return self::ENTITY_NAME;
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
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     * @return Event
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @PrePersist
     */
    public function updatedTimestamps()
    {
        $this->setModifiedAt(new Carbon());

        if ($this->getCreatedAt() == null) {
            $this->setCreatedAt(new Carbon());
        }
        return $this;
    }

    /**
     * @return \Carbon\Carbon
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * @param \Carbon\Carbon $created_at
     * @return $this
     */
    public function setCreatedAt($created_at)
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function getResourceId()
    {
        return self::RESOURCE_ID;
    }

    public function jsonSerialize()
    {
        return array(
            'entity_type' => self::RESOURCE_ID,
            'download_notification' => $this->isDownloadNotification(),
            'upload_notification' => $this->isUploadNotification(),
            'delete_notification' => $this->isDeleteNotification(),
            'inherited' => $this->isInherited(),
            'modified_at' => ($this->getModifiedAt()) ? $this->getModifiedAt()->format(DateTime::ISO8601) : null,
            'created_at' => ($this->getCreatedAt()) ? $this->getCreatedAt()->format(DateTime::ISO8601) : null
        );
    }

    /**
     * @return boolean
     */
    public function isDownloadNotification()
    {
        return $this->download_notification;
    }

    /**
     * @param $download_notification
     * @return $this
     */
    public function setDownloadNotification($download_notification)
    {
        $this->download_notification = $download_notification;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isUploadNotification()
    {
        return $this->upload_notification;
    }

    /**
     * @param $upload_notification
     * @return $this
     */
    public function setUploadNotification($upload_notification)
    {
        $this->upload_notification = $upload_notification;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isDeleteNotification()
    {
        return $this->delete_notification;
    }

    /**
     * @param $delete_notification
     * @return $this
     */
    public function setDeleteNotification($delete_notification)
    {
        $this->delete_notification = $delete_notification;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isInherited()
    {
        return $this->inherited;
    }

    /**
     * @param $inherited
     * @return $this
     */
    public function setInherited($inherited)
    {
        $this->inherited = $inherited;
        return $this;
    }

    /**
     * @return \Carbon\Carbon
     */
    public function getModifiedAt()
    {
        return $this->modified_at;
    }

    /**
     * @param \Carbon\Carbon $modified_at
     * @return $this
     */
    public function setModifiedAt($modified_at)
    {
        $this->modified_at = $modified_at;
        return $this;
    }
}
