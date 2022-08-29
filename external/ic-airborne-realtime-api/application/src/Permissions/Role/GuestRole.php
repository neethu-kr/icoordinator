<?php

namespace iCoordinator\Permissions\Role;

use Laminas\Permissions\Acl\Role\RoleInterface;

class GuestRole implements RoleInterface, SharedLinkAccessInterface
{
    const ROLE_ID = 'guest';

    /**
     * @var string
     */
    private $sharedLinkToken = null;

    public function getRoleId()
    {
        return self::ROLE_ID;
    }

    /**
     * @param string $sharedLinkToken
     * @return $this
     */
    public function setSharedLinkToken($sharedLinkToken)
    {
        $this->sharedLinkToken = $sharedLinkToken;
        return $this;
    }

    /**
     * @return string
     */
    public function getSharedLinkToken()
    {
        return $this->sharedLinkToken;
    }
}
