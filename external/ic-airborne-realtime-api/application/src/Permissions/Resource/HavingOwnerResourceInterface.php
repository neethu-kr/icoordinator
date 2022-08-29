<?php

namespace iCoordinator\Permissions\Resource;

use Laminas\Permissions\Acl\Resource\ResourceInterface;

interface HavingOwnerResourceInterface extends ResourceInterface
{
    public function getId();
    public function getOwnedBy();
}
