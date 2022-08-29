<?php

namespace iCoordinator\Permissions\Assertion;

use iCoordinator\Entity\MetaField;
use iCoordinator\Permissions\Privilege\MetaFieldPrivilege;
use iCoordinator\Permissions\Role\UserRole;
use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;

class MetaFieldAssertion extends PortalAccessAssertion
{

    /**
     * @var Acl
     */
    private $acl;

    /**
     * @var RoleInterface
     */
    private $role;

    /**
     * @var MetaField
     */
    private $resource;

    public function assert(Acl $acl, RoleInterface $role = null, ResourceInterface $resource = null, $privilege = null)
    {
        if (!$role instanceof UserRole) {
            return false;
        }

        if (!$resource instanceof MetaField) {
            return false;
        }

        $this->acl = $acl;
        $this->role = $role;
        $this->resource = $resource;

        if (!parent::assert($acl, $role, $resource->getPortal())) {
            return false;
        }

        switch ($privilege) {
            case null:
            case MetaFieldPrivilege::PRIVILEGE_READ:
                return true;
            break;
            case MetaFieldPrivilege::PRIVILEGE_DELETE:
            case MetaFieldPrivilege::PRIVILEGE_MODIFY:
                return $this->isPortalAdmin();
            break;
        }
    }

    private function isPortalAdmin()
    {
        $portalAdminAssertion = new PortalAdminAssertion();
        return $portalAdminAssertion->assert($this->acl, $this->role, $this->resource->getPortal());
    }
}
