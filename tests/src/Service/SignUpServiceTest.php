<?php

namespace iCoordinator;

use ChargifyV2\Client as ChargifyClient;
use ChargifyV2\DirectHelper as ChargifyDirectHelper;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use iCoordinator\Entity\User;
use iCoordinator\Permissions\PermissionType;
use iCoordinator\PHPUnit\DbUnit\DataSet\ArrayDataSet;
use iCoordinator\PHPUnit\TestCase;
use iCoordinator\Service\EmailConfirmationService;
use iCoordinator\Service\PermissionService;
use iCoordinator\Service\SignUpService;
use iCoordinator\Service\UserService;
use iCoordinator\Service\WorkspaceService;
use iCoordinator\Test\Helper\MockReader;

class SignUpServiceTest extends TestCase
{
    const USER_ID = 1;
    const USER_ID2 = 2;
    const USER_ID3 = 3;
    const PORTAL_ID = 1;
    const PORTAL_ID2 = 2;
    const WORKSPACE_ID1 = 1;
    const WORKSPACE_ID2 = 2;
    const WORKSPACE_ID3 = 3;
    const WORKSPACE_ID4 = 4;
    const WORKSPACE_ID5 = 5;
    const WORKSPACE_ID6 = 6;
    const GROUP_ID3 = 3;
    const GROUP_ID4 = 4;
    const GROUP_ID5 = 5;
    const GROUP_ID6 = 6;
    const USERNAME = 'test@icoordinator.com';
    const USERNAME2 = 'test2@icoordinator.com';
    const USERNAME3 = 'test3@icoordinator.com';
    const USERNAME4 = 'test4@icoordinator.com';
    const PASSWORD = 'password';
    const PUBLIC_CLIENT_ID = 'public_test';
    const INVITATION_TOKEN1 = 'xxxx';
    const INVITATION_TOKEN2 = 'yyyy';
    const INVITATION_ID1 = 1;
    const INVITATION_ID2 = 2;
    const WEBSITE_ID = 'ic_eur';

