<?php

namespace iCoordinator\Entity\HistoryEvent;

use iCoordinator\Entity\HistoryEvent;
use iCoordinator\Entity\Invitation;

/**
 * @Entity
 */
class InvitationHistoryEvent extends HistoryEvent
{
    const ENTITY_NAME = 'entity:HistoryEvent\InvitationHistoryEvent';

    const TYPE_PORTAL_INVITATION_SENT = 'INVITATION_USER_PORTAL_SENT';
    const TYPE_PORTAL_INVITATION_RESENT = 'INVITATION_USER_PORTAL_RESENT';
    const TYPE_PORTAL_INVITATION_DELETE = 'INVITATION_USER_PORTAL_DELETE';
    const TYPE_PORTAL_INVITATION_ACCEPTED = 'INVITATION_USER_PORTAL_ACCEPTED';

    /**
     * @ManyToOne(targetEntity="iCoordinator\Entity\Invitation")
     * @JoinColumn(name="source_id", referencedColumnName="id")
     **/
    protected $source;

    /**
     * @return array
     */
    public static function getEventTypes()
    {
        return array(
            self::TYPE_PORTAL_INVITATION_SENT,
            self::TYPE_PORTAL_INVITATION_RESENT,
            self::TYPE_PORTAL_INVITATION_DELETE,
            self::TYPE_PORTAL_INVITATION_ACCEPTED
        );
    }

    public static function getEntityName()
    {
        return self::ENTITY_NAME;
    }

    /**
     * @return \iCoordinator\Entity\Invitation
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param $source
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setSource($source)
    {
        if (!$source instanceof Invitation) {
            throw new \InvalidArgumentException(
                '$source should be instance of iCoordinator\\Entity\\Invitation'
            );
        }
        $this->source = $source;
        return $this;
    }
}
