<?php

namespace iCoordinator;

use iCoordinator\Entity\Acl\AclResource\AclPortalResource;
use iCoordinator\Entity\Acl\AclRole\AclUserRole;
use iCoordinator\Entity\Portal;
use iCoordinator\Entity\Workspace;
use iCoordinator\Permissions\BitMask;
use iCoordinator\Permissions\PermissionType;
use iCoordinator\PHPUnit\DbUnit\DataSet\ArrayDataSet;
use iCoordinator\PHPUnit\TestCase;
use iCoordinator\Test\Helper\MockReader;

class ChargifyWebhooksTest extends TestCase
{
    const USER_ID = 1;
    const PORTAL_ID = 1;
    const USERNAME = 'user1@icoordinator.com';
    const PASSWORD = 'password';
    const PUBLIC_CLIENT_ID = 'public_test';
    const INVITATION_TOKEN1 = 'xxxx';
    const INVITATION_TOKEN2 = 'yyyy';
    const WEBSITE_ID = 'ic_test';
    const SUBSCRIPTION_ID = '10604899';

    protected function getDataSet()
    {
        $workspaceBitMask = new BitMask(Workspace::RESOURCE_ID);
        $portalBitMask = new BitMask(Portal::RESOURCE_ID);
        return new ArrayDataSet([
            'users' => array(
                array(
                    'id' => self::USER_ID,
                    'email' => self::USERNAME,
                    'name' => 'John Dow',
                    'password' => password_hash(self::PASSWORD, PASSWORD_DEFAULT),
                    'email_confirmed' => 1
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
                )
            ),
            'portals' => [
                array(
                    'id' => 1,
                    'name' => 'Test Portal 1',
                    'owned_by' => self::USER_ID
                )
            ],
            'workspaces' => [],
            'groups' => [],
            'group_memberships' => [],
            'invitations' => [],
            'acl_roles' => array(
                array(
                    'id' => 1,
                    'entity_type' => AclUserRole::ACL_ROLE_ENTITY_TYPE,
                    'entity_id' => self::USER_ID
                )
            ),
            'acl_resources' => array(
                array(
                    'id' => 1,
                    'entity_type' => AclPortalResource::ACL_RESOURCE_ENTITY_TYPE,
                    'entity_id' => self::PORTAL_ID
                )
            ),
            'acl_permissions' => [
                //portal permissions
                array(
                    'acl_role_id' => self::USER_ID,
                    'acl_resource_id' => 1,
                    'bit_mask' => $portalBitMask->getBitMask(PermissionType::PORTAL_ADMIN),
                    'is_deleted' => 0,
                    'portal_id' => self::PORTAL_ID
                )
            ],
            'licenses' => [
                [
                    'id' => 1,
                    'users_limit' => 10,
                    'workspaces_limit' => 5,
                    'storage_limit' => 5,
                    'file_size_limit' => 1
                ]
            ],
            'license_chargify_mappers' => [
                [
                    'id' => 1,
                    'license_id' => 1,
                    'chargify_website_id' => 'ic_test',
                    'chargify_product_handle' => 'business-edition-trial',
                    'chargify_users_component_ids' => '115708',
                    'chargify_workspaces_component_ids' => 2,
                    'chargify_storage_component_ids' => 3
                ],
                [
                    'id' => 2,
                    'license_id' => 1,
                    'chargify_website_id' => 'ic_test',
                    'chargify_product_handle' => 'business-edition',
                    'chargify_users_component_ids' => '115708',
                    'chargify_workspaces_component_ids' => 2,
                    'chargify_storage_component_ids' => 3
                ]
            ],
            'subscriptions' => [
                [
                    'id' => 1,
                    'portal_id' => self::PORTAL_ID,
                    'license_id' => 1,
                    'users_allocation' => 3
                ]
            ],
            'subscription_chargify_mappers' => [
                [
                    'id' => 1,
                    'subscription_id' => 1,
                    'chargify_subscription_id' => self::SUBSCRIPTION_ID
                ]
            ]
        ]);
    }

    public function testSignUpSuccessWebhook()
    {
        $body = MockReader::read('chargify-webhooks/signup-success.txt');

        $response = $this->post('/chargify/webhook', $body, [
            'Accept' => ' */*; q=0.5, application/xml',
            'X-Chargify-Webhook-Id' => '53999238',
            'Connect-Time' => '1',
            'Content-Length' => '5419',
            'X-Request-Id' => '98bfd223-6cd8-49bd-8f32-bccd436a4da8',
            'User-Agent' => 'Ruby',
            'X-Chargify-Webhook-Signature-Hmac-Sha-256' => '367bac91cee350d5586971f1fc7b0631c159c3cdfd1be5b009d431ac1f7a2c9c',
            'Accept-Encoding' => 'gzip, deflate',
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Via' => '1.1 vegur',
            'X-Chargify-Webhook-Signature' => '89460936fc012a5e58ba83816156db35'
        ]);

        $result = json_decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('user', $result->entity_type);
    }

