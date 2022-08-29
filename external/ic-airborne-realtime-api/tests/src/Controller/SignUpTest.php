<?php

namespace iCoordinator;


use GuzzleHttp\Client;
use iCoordinator\Config\Route\SignUpRouteConfig;
use iCoordinator\Console\Command\Chargify\SetupLicenseMappingsCommand;
use iCoordinator\Console\Helper\ContainerHelper;
use iCoordinator\Entity\Acl\AclResource\AclPortalResource;
use iCoordinator\Entity\Acl\AclResource\AclWorkspaceResource;
use iCoordinator\Entity\Acl\AclRole\AclUserRole;
use iCoordinator\Entity\Portal;
use iCoordinator\Entity\Subscription;
use iCoordinator\Entity\Workspace;
use iCoordinator\Permissions\BitMask;
use iCoordinator\Permissions\PermissionType;
use iCoordinator\PHPUnit\DbUnit\DataSet\ArrayDataSet;
use iCoordinator\PHPUnit\TestCase;
use iCoordinator\Service\EmailConfirmationService;
use iCoordinator\Service\GroupService;
use iCoordinator\Service\PortalService;
use iCoordinator\Service\SignUpService;
use iCoordinator\Service\SubscriptionService;
use iCoordinator\Service\UserService;
use Rhumsaa\Uuid\Uuid;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Laminas\Json\Json;

class SignUpTest extends TestCase
{
    const USER_ID = 1;
    const USER_ID2 = 2;
    const USER_ID3 = 3;
    const USER_ID4 = 4;
    const PORTAL_ID = 1;
    const PORTAL_ID2 = 2;
    const USERNAME = 'test@icoordinator.com';
    const USERNAME2 = 'test2@icoordinator.com';
    const USERNAME3 = 'test3@icoordinator.com';
    const USERNAME4 = 'test4@icoordinator.com';
    const PASSWORD = 'password';
    const PUBLIC_CLIENT_ID = 'public_test';
    const INVITATION_TOKEN1 = 'xxxx';
    const INVITATION_TOKEN2 = 'yyyy';
    const INVITATION_TOKEN4 = 'zzzz';
    const INVITATION_ID1 = 1;
    const WEBSITE_ID = 'ic_test';
    const GROUP_ID = 1;
    const GROUP_ID2 = 2;
    const GROUP_ID4 = 4;
    const WORKSPACE_ID = 1;
    const WORKSPACE_ID2 = 2;
    const INVITATION_ID3 = 3;
    const INVITE_EMAIL1 = 'inviteemail1@icoordinator.com';
    const INVITE_EMAIL2 = 'inviteemail2@icoordinator.com';
    const INVITE_EMAIL3 = 'inviteemail3@icoordinator.com';
    const INVITE_EMAIL4 = 'inviteemail4@icoordinator.com';
    const LICENSE_ID = 1;

    private $emailConfirmationToken;

