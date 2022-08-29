<?php

namespace iCoordinator\Entity\EmailNotification;

use iCoordinator\Entity\EmailNotification;
use iCoordinator\Entity\SharedLink;

class SharedLinkEmailNotification extends EmailNotification
{
    /**
     * @var SharedLink
     */
    private $shared_link;

    /**
     * @var string
     */
    private $message = '';

    /**
     * @return SharedLink
     */
    public function getSharedLink()
    {
        return $this->shared_link;
    }

    /**
     * @param $shared_link
     * @return $this
     */
    public function setSharedLink($shared_link)
    {
        $this->shared_link = $shared_link;
        return $this;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param $message
     * @return $this
     */
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    public function jsonSerialize()
    {
        $result = parent::jsonSerialize();

        return array_merge($result, array(
           'entity_type' => 'shared_link_email_notification'
        ));
    }
}
