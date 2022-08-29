<?php

namespace iCoordinator\PHPUnit\Helper;

use iCoordinator\Entity\User;

class UsersHelper extends AbstractDataHelper
{
    protected $defaults = [
        'name' => 'John Dow',
        'email' => 'test@icoordinator.com',
        'email_confirmed' => 1
    ];

    protected $randomizableFields = [
        'name' => [
            'type' => self::RANDOMIZER_TYPE_APPEND,
            'separator' => ' '
        ],
        'email' => [
            'type' => self::RANDOMIZER_TYPE_PREPEND,
            'separator' => '_'
        ]
    ];

    public function createUser($data = array(), $useDefaults = true, $randomizeDefaults = true)
    {
        $user = new User();

        $user = $this->hydrate($user, $data, $useDefaults, $randomizeDefaults);

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush($user);

        return $user;
    }
}
