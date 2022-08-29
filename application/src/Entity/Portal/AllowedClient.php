<?php

namespace iCoordinator\Entity\Portal;

use iCoordinator\Entity\AbstractEntity;

/**
 * @Entity
 * @Table(name="portal_allowed_clients", options={"collate"="utf8_general_ci"})
 * @HasLifecycleCallbacks
 */

class AllowedClient extends AbstractEntity
{
    const ENTITY_NAME = 'entity:Portal\AllowedClient';

    const RESOURCE_ID = 'allowed_client';

    /**
     * @var \iCoordinator\Entity\Portal
     *
     * @ManyToOne(targetEntity="\iCoordinator\Entity\Portal", inversedBy="allowed_clients")
     * @JoinColumn(name="portal_id", referencedColumnName="id", nullable=false)
     */
    protected $portal;

    /**
     * @var \iCoordinator\Entity\User
     * @ManyToOne(targetEntity="\iCoordinator\Entity\User")
     * @JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     **/
    protected $user;

    /**
     * @var string
     * @Column(type="string", nullable=true, length=100)
     */
    protected $uuid;

    /**
     * @var boolean
     * @Column(type="boolean")
     */
    protected $desktop;

    /**
     * @var boolean
     * @Column(type="boolean")
     */
    protected $mobile;


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
            'uuid' => $this->getUuid(),
            'user' => $this->getUser(),
            'portal' => $this->getPortal(),
            'desktop' => $this->getDesktop(),
            'mobile' => $this->getMobile()
        );
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param $user
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * @param $uuid
     * @return $this
     */
    public function setUuid($uuid)
    {
        $this->uuid = $uuid;
        return $this;
    }

    /**
     * @return Portal
     */
    public function getPortal()
    {
        return $this->portal;
    }

    /**
     * @param $portal
     * @return $this
     */
    public function setPortal($portal)
    {
        $this->portal = $portal;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getDesktop()
    {
        return $this->desktop;
    }

    /**
     * @param $desktop
     * @return $this
     */
    public function setDesktop($desktop)
    {
        $this->desktop = $desktop;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * @param $mobile
     * @return $this
     */
    public function setMobile($mobile)
    {
        $this->mobile = $mobile;
        return $this;
    }
}
