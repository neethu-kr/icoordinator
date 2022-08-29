<?php

namespace iCoordinator\Permissions\Assertion;

use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Assertion\AssertionInterface;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;

class SystemAssertion implements AssertionInterface
{
    /**
     * @param Acl $acl
     * @param RoleInterface $role
     * @param ResourceInterface $resource
     * @param null $privilege
     * @return bool
     */
    public function assert(Acl $acl, RoleInterface $role = null, ResourceInterface $resource = null, $privilege = null)
    {
        return true;
    }
}