    public function testSignUpSuccessSelfServeWebhook()
    {
        $body = MockReader::read('chargify-webhooks/signup-success-self-serve.txt');

        $response = $this->post('/chargify/webhook', $body, [
            'Accept' => ' */*; q=0.5, application/xml',
            'X-Chargify-Webhook-Id' => '53999238',
            'Connect-Time' => '1',
            'Content-Length' => '5419',
            'X-Request-Id' => '98bfd223-6cd8-49bd-8f32-bccd436a4da8',
            'User-Agent' => 'Ruby',
            'X-Chargify-Webhook-Signature-Hmac-Sha-256' => 'c31b8ee73f8ed86733d6c8d47656761f5d761aeca0c415b14f6d0e063970181a',
            'Accept-Encoding' => 'gzip, deflate',
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Via' => '1.1 vegur',
            'X-Chargify-Webhook-Signature' => 'b2e9d4e028b5fa5371691e114eb0ebf3'
        ]);

        $result = json_decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEmpty($result);
    }

    public function testSubscriptionStateChange()
    {
        $body = MockReader::read('chargify-webhooks/subscription-state-change.txt');

        $response = $this->post('/chargify/webhook', $body, [
            'Accept' => ' */*; q=0.5, application/xml',
            'X-Chargify-Webhook-Id' => '54004073',
            'Connect-Time' => '1',
            'Content-Length' => '5419',
            'X-Request-Id' => 'cbc2ea67-4607-4eb9-95d4-befac3c282b4',
            'User-Agent' => 'Ruby',
            'X-Chargify-Webhook-Signature-Hmac-Sha-256' => '1ac78b3f1375eee6ae230da73e82a60240062a13dbe6fee01dbacd82e97fdbae',
            'Accept-Encoding' => 'gzip, deflate',
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Via' => '1.1 vegur',
            'X-Chargify-Webhook-Signature' => '48f7c9dea115bbf3db5a3d277edb39bc'
        ]);

        $result = json_decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('subscription', $result->entity_type);
        $this->assertEquals('canceled', $result->state);
    }

    public function testComponentAllocationChange()
    {
        $body = MockReader::read('chargify-webhooks/component-allocation-change.txt');

        $response = $this->post('/chargify/webhook', $body, [
            'Accept' => ' */*; q=0.5, application/xml',
            'X-Chargify-Webhook-Id' => '53999115',
            'Connect-Time' => '1',
            'Content-Length' => '5419',
            'X-Request-Id' => '0f4a9ba4-88d6-4293-a0c7-e0bd3c5a56c1',
            'User-Agent' => 'Ruby',
            'X-Chargify-Webhook-Signature-Hmac-Sha-256' => 'eef22d513372a98e9009742d486605a9c5006836a8e2790956635220ec630612',
            'Accept-Encoding' => 'gzip, deflate',
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Via' => '1.1 vegur',
            'X-Chargify-Webhook-Signature' => 'ab916e7cb0f1ad5aed4da63d2b894092'
        ]);

        $result = json_decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('subscription', $result->entity_type);
        $this->assertEquals(3, $result->users_allocation);
    }

    public function testSubscriptionProductChangeWebhook()
    {
        $body = MockReader::read('chargify-webhooks/subscription-product-change.txt');

        $response = $this->post('/chargify/webhook', $body, [
            'Accept' => ' */*; q=0.5, application/xml',
            'X-Chargify-Webhook-Id' => '53999115',
            'Connect-Time' => '1',
            'Content-Length' => '5419',
            'X-Request-Id' => '0f4a9ba4-88d6-4293-a0c7-e0bd3c5a56c1',
            'User-Agent' => 'Ruby',
            'X-Chargify-Webhook-Signature-Hmac-Sha-256' => '41114d70f872b43da2ed164997f5c4b87f56ce62a614a823451b63ce7667d74d',
            'Accept-Encoding' => 'gzip, deflate',
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Via' => '1.1 vegur',
            'X-Chargify-Webhook-Signature' => '4ea3ef8ffe9bd3718bb3d202c6111702'
        ]);

        $result = json_decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('subscription', $result->entity_type);
        $this->assertEquals(3, $result->users_allocation);
    }
}
