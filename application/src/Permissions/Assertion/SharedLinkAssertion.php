<?php

namespace iCoordinator\Permissions\Assertion;

use iCoordinator\Entity\File;
use iCoordinator\Entity\SharedLink;
use iCoordinator\Permissions\Role\SharedLinkAccessInterface;
use iCoordinator\Permissions\Role\UserRole;
use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Assertion\AssertionInterface;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;

class SharedLinkAssertion implements AssertionInterface
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
     * @var File
     */
    private $resource;

    public function assert(Acl $acl, RoleInterface $role = null, ResourceInterface $resource = null, $privilege = null)
    {
        if (!$acl instanceof \iCoordinator\Permissions\Acl) {
            throw new \Exception('$acl should be instance of \iCoordinator\Permissions\Acl');
        }

        if (!$role instanceof SharedLinkAccessInterface) {
            return false;
        }

        if (!$resource instanceof File) {
            throw new \Exception("SharedLinkAssertion supports only instances of File resource for now");
        }
        $this->acl = $acl;
        $this->role = $role;
        $this->resource = $resource;

        $sharedLinkToken = $role->getSharedLinkToken();

        if (!$sharedLinkToken) {
            return false;
        }

        $sharedLinkService = $acl->getContainer()->get('SharedLinkService');
        $sharedLink = $sharedLinkService->getSharedLinkByToken($role->getSharedLinkToken());

        // check if shared link belongs to one of parent folders
        if ($resource != $sharedLink->getFile()) {
            $parent = $resource->getParent();
            while ($parent != null && $parent != $sharedLink->getFile()) {
                $parent = $parent->getParent();
            }
            if ($parent == null) {
                return false;
            }
        }

        if ($sharedLink->getAccessType() != SharedLink::ACCESS_TYPE_PUBLIC) {
            if ($sharedLink->getAccessType() == SharedLink::ACCESS_TYPE_PORTAL) {
                return $this->hasPortalAccess();
            }
            if ($sharedLink->getAccessType() == SharedLink::ACCESS_TYPE_RESTRICTED) {
                return false;
            }

            if (!$role instanceof UserRole) {
                return false;
            }
        }

        return true;
    }
    private function hasPortalAccess()
    {
        $portalAccessAssertion = new PortalAccessAssertion();
        $result = $portalAccessAssertion->assert($this->acl, $this->role, $this->resource->getWorkspace()->getPortal());
        return $result;
    }
}
