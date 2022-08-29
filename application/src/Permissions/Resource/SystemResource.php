<?php

namespace iCoordinator\Permissions\Resource;

use Laminas\Permissions\Acl\Resource\ResourceInterface;

class SystemResource implements ResourceInterface
{
    const RESOURCE_ID = 'system';

    public function getResourceId()
    {
        return self::RESOURCE_ID;
    }
}
