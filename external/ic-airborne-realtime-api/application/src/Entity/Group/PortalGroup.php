<?php

namespace iCoordinator\Entity\Group;

use iCoordinator\Entity\Group;
use iCoordinator\Entity\Portal;

/**
 * Class PortalGroup
 *
 * @Entity
 * @package iCoordinator\Entity\Group
 */
class PortalGroup extends Group
{
    const ENTITY_NAME = 'entity:Group\PortalGroup';

    /**
     * @ManyToOne(targetEntity="iCoordinator\Entity\Portal")
     * @JoinColumn(name="scope_id", referencedColumnName="id", nullable=false)
     **/
    protected $scope;

    /**
     * @return \iCoordinator\Entity\Portal
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @param $scope
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setScope($scope)
    {
        if (!$scope instanceof Portal) {
            throw new \InvalidArgumentException(
                '$source should be instance of iCoordinator\\Entity\\Portal'
            );
        }
        $this->scope = $scope;
        return $this;
    }
}
