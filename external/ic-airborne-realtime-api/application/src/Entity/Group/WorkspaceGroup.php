<?php

namespace iCoordinator\Entity\Group;

use iCoordinator\Entity\Group;
use iCoordinator\Entity\Workspace;

/**
 * Class WorkspaceGroup
 *
 * @Entity
 * @package iCoordinator\Entity\Group
 */
class WorkspaceGroup extends Group
{
    const ENTITY_NAME = 'entity:Group\WorkspaceGroup';

    /**
     * @ManyToOne(targetEntity="iCoordinator\Entity\Workspace", cascade={"persist"})
     * @JoinColumn(name="scope_id", referencedColumnName="id", nullable=false)
     **/
    protected $scope;

    /**
     * @return \iCoordinator\Entity\Workspace
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
        if (!$scope instanceof Workspace) {
            throw new \InvalidArgumentException(
                '$source should be instance of iCoordinator\\Entity\\Workspace'
            );
        }
        $this->scope = $scope;
        return $this;
    }
}
