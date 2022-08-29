<?php

namespace iCoordinator\Permissions\Resource;

use Laminas\Permissions\Acl\Resource\ResourceInterface;

interface HavingWorkspaceResourceInterface extends ResourceInterface
{
    /**
     * @return Workspace
     */
    public function getWorkspace();
}
