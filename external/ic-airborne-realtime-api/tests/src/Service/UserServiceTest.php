<?php

namespace iCoordinator;

use iCoordinator\PHPUnit\DbUnit\DataSet\ArrayDataSet;
use iCoordinator\PHPUnit\TestCase;

class UserServiceTest extends TestCase
{
    const USER_ID = 1;
    const USER_ID2 = 2;
    const USER_ID3 = 3;
    const PORTAL_ID = 1;
    const USERNAME = 'test@icoordinator.com';
    const USERNAME2 = 'test2@icoordinator.com';
    const USERNAME3 = 'test3@icoordinator.com';
    const USERNAME4 = 'test4@icoordinator.com';
    const PASSWORD = 'password';
    const PUBLIC_CLIENT_ID = 'public_test';
    const INVITATION_TOKEN1 = 'xxxx';
    const INVITATION_TOKEN2 = 'yyyy';

    protected function getDataSet()
    {
        return new ArrayDataSet(array(
            'oauth_clients' => array(
                array(
                    'client_id' => self::PUBLIC_CLIENT_ID
                )
            ),
            'users' => array(
                array(
                    'id' => self::USER_ID,
                    'email' => self::USERNAME,
                    'name' => 'John Dou',
                    'password' => password_hash(self::PASSWORD, PASSWORD_DEFAULT),
                    'email_confirmed' => 1
                ),
                array(
                    'id' => self::USER_ID2,
                    'email' => self::USERNAME2,
                    'name' => 'John Dou',
                    'password' => password_hash(self::PASSWORD, PASSWORD_DEFAULT),
                    'email_confirmed' => 1
                ),
                array(
                    'id' => self::USER_ID3,
                    'email' => self::USERNAME3,
                    'name' => 'John Dou',
                    'password' => password_hash(self::PASSWORD, PASSWORD_DEFAULT),
                    'email_confirmed' => 0
                )
            ),
            'user_locales' => array(
                array(
                    'id' => self::USER_ID,
                    'user_id' => self::USER_ID,
                    'lang' => 'en',
                    'date_format' => 'dd/mm/yyyy',
                    'time_format' => 'HH:MM',
                    'first_week_day' => 1
                ),
                array(
                    'id' => self::USER_ID2,
                    'user_id' => self::USER_ID2,
                    'lang' => 'en',
                    'date_format' => 'dd/mm/yyyy',
                    'time_format' => 'HH:MM',
                    'first_week_day' => 1
                )
            ),
            'portals' => array(
                array(
                    'id' => 1,
                    'name' => 'Test Portal',
                    'owned_by' => self::USER_ID
                )
            ),
            'workspaces' => array(
                array(
                    'id' => 1,
                    'name' => 'Workspace 1',
                    'portal_id' => self::PORTAL_ID

                ),
                array(
                    'id' => 2,
                    'name' => 'Workspace 2',
                    'portal_id' => self::PORTAL_ID
                )
            ),
            'email_confirmations' => array(),
            'invitations' => array(
                array(
                    'id' => 1,
                    'email' => 'constantine.yurevich@designtech.se',
                    'portal_id' => self::PORTAL_ID,
                    'created_by' => self::USER_ID,
                    'token' => self::INVITATION_TOKEN1
                ),
                array(
                    'id' => 2,
                    'email' => self::USERNAME2,
                    'portal_id' => self::PORTAL_ID,
                    'created_by' => self::USER_ID,
                    'token' => self::INVITATION_TOKEN2
                )
            ),
            'acl_permissions' => array(),
            'acl_roles' => array(),
            'acl_resources' => array(),
            'events' => array(),
            'portals' => array(
                array(
                    'id' => 1,
                    'name' => 'Test Portal',
                    'owned_by' => self::USER_ID
                )
            )
        ));
    }

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function testGetUser()
    {
        $userService = $this->getContainer()->get('UserService');
        $user = $userService->getUser(self::USER_ID);

        $this->assertInstanceOf('iCoordinator\Entity\User', $user);
        $this->assertEquals(self::USER_ID, $user->getId());
    }

    public function testGetUsersById()
    {
        $userService = $this->getContainer()->get('UserService');
        $users = $userService->getUsersByIds(array(self::USER_ID, self::USER_ID2));

        $this->assertCount(2, $users);
    }

    public function testCheckEmail()
    {
        $userService = $this->getContainer()->get('UserService');

        $this->assertTrue($userService->checkEmailExists(self::USERNAME));
        $this->assertTrue($userService->checkEmailExists(self::USERNAME3));
        $this->assertFalse($userService->checkEmailExists(self::USERNAME4));
    }

    public function testGetUsersList()
    {
        $userService = $this->getContainer()->get('UserService');
        $paginator = $userService->getUsers(1);

        $this->assertEquals(3, $paginator->count());
        $this->assertCount(1, $paginator->getIterator());
    }

    public function testCreateUser()
    {
        $userService = $this->getContainer()->get('UserService');
        $user = $userService->createUser(array(
            'email' => 'constantine.yurevich@designtech.se',
            'password' => 'password'
        ));

        $this->assertInstanceOf('iCoordinator\Entity\User', $user);
    }

    public function testCreateUserWithLocaleInformation()
    {
        $email = 'constantine.yurevich@designtech.se';
        $lang = 'no';
        $date_format = 'yyyy-mm-dd';
        $time_format = 'H:i:s';
        $first_week_day = 0;
        $locale = array('lang' => $lang,
            'date_format' => $date_format,
            'time_format' => $time_format,
            'first_week_day' => $first_week_day);
        $userService = $this->getContainer()->get('UserService');
        $user = $userService->createUser(array(
            'email' => 'constantine.yurevich@designtech.se',
            'password' => 'password',
            'locale' => $locale
        ));

        $this->assertInstanceOf('iCoordinator\Entity\User', $user);
    }

    public function testUpdateUser()
    {
        $userService = $this->getContainer()->get('UserService');
        $user = $userService->updateUser(self::USER_ID2, array('password' => 'newpassword'), self::USER_ID);

        $this->assertTrue(password_verify('newpassword', $user->getPassword()));
    }
    public function testUpdateUserWithLocaleInformation()
    {
        $lang = 'no';
        $date_format = 'yyyy-mm-dd';
        $time_format = 'H:i:s';
        $first_week_day = 0;
        $locale = array('lang' => $lang,
            'date_format' => $date_format,
            'time_format' => $time_format,
            'first_week_day' => $first_week_day);
        $userService = $this->getContainer()->get('UserService');
        $user = $userService->updateUser(self::USER_ID2,
            array('locale' => $locale),
            self::USER_ID);

        $this->assertEquals($lang, $user->getLocale()->getLang());
        $this->assertEquals($date_format, $user->getLocale()->getDateFormat());
        $this->assertEquals($time_format, $user->getLocale()->getTimeFormat());
        $this->assertEquals($first_week_day, $user->getLocale()->getFirstWeekDay());
    }
    public function testDeleteUser()
    {
        $userService = $this->getContainer()->get('UserService');
        $userService->deleteUser(self::USER_ID2, self::USER_ID);

        $user = $userService->getUser(self::USER_ID2);

        $this->assertTrue($user->getIsDeleted());
    }
}