    protected function getDataSet()
    {
        return new ArrayDataSet([
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
            'user_locales' => [

            ],
            'portals' => [
                [
                    'id' => 1,
                    'name' => 'Test Portal',
                    'owned_by' => self::USER_ID
                ],
                [
                    'id' => 2,
                    'name' => 'Test Portal2',
                    'owned_by' => self::USER_ID
                ]
            ],
            'workspaces' => array(
                array(
                    'id' => self::WORKSPACE_ID1,
                    'name' => 'Workspace 1',
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'id' => self::WORKSPACE_ID2,
                    'name' => 'Workspace 2',
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'id' => self::WORKSPACE_ID3,
                    'name' => 'Workspace 3',
                    'portal_id' => self::PORTAL_ID2
                ),
                array(
                    'id' => self::WORKSPACE_ID4,
                    'name' => 'Workspace 4',
                    'portal_id' => self::PORTAL_ID2
                ),
                array(
                    'id' => self::WORKSPACE_ID5,
                    'name' => 'Workspace 5',
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'id' => self::WORKSPACE_ID6,
                    'name' => 'Workspace 6',
                    'portal_id' => self::PORTAL_ID
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
                    'scope_type' => 'portal',
                    'scope_id' => self::PORTAL_ID
                ),
                array(
                    'id' => 3,
                    'name' => 'Test Group 3',
                    'portal_id' => self::PORTAL_ID,
                    'scope_type' => 'workspace',
                    'scope_id' => self::WORKSPACE_ID1
                ),
                array(
                    'id' => 4,
                    'name' => 'Test Group 4',
                    'portal_id' => self::PORTAL_ID,
                    'scope_type' => 'workspace',
                    'scope_id' => self::WORKSPACE_ID1
                ),
                array(
                    'id' => 5,
                    'name' => 'Test Group 5',
                    'portal_id' => self::PORTAL_ID,
                    'scope_type' => 'workspace',
                    'scope_id' => self::WORKSPACE_ID5
                ),
                array(
                    'id' => 6,
                    'name' => 'Test Group 6',
                    'portal_id' => self::PORTAL_ID,
                    'scope_type' => 'workspace',
                    'scope_id' => self::WORKSPACE_ID6
                )
            ),
            'licenses' => [
                [
                    'id' => 1,
                    'users_limit' => 10,
                    'workspaces_limit' => 3,
                    'storage_limit' => 5
                ]
            ],
            'license_chargify_mappers' => [
                [
                    'id' => 1,
                    'license_id' => 1,
                    'chargify_website_id' => 'ic_eur',
                    'chargify_product_handle' => 'basic-*',
                    'chargify_users_component_ids' => '123|345',
                    'chargify_workspaces_component_ids' => null,
                    'chargify_storage_component_ids' => null
                ]
            ],
            'subscriptions' => [],
            'subscription_chargify_mappers' => [],
            'email_confirmations' => [
                [
                    'id' => 1,
                    'user_id' => self::USER_ID3,
                    'token' => $this->getEmailConfirmationService()->passwordToToken(self::USER_ID3, 'xxx')
                ]
            ],
            'invitations' => [
                [
                    'id' => 1,
                    'email' => 'constantine.yurevich@designtech.se',
                    'first_name' => 'Constantine',
                    'last_name' => 'Yurevich',
                    'portal_id' => self::PORTAL_ID,
                    'created_by' => self::USER_ID,
                    'token' => self::INVITATION_TOKEN1
                ],
                [
                    'id' => self::INVITATION_ID2,
                    'email' => self::USERNAME2,
                    'portal_id' => self::PORTAL_ID,
                    'created_by' => self::USER_ID,
                    'token' => self::INVITATION_TOKEN2
                ]
            ],
            'invitation_workspaces' => [
                [
                    'id' => 1,
                    'invitation_id' => self::INVITATION_ID1,
                    'workspace_id' => self::WORKSPACE_ID1
                ],
                [
                    'id' => 2,
                    'invitation_id' => self::INVITATION_ID1,
                    'workspace_id' => self::WORKSPACE_ID2
                ]
            ],
            'invitation_workspace_groups' => [
                [
                    'id' => 1,
                    'invitation_id' => self::INVITATION_ID1,
                    'group_id' => self::GROUP_ID3
                ],
                [
                    'id' => 2,
                    'invitation_id' => self::INVITATION_ID1,
                    'group_id' => self::GROUP_ID4
                ]
            ],
            'acl_permissions' => [],
            'acl_roles' => [],
            'acl_resources' => [],
            'events' => []
        ]);
    }

    public function testGetSecureFields()
    {
        $signUpService = $this->getSignUpService();

        $redirectUrl = 'http://icoordinator.com';
        $secureFields = $signUpService->getSecureFields(self::WEBSITE_ID, $redirectUrl);

        $this->assertContains('redirect_uri=' . urlencode($redirectUrl), $secureFields['data']);
//        $this->assertContains('signup[customer][reference]=self-serve', $secureFields['data']);
    }

    public function testSignUp()
    {
        $apiId = 'xxxx';
        $apiPassword = 'xxxx';
        $apiSecret = 'yyyy';

        $chargifyDirectHelper = new ChargifyDirectHelper($apiId, $apiSecret);
        $httpClient = new HttpClient([
            'handler' => $this->getSuccessChargifyResponseMockHandler()
        ]);
        $chargifyClient = new ChargifyClient($apiId, $apiPassword);
        $chargifyClient->setHttpClient($httpClient);

        $signUpService = $this->getSignUpService()
            ->setChargifyDirectHelper($chargifyDirectHelper)
            ->setChargifyClient($chargifyClient);

        $timeStamp = time();
        $nonce = 'xxx';
        $statusCode = '200';
        $resultCode = '200';
        $callId = 'zzz';
        $signature = hash_hmac('sha1', $apiId . $timeStamp . $nonce . $statusCode . $resultCode . $callId, $apiSecret);

        $signUpService->signUp([
            'user' => [
                'email' => 'test5@icoordinator.com',
                'password' => 'xxxx'
            ],
            'portal' => [
                'name' => 'My New Portal'
            ],
            'chargify' => [
                'website_id' => 'ic_eur',
                'api_id' => $apiId,
                'timestamp' => $timeStamp,
                'nonce' => $nonce,
                'status_code' => $statusCode,
                'result_code' => $resultCode,
                'call_id' => $callId,
                'signature' => $signature
            ]
        ]);
    }

