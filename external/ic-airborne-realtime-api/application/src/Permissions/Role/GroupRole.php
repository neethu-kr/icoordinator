<?php

namespace iCoordinator\Permissions\Role;

use iCoordinator\Entity\Acl\AclRole\AclGroupRole;
use iCoordinator\Entity\Group;
use Laminas\Permissions\Acl\Role\RoleInterface;

class GroupRole implements RoleInterface
{
    const ROLE_ID = 'group';

    /**
     * @var \iCoordinator\Entity\Group | string
     */
    private $group = null;

    /**
     * @param Group|AclGroupRole $group
     * @throws \Exception
     */
    public function __construct($group)
    {
        if ($group instanceof Group) {
            $this->group = $group;
        } elseif ($group instanceof AclGroupRole) {
            $this->group = $group->getGroup();
        } else {
            throw new \Exception(
                "\$group should be either instance of \\iCoordinator\\Entity\\Acl\\AclGroupRole
                or instance of \\iCoordinator\\Entity\\Group"
            );
        }
    }

    public function getRoleId()
    {
        return self::ROLE_ID;
    }

    public function getGroupId()
    {
        return $this->group->getId();
    }

    public function getGroup()
    {
        return $this->group;
    }
}
