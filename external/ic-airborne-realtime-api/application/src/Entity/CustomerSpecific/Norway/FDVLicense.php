<?php

namespace iCoordinator\Entity\CustomerSpecific\Norway;

use iCoordinator\Entity\AbstractEntity;
use iCoordinator\Entity\Portal;

/**
 * @Entity
 * @Table(name="fdv_licenses", options={"collate"="utf8_general_ci"})
 * @HasLifecycleCallbacks
 */
class FDVLicense extends AbstractEntity
{
    const ENTITY_NAME = 'entity:CustomerSpecific\Norway\FDVLicense';

    const RESOURCE_ID = 'fdvlicense';

    /**
     * @var Portal
     * @ManyToOne(targetEntity="iCoordinator\Entity\Portal")
     * @JoinColumn(name="portal_id", referencedColumnName="id")
     */
    protected $portal;

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

    public function __construct()
    {
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
        return [
            'entity_type' => self::RESOURCE_ID,
            'id' => $this->getId(),
            'portal' => $this->getPortal()->jsonSerialize(true)
        ];
    }
}
