<?php

namespace iCoordinator\Permissions\Role;

interface SharedLinkAccessInterface
{
    /**
     * @param string $sharedLinkToken
     * @return $this
     */
    public function setSharedLinkToken($sharedLinkToken);

    /**
     * @return string
     */
    public function getSharedLinkToken();
}