    private function getSuccessChargifyResponseMockHandler()
    {
        $body = MockReader::read('chargify-responses/signup-success.json');

        $mockHandler = new MockHandler([
            new Response(200, [
                'Cache-Control' => 'must-revalidate, private, max-age=0',
                'Content-Type' => 'application/json; charset=utf-8',
                'Date' => 'Fri, 28 Aug 2015 10:35:18 GMT',
                'Etag' => '38042fe852494a1342625cd0cb6f80e5',
                'P3p' => 'CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"',
                'Server' => 'nginx + Phusion Passenger',
                'Status' => '200 OK',
                'Strict-Transport-Security' => 'max-age=31536000',
                'X-Content-Type-Options' => 'nosniff',
                'X-Powered-By' => 'Phusion Passenger',
                'X-Rack-Cache' => 'miss',
                'X-Runtime' => '0.040029',
                'X-Ua-Compatible' => 'IE=Edge,chrome=1',
                'X-Xss-Protection' => '1; mode=block',
                'Content-Length' => strlen($body),
                'Connection' => 'keep-alive'
            ], $body)
        ]);

        return $mockHandler;
    }

    public function testSignUpConfirmEmail()
    {
        $user = $this->getEntityManager()->getReference(User::ENTITY_NAME, self::USER_ID3);

        $emailConfirmationService = $this->getEmailConfirmationService();

        $emailConfirmation = $emailConfirmationService->getEmailConfirmationByUser($user);
        $user = $emailConfirmationService->signUpConfirmEmail($emailConfirmation->getToken());

        $this->assertTrue($user->isEmailConfirmed());
        $this->assertNull($emailConfirmationService->getEmailConfirmationByUser($user));
    }

    public function testInviteNewUser()
    {
        $email = 'fredrik.lindvall@designtech.se';

        $invitation = $this->getSignUpService()->inviteUser([
            'email' => $email,
            'portal' => [
                'id' => self::PORTAL_ID,
            ],
            'workspaces' => [
                [
                    'id' => self::WORKSPACE_ID5
                ],
                [
                    'id' => self::WORKSPACE_ID6
                ]
            ],
            'groups' => [
                [
                    'id' => self::GROUP_ID5
                ],
                [
                    'id' => self::GROUP_ID6
                ]
            ]
        ], self::USER_ID);

        $this->assertInstanceOf('iCoordinator\Entity\Invitation', $invitation);
        $this->assertEquals(self::PORTAL_ID, $invitation->getPortal()->getId());
        $this->assertEquals($email, $invitation->getEmail());
        $this->assertEquals(self::USER_ID, $invitation->getCreatedBy()->getId());
        $this->assertNotEmpty($invitation->getToken());
        $this->assertCount(2,$invitation->getWorkspaces());
        $this->assertCount(2,$invitation->getWorkspaceGroups());

    }

    public function testInviteExistingUser()
    {
        $email = self::USERNAME2;
        $invitation = $this->getSignUpService()->inviteUser(array(
            'email' => $email,
            'portal' => array(
                'id' => self::PORTAL_ID
            )
        ), self::USER_ID);

        $this->assertInstanceOf('iCoordinator\Entity\Invitation', $invitation);
        $this->assertEquals(self::PORTAL_ID, $invitation->getPortal()->getId());
        $this->assertEquals($email, $invitation->getEmail());
        $this->assertEquals(self::USER_ID, $invitation->getCreatedBy()->getId());
        $this->assertNotEmpty($invitation->getToken());
    }