    protected function getDataSet()
    {
        $this->emailConfirmationToken = $this->getEmailConfirmationService()->passwordToToken('newuser@icoordinator.com', 'pass');

        $workspaceBitMask = new BitMask(Workspace::RESOURCE_ID);
        $portalBitMask = new BitMask(Portal::RESOURCE_ID);

        return new ArrayDataSet([
            'users' => array(
                array(
                    'id' => self::USER_ID,
                    'email' => self::USERNAME,
                    'name' => 'John Dow',
                    'password' => password_hash(self::PASSWORD, PASSWORD_DEFAULT),
                    'email_confirmed' => 1,
                    'uuid' => Uuid::uuid4()->toString()
                ),
                array(
                    'id' => self::USER_ID2,
                    'email' => self::USERNAME2,
                    'name' => 'John Dow',
                    'password' => password_hash(self::PASSWORD, PASSWORD_DEFAULT),
                    'email_confirmed' => 1,
                    'uuid' => Uuid::uuid4()->toString()
                ),
                array(
                    'id' => self::USER_ID3,
                    'email' => self::USERNAME3,
                    'name' => 'John Dow',
                    'password' => password_hash(self::PASSWORD, PASSWORD_DEFAULT),
                    'email_confirmed' => 0,
                    'uuid' => Uuid::uuid4()->toString()
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
                    'lang' => 'da',
                    'date_format' => 'dd/mm/yyyy',
                    'time_format' => 'HH:MM',
                    'first_week_day' => 1
                ),
                array(
                    'id' => self::USER_ID3,
                    'user_id' => self::USER_ID3,
                    'lang' => 'en',
                    'date_format' => 'dd/mm/yyyy',
                    'time_format' => 'HH:MM',
                    'first_week_day' => 1
                )
            ),
            'portals' => [
                array(
                    'id' => 1,
                    'name' => 'Test Portal 1',
                    'owned_by' => self::USER_ID
                ),
                array(
                    'id' => 2,
                    'name' => 'Test Portal 2',
                    'owned_by' => self::USER_ID2
                )
            ],
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
                ),
                array(
                    'id' => 3,
                    'name' => 'Workspace 3',
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'id' => 4,
                    'name' => 'Workspace 21',
                    'portal_id' => self::PORTAL_ID2
                )
            ),
            'groups' => array(
                array(
                    'id' => 1,
                    'name' => 'Test Group 1',
                    'portal_id' => self::PORTAL_ID,
                    'scope_type' => 'portal',
                    'scope_id' => self::PORTAL_ID
                ),
                array(
                    'id' => 2,
                    'name' => 'Test Group 2',
                    'portal_id' => self::PORTAL_ID,
                    'scope_type' => 'workspace',
                    'scope_id' => self::WORKSPACE_ID
                ),
                array(
                    'id' => 3,
                    'name' => 'Test Group 3',
                    'portal_id' => self::PORTAL_ID,
                    'scope_type' => 'workspace',
                    'scope_id' => self::WORKSPACE_ID
                ),
                array(
                    'id' => 4,
                    'name' => 'Test Group 4',
                    'portal_id' => self::PORTAL_ID,
                    'scope_type' => 'workspace',
                    'scope_id' => self::WORKSPACE_ID2
                )
            ),
            'group_memberships' => [],
            'email_confirmations' => array(
                array(
                    'id' => 1,
                    'user_id' => self::USER_ID3,
                    'token' => $this->emailConfirmationToken
                )
            ),
            'invitations' => array(
                array(
                    'id' => 1,
                    'email' => self::INVITE_EMAIL1,
                    'portal_id' => self::PORTAL_ID,
                    'created_by' => self::USER_ID,
                    'token' => self::INVITATION_TOKEN1,
                    'first_name' => 'John',
                    'last_name' => 'Dow'
                ),
                array(
                    'id' => 2,
                    'email' => self::INVITE_EMAIL2,
                    'portal_id' => self::PORTAL_ID,
                    'created_by' => self::USER_ID,
                    'token' => self::INVITATION_TOKEN2
                ),
                array(
                    'id' => self::INVITATION_ID3,
                    'email' => self::INVITE_EMAIL4,
                    'portal_id' => self::PORTAL_ID2,
                    'created_by' => self::USER_ID2,
                    'token' => self::INVITATION_TOKEN4
                )
            ),
            'invitation_workspaces' => [
                [
                    'id' => 1,
                    'invitation_id' => self::INVITATION_ID1,
                    'workspace_id' => self::WORKSPACE_ID
                ]
            ],
            'invitation_workspace_groups' => [
                [
                    'id' => 1,
                    'invitation_id' => self::INVITATION_ID1,
                    'group_id' => self::GROUP_ID
                ]
            ],
            'acl_roles' => array(
                array(
                    'id' => 1,
                    'entity_type' => AclUserRole::ACL_ROLE_ENTITY_TYPE,
                    'entity_id' => self::USER_ID
                ),
                array(
                    'id' => 2,
                    'entity_type' => AclUserRole::ACL_ROLE_ENTITY_TYPE,
                    'entity_id' => self::USER_ID2
                ),
                array(
                    'id' => 3,
                    'entity_type' => AclUserRole::ACL_ROLE_ENTITY_TYPE,
                    'entity_id' => self::USER_ID3
                )
            ),
            'acl_resources' => array(
                array(
                    'id' => 1,
                    'entity_type' => AclPortalResource::ACL_RESOURCE_ENTITY_TYPE,
                    'entity_id' => self::PORTAL_ID
                ),
                array(
                    'id' => 2,
                    'entity_type' => AclPortalResource::ACL_RESOURCE_ENTITY_TYPE,
                    'entity_id' => self::PORTAL_ID2
                ),
                array(
                    'id' => 3,
                    'entity_type' => AclWorkspaceResource::ACL_RESOURCE_ENTITY_TYPE,
                    'entity_id' => 1
                ),
                array(
                    'id' => 4,
                    'entity_type' => AclWorkspaceResource::ACL_RESOURCE_ENTITY_TYPE,
                    'entity_id' => 2
                ),
                array(
                    'id' => 5,
                    'entity_type' => AclWorkspaceResource::ACL_RESOURCE_ENTITY_TYPE,
                    'entity_id' => 3
                )
            ),
            'acl_permissions' => array(
                //portal permissions
                array(
                    'acl_role_id' => self::USER_ID,
                    'acl_resource_id' => 1,
                    'bit_mask' => $portalBitMask->getBitMask(PermissionType::PORTAL_ADMIN),
                    'is_deleted' => 0,
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'acl_role_id' => self::USER_ID2,
                    'acl_resource_id' => 1,
                    'bit_mask' => $portalBitMask->getBitMask(PermissionType::PORTAL_ACCESS),
                    'is_deleted' => 0,
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'acl_role_id' => self::USER_ID2,
                    'acl_resource_id' => 2,
                    'bit_mask' => $portalBitMask->getBitMask(PermissionType::PORTAL_ACCESS),
                    'is_deleted' => 0,
                    'portal_id' => self::PORTAL_ID2
                ),
                array(
                    'acl_role_id' => self::USER_ID3,
                    'acl_resource_id' => 2,
                    'bit_mask' => $portalBitMask->getBitMask(PermissionType::PORTAL_ADMIN),
                    'is_deleted' => 0,
                    'portal_id' => self::PORTAL_ID2
                ),
                //workspace permissions
                array(
                    'acl_role_id' => self::USER_ID2,
                    'acl_resource_id' => 3,
                    'bit_mask' => $workspaceBitMask->getBitMask(PermissionType::WORKSPACE_ACCESS),
                    'is_deleted' => 1,
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'acl_role_id' => self::USER_ID,
                    'acl_resource_id' => 3,
                    'bit_mask' => $workspaceBitMask->getBitMask(PermissionType::WORKSPACE_ACCESS),
                    'is_deleted' => 0,
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'acl_role_id' => self::USER_ID,
                    'acl_resource_id' => 4,
                    'bit_mask' => $workspaceBitMask->getBitMask(PermissionType::WORKSPACE_ACCESS),
                    'is_deleted' => 0,
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'acl_role_id' => self::USER_ID3,
                    'acl_resource_id' => 5,
                    'bit_mask' => $workspaceBitMask->getBitMask(PermissionType::WORKSPACE_ACCESS),
                    'is_deleted' => 0,
                    'portal_id' => self::PORTAL_ID
                )
            ),
            'licenses' => [
                [
                    'id' => self::LICENSE_ID,
                    'users_limit' => 3,
                    'workspaces_limit' => 0,
                    'storage_limit' => 0
                ]
            ],
            'license_chargify_mappers' => [

            ],
            'subscriptions' => [
                [
                    'id' => 1,
                    'portal_id' => self::PORTAL_ID,
                    'license_id' => self::LICENSE_ID,
                    'users_allocation' => 5
                ],
                [
                    'id' => 2,
                    'portal_id' => self::PORTAL_ID2,
                    'license_id' => self::LICENSE_ID,
                    'users_allocation' => 2
                ]
            ],
            'subscription_chargify_mappers' => [],
            'portal_allowed_clients' => []
        ]);
    }

    public function setUp(): void
    {
        parent::setUp();

        //setup real chargify mappers
        $command = new SetupLicenseMappingsCommand('setup-mappings');
        $command->setHelperSet(new HelperSet(array(
            'container' => new ContainerHelper($this->getContainer()),
        )));
        $command->execute(new ArrayInput([]), new NullOutput());
    }

    public function testCheckEmail()
    {
        $response = $this->post('/sign-up/check-email', [
            'email' => self::USERNAME
        ]);
        $result1 = json_decode((string)$response->getBody());

        $response = $this->post('/sign-up/check-email', [
            'email' => 'not-exists@icoordinator.com'
        ]);

        $result2 = json_decode((string)$response->getBody());

        $this->assertTrue($result1->exists);
        $this->assertFalse($result2->exists);
    }

    public function testSignUpWithChargify()
    {
        $response = $this->get(
            '/sign-up/secure-fields?website_id=' . self::WEBSITE_ID . '&redirect_url=http://icoordinator.com'
        );

        $result = json_decode((string)$response->getBody(), true);

        $data = [
            'secure' => [],
            'signup' => [
                'product' => [
                    'handle' => 'basic-edition'
                ],
                'components' => [
                    '115708' => '5'
                ],
                'customer' => [
                    'first_name' => 'Eric',
                    'last_name' => 'Cartman',
                    'email' => 'test6@icoordinator.com',
                    'organization' => 'Designtech',
                    'address' => 'My Street 123',
                    'city' => 'Lulea',
                    'country' => 'SE',
                    'reference' => 'v2-' . Uuid::uuid4()
                ],
                'payment_profile' => [
                    'first_name' => 'John',
                    'last_name' => 'Dow',
                    'card_number' => '1',
                    'expiration_month' => '12',
                    'expiration_year' => '2030'
                ]
            ]
        ];
        foreach ($result as $key => $value) {
            $data['secure'][$key] = str_replace('&amp;', '&', $value);
        }

        $httpClient = new Client();
        $response = $httpClient->post('https://api.chargify.com/api/v2/signups', [
            'form_params' => $data,
            'allow_redirects' => false
        ]);

        $this->assertEquals(302, $response->getStatusCode());

        $location = $response->getHeaderLine('Location');
        $urlParts = parse_url($location);
        $queryParams = [];
        parse_str($urlParts['query'], $queryParams);

        $response = $this->post('/sign-up', [
            'chargify' => [
                'website_id' => self::WEBSITE_ID,
                'api_id' => $queryParams['api_id'],
                'call_id' => $queryParams['call_id'],
                'nonce' => $queryParams['nonce'],
                'result_code' => $queryParams['result_code'],
                'signature' => $queryParams['signature'],
                'status_code' => $queryParams['status_code'],
                'timestamp' => $queryParams['timestamp']
            ],
            'user' => [
                'email' => 'test5@icoordinator.com',
                'password' => 'xxxx'
            ],
            'portal' => [
                'name' => 'My New Portal'
            ]
        ]);

        $result = json_decode((string)$response->getBody());

        $this->assertEquals(202, $response->getStatusCode());
        $this->assertNotEmpty($result);
        $this->assertNotEmpty($result->email);

        $portals = $this->getPortalService()->getPortalsAvailableForUser($result->id);

        $this->assertCount(1, $portals);

        /** @var Subscription $subscription */
        $subscription = $this->getSubscriptionService()->getPortalSubscription($portals[0]);

        $this->assertNotNull($subscription);
        $this->assertEquals(5, $subscription->getUsersAllocation());
        $this->assertEquals('active', $subscription->getState());
    }

    public function testSignUpWithoutChargify()
    {
        $response = $this->post('/sign-up', [
            'user' => [
                'email' => 'test5@icoordinator.com',
                'password' => 'xxxx'
            ]
        ]);

        $result = json_decode((string)$response->getBody());

        $this->assertEquals(202, $response->getStatusCode());
        $this->assertNotEmpty($result);
        $this->assertNotEmpty($result->email);
    }

    public function testSignUpConfirmEmail()
    {
        $response = $this->post(
            $this->urlFor(SignUpRouteConfig::ROUTE_SIGN_UP_CONFIRM_EMAIL, array(
                'token' => urlencode($this->emailConfirmationToken)
            ))
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($result->email_confirmed);


        $response = $this->post(
            $this->urlFor(SignUpRouteConfig::ROUTE_SIGN_UP_CONFIRM_EMAIL, array(
                'token' => urlencode($this->emailConfirmationToken)
            ))
        );

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testGetPortalInvitations()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->get(
            sprintf('/portals/%d/invitations', self::PORTAL_ID),
            [],
            $headers
        );

        $result = json_decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(2, $result);
        $result = current($result);
        $this->assertEquals(self::INVITE_EMAIL1, $result->email);
        $this->assertEquals('John', $result->first_name);
        $this->assertEquals('Dow', $result->last_name);
        $this->assertNotEmpty($result->workspaces);
        $this->assertNotEmpty($result->groups);
    }

    public function testInviteNewUser()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $email = 'fredrik.lindvall@designtech.se';
        $response = $this->post(
            '/invitations',
            array(
                'portal' => array(
                    'id' => self::PORTAL_ID
                ),
                'email' => $email
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(202, $response->getStatusCode());
        $this->assertNotEmpty($result->id);
        $this->assertEquals($email, $result->email);
    }

    public function testInviteNewUserWithDefaultGroup()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $email = 'constantine.yurevich@designtech.se';
        $response = $this->post(
            '/invitations',
            array(
                'portal' => [
                    'id' => self::PORTAL_ID
                ],
                'workspaces' => [
                    [
                        'id' => self::WORKSPACE_ID2
                    ]
                ],
                'groups' => [
                    [
                        'id' => self::GROUP_ID4
                    ]
                ],
                'email' => $email,
                'first_name' => 'John',
                'last_name' => 'Dow'
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(202, $response->getStatusCode());
        $this->assertNotEmpty($result->id);
        $this->assertEquals($email, $result->email);
        $this->assertEquals('John', $result->first_name);
        $this->assertEquals('Dow', $result->last_name);
        $this->assertNotEmpty($result->workspaces);
        $this->assertNotEmpty($result->groups);
    }

    public function testInviteNewUserWithoutEmailConfirmation()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $email = 'fredrik.lindvall@designtech.se';
        $response = $this->post(
            '/invitations?disable_email_confirmation=1',
            array(
                'portal' => array(
                    'id' => self::PORTAL_ID
                ),
                'email' => $email,
                'first_name' => 'Fredrik',
                'last_name' => 'Lindvall',
                'password' => 'Test1234'
            ),
            $headers
        );
        $result = Json::decode($response->getBody());
        $this->assertEquals(201, $response->getStatusCode());

        /** @var UserService $userService */
        $userService = $this->getContainer()->get('UserService');
        $user = $userService->getUserByEmail($email);

        $this->assertTrue($user->isEmailConfirmed());
        $this->assertNotNull($result->user_id);
    }

    public function testInviteExistingUser()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $email = self::USERNAME2;
        $response = $this->post(
            '/invitations',
            array(
                'portal' => array(
                    'id' => self::PORTAL_ID
                ),
                'email' => $email
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(202, $response->getStatusCode());
        $this->assertNotEmpty($result->id);
        $this->assertEquals($email, $result->email);
    }

    public function testInviteNewUserNotAuthorized()
    {
        $email = 'fredrik.lindvall@designtech.se';
        $response = $this->post(
            '/invitations',
            array(
                'portal' => array(
                    'id' => self::PORTAL_ID
                ),
                'email' => $email
            )
        );

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testInviteNewUserAccessDenied1()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $email = 'constantine.yurevich@designtech.se';
        $response = $this->post(
            '/invitations',
            array(
                'portal' => array(
                    'id' => self::PORTAL_ID
                ),
                'email' => $email
            ),
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testInviteNewUserAccessDenied2()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $email = 'fredrik.lindvall@designtech.se';
        $response = $this->post(
            '/invitations',
            array(
                'portal' => array(
                    'id' => self::PORTAL_ID2
                ),
                'email' => $email
            ),
            $headers
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testAcceptInvitationNewUser()
    {
        $response = $this->post(
            $this->urlFor(SignUpRouteConfig::ROUTE_SIGN_UP_ACCEPT_INVITATION, array(
                'token' => self::INVITATION_TOKEN1
            ))
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(self::PORTAL_ID, $result->id);


        $response = $this->post(
            $this->urlFor(SignUpRouteConfig::ROUTE_SIGN_UP_ACCEPT_INVITATION, array(
                'token' => self::INVITATION_TOKEN1
            ))
        );

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testAcceptInvitationExistingUser()
    {
        $response = $this->post(
            $this->urlFor(SignUpRouteConfig::ROUTE_SIGN_UP_ACCEPT_INVITATION, array(
                'token' => self::INVITATION_TOKEN2
            ))
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(self::PORTAL_ID, $result->id);


        $response = $this->post(
            $this->urlFor(SignUpRouteConfig::ROUTE_SIGN_UP_ACCEPT_INVITATION, array(
                'token' => self::INVITATION_TOKEN2
            ))
        );

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testAcceptInvitationWithGroupExistingUser()
    {
        $response = $this->post(
            $this->urlFor(SignUpRouteConfig::ROUTE_SIGN_UP_ACCEPT_INVITATION, array(
                'token' => self::INVITATION_TOKEN1
            ))
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(self::PORTAL_ID, $result->id);

        $paginator = $this->getGroupService()->getGroupUsers(self::GROUP_ID);
        $this->assertEquals(1, $paginator->count());
    }

    public function testAcceptInvitationNewUserExceedingLicense()
    {
        $response = $this->post(
            $this->urlFor(SignUpRouteConfig::ROUTE_SIGN_UP_ACCEPT_INVITATION, array(
                'token' => self::INVITATION_TOKEN4
            ))
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(403, $response->getStatusCode());
        //$this->assertEquals(self::PORTAL_ID2, $result->id);

    }

    public function testResendInvitation()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->post(
            $this->urlFor(SignUpRouteConfig::ROUTE_SIGN_UP_INVITATION_RESEND, array(
                'invitation_id' => self::INVITATION_ID3
            )),
            [],
            $headers
        );

        $this->assertEquals(204, $response->getStatusCode());

    }

    public function testDeleteInvitation()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME2, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->post(
            $this->urlFor(SignUpRouteConfig::ROUTE_SIGN_UP_INVITATION_RESEND, array(
                'invitation_id' => self::INVITATION_ID3
            )),
            [],
            $headers
        );

        $this->assertEquals(204, $response->getStatusCode());

    }

    /**
     * @return SignUpService
     */
    private function getSignUpService()
    {
        return $this->getContainer()->get('SignUpService');
    }

    /**
     * @return PortalService
     */
    private function getPortalService()
    {
        return $this->getContainer()->get('PortalService');
    }

    /**
     * @return SubscriptionService
     */
    private function getSubscriptionService()
    {
        return $this->getContainer()->get('SubscriptionService');
    }

    /**
     * @return EmailConfirmationService
     */
    private function getEmailConfirmationService()
    {
        return $this->getContainer()->get('EmailConfirmationService');
    }

    /**
     * @return GroupService
     */
    private function getGroupService()
    {
        return $this->getContainer()->get('GroupService');
    }
}