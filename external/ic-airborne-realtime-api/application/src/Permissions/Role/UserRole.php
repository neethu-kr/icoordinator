<?php

namespace iCoordinator\Permissions\Role;

use iCoordinator\Entity\Acl\AclRole\AclUserRole;
use iCoordinator\Entity\User;
use Laminas\Permissions\Acl\Role\RoleInterface;

class UserRole implements RoleInterface, SharedLinkAccessInterface
{
    const ROLE_ID = 'user';

    /**
     * @var integer
     */
    private $userId = null;

    /**
     * @var string
     */
    private $sharedLinkToken = null;

    /**
     * @param $user
     * @throws \Exception
     */
    public function __construct($user)
    {
        if (is_numeric($user)) {
            $this->userId = $user;
        } elseif ($user instanceof User) {
            $this->userId = $user->getId();
        } elseif ($user instanceof AclUserRole) {
            $this->userId = $user->getUser()->getId();
        } else {
            throw new \Exception("\$user should be either integer or instance of \\iCoordinator\\Entity\\User");
        }
    }

    public function getRoleId()
    {
        return self::ROLE_ID;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @return string
     */
    public function getSharedLinkToken()
    {
        return $this->sharedLinkToken;
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
}
