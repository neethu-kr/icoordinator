<?php

namespace iCoordinator\Entity;

use Laminas\Stdlib\JsonSerializable;

class EmailNotification implements JsonSerializable
{
    /**
     * @var array
     */
    private $emails;

    /**
     * @var array
     */
    private $failed_emails;

    /**
     * @var array
     */
    private $successful_emails;

    /**
     * @var array
     */
    private $failed_ids;


    public function __construct()
    {
        $this->emails = new \ArrayIterator();
        $this->failed_emails = new \ArrayIterator();
        $this->successful_emails = new \ArrayIterator();
        $this->failed_ids = new \ArrayIterator();
    }

    public function jsonSerialize()
    {
        return array(
            'emails' => $this->getEmails(),
            'successful_emails' => $this->getSuccessfulEmails(),
            'failed_emails' => $this->getFailedEmails(),
            'failed_ids' => $this->getFailedIds()
        );
    }

    /**
     * @return \ArrayIterator
     */
    public function getEmails()
    {
        return $this->emails;
    }

    /**
     * @param $emails
     * @return $this
     */
    public function setEmails($emails)
    {
        $this->emails = $emails;
        return $this;
    }

    /**
     * @return \ArrayIterator
     */
    public function getSuccessfulEmails()
    {
        return $this->successful_emails;
    }

    /**
     * @param $successful_emails
     * @return $this
     */
    public function setSuccessfulEmails($successful_emails)
    {
        $this->successful_emails = $successful_emails;
        return $this;
    }

    /**
     * @return \ArrayIterator
     */
    public function getFailedEmails()
    {
        return $this->failed_emails;
    }

    /**
     * @param $failed_emails
     * @return $this
     */
    public function setFailedEmails($failed_emails)
    {
        $this->failed_emails = $failed_emails;
        return $this;
    }

    /**
     * @return \ArrayIterator
     */
    public function getFailedIds()
    {
        return $this->failed_ids;
    }

    /**
     * @param $failed_ids
     * @return $this
     */
    public function setFailedIds($failed_ids)
    {
        $this->failed_ids = $failed_ids;
        return $this;
    }
}