    public function testAcceptInvitationNewUser()
    {
        $signUpService = $this->getSignUpService();
        $invitation = $signUpService->getInvitationByToken(self::INVITATION_TOKEN1);
        $permissionService = $this->getPermissionService();

        //accept invitation
        $portal = $signUpService->acceptInvitation(self::INVITATION_TOKEN1);

        //get user just created
        $user = $this->getUserService()->getUserByEmail($invitation->getEmail());

        $this->assertNotEmpty($portal);
        $this->assertEquals($invitation->getPortal()->getId(), $portal->getId());
        $this->assertNotEmpty($user);
        $this->assertNotEmpty($user->getName());
        $this->assertTrue($user->isEmailConfirmed());
        $this->assertTrue($permissionService->hasPermission($user, $portal, PermissionType::PORTAL_ACCESS));
        $this->assertTrue(
            $permissionService->hasPermission(
                $user,
                $this->getWorkspaceService()->getWorkspace(self::WORKSPACE_ID1), PermissionType::WORKSPACE_ACCESS
            )
        );
        $this->assertTrue(
            $permissionService->hasPermission(
                $user,
                $this->getWorkspaceService()->getWorkspace(self::WORKSPACE_ID2), PermissionType::WORKSPACE_ACCESS
            )
        );
    }

    public function testAcceptInvitationExistingUser()
    {
        $signUpService = $this->getSignUpService();
        $invitation = $signUpService->getInvitationByToken(self::INVITATION_TOKEN2);
        $user = $this->getUserService()->getUserByEmail($invitation->getEmail());
        $permissionService = $this->getPermissionService();

        //accept invitation
        $portal = $signUpService->acceptInvitation(self::INVITATION_TOKEN2);

        $this->assertNotEmpty($portal);
        $this->assertEquals($invitation->getPortal()->getId(), $portal->getId());
        $this->assertNotEmpty($user);
        $this->assertTrue($user->isEmailConfirmed());
        $this->assertTrue($permissionService->hasPermission($user, $portal, PermissionType::PORTAL_ACCESS));
    }

    public function testDeleteInvitation(){
        $signUpService = $this->getSignUpService();
        $invitation = $signUpService->getInvitationByToken(self::INVITATION_TOKEN1);

        //delete invitation
        $signUpService->deleteInvitation($invitation->getId());

        $invitation = $signUpService->getInvitationByToken(self::INVITATION_TOKEN1);
        $this->assertEmpty($invitation);
    }
    public function testResendInvitation(){
        $invitation = $this->getSignUpService()->resendInvitation(self::INVITATION_ID2);

        $this->assertInstanceOf('iCoordinator\Entity\Invitation', $invitation);
        $this->assertEquals(self::PORTAL_ID, $invitation->getPortal()->getId());
        $this->assertEquals(self::USERNAME2, $invitation->getEmail());
        $this->assertEquals(self::USER_ID, $invitation->getCreatedBy()->getId());
        $this->assertNotEmpty($invitation->getToken());
    }
    /**
     * @return SignUpService
     */
    private function getSignUpService()
    {
        return $this->getContainer()->get('SignUpService');
    }

    /**
     * @return EmailConfirmationService
     */
    private function getEmailConfirmationService()
    {
        return $this->getContainer()->get('EmailConfirmationService');
    }

    /**
     * @return PermissionService
     */
    private function getPermissionService()
    {
        return $this->getContainer()->get('PermissionService');
    }

    /**
     * @return UserService
     */
    private function getUserService()
    {
        return $this->getContainer()->get('UserService');
    }

    /**
     * @return WorkspaceService
     */
    private function getWorkspaceService()
    {
        return $this->getContainer()->get('WorkspaceService');
    }
}