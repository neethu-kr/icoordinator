<?php

namespace iCoordinator;

use iCoordinator\Config\Route\PortalsRouteConfig;
use iCoordinator\Entity\Acl\AclResource\AclPortalResource;
use iCoordinator\Entity\Acl\AclRole\AclUserRole;
use iCoordinator\Entity\Portal;
use iCoordinator\Entity\Subscription;
use iCoordinator\Permissions\BitMask;
use iCoordinator\Permissions\PermissionType;
use iCoordinator\PHPUnit\DbUnit\DataSet\ArrayDataSet;
use iCoordinator\PHPUnit\TestCase;
use Rhumsaa\Uuid\Uuid;

class PortalsTest extends TestCase
{
    const USER_ID = 1;
    const USER_ID2 = 2;
    const USER_ID3 = 3;
    const PORTAL_ID = 1;
    const PORTAL_ID2 = 2;
    const LICENSE_ID = 1;
    const USERNAME = 'test@icoordinator.com';
    const USERNAME2 = 'test2@icoordinator.com';
    const USERNAME3 = 'test3@icoordinator.com';
    const PASSWORD = 'password';
    const PUBLIC_CLIENT_ID = 'public_test';

    public function testCreatePortal()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->post(
            $this->urlFor(PortalsRouteConfig::ROUTE_PORTAL_CREATE),
            array(
                'name' => 'Test Portal 3'
            ),
            $headers
        );

        $result = json_decode($response->getBody());

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('Test Portal 3', $result->name);
    }

    public function testPortalsGetList()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        //getting with access
        $response = $this->get(
            $this->urlFor(PortalsRouteConfig::ROUTE_PORTALS_LIST),
            array(
                'state' => 1,
                'slim_state' => 1
            ),
            $headers
        );
        $result = json_decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(2, $result);
        //var_dump($result);
        $this->assertEquals(Subscription::STATE_TRIAL_ENDED,$result[0]->subscription->state);
    }

    public function testPortalsGetAllowedClients()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        //getting with access
        $response = $this->get(
            $this->urlFor(PortalsRouteConfig::ROUTE_PORTAL_ALLOWED_CLIENTS_LIST,array('portal_id' => self::PORTAL_ID)),
            array(),
            $headers
        );
        $result = json_decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(2, $result);
    }

    public function testPortalsSetAllowedClients()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->post(
            $this->urlFor(PortalsRouteConfig::ROUTE_PORTAL_ALLOWED_CLIENTS_ADD,array('portal_id' => self::PORTAL_ID)),
            array(
                'allowed_clients' => array(
                    array(
                        'user' => self::USER_ID2,
                        'mobile' => true,
                        'desktop' => true
                    ),
                    array(
                        'user' => self::USER_ID,
                        'mobile' => true,
                        'desktop' => true
                    )
                )
            ),
            $headers
        );

        $result = json_decode($response->getBody());

        $this->assertEquals(201, $response->getStatusCode());
    }

    public function testPortalsUpdateAllowedClients()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->put(
            $this->urlFor(PortalsRouteConfig::ROUTE_PORTAL_ALLOWED_CLIENTS_UPDATE,
                array(
                    'portal_id' => self::PORTAL_ID,
                    'allowed_client_id' => 1
                )
            ),
            array(
                'desktop' => false
            ),
            $headers
        );

        $result = json_decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(false, $result->desktop);
    }

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    protected function getDataSet()
    {
        $uuid1 = Uuid::uuid4()->toString();
        $portalBitMask = new BitMask(Portal::RESOURCE_ID);
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
                    'name' => 'John Dow',
                    'password' => password_hash(self::PASSWORD, PASSWORD_DEFAULT),
                    'email_confirmed' => 1,
                    'uuid' => $uuid1
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
            'portals' => array(
                array(
                    'id' => 1,
                    'name' => 'Test Portal',
                    'owned_by' => self::USER_ID
                ),
                array(
                    'id' => 2,
                    'name' => 'Test Portal 2',
                    'owned_by' => self::USER_ID2
                )
            ),
            'email_confirmations' => array(),
            'invitations' => array(),
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
                )
            ),
            'acl_permissions' => array(
                array(
                    'acl_role_id' => self::USER_ID,
                    'acl_resource_id' => 1,
                    'bit_mask' => $portalBitMask->getBitMask(PermissionType::PORTAL_ADMIN),
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'acl_role_id' => self::USER_ID2,
                    'acl_resource_id' => 1,
                    'bit_mask' => $portalBitMask->getBitMask(PermissionType::PORTAL_ACCESS),
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'acl_role_id' => self::USER_ID,
                    'acl_resource_id' => 2,
                    'bit_mask' => $portalBitMask->getBitMask(PermissionType::PORTAL_ACCESS),
                    'portal_id' => self::PORTAL_ID2
                )
            ),
            'workspaces' => array(),
            'files' => array(),
            'events' => array(),
            'licenses' => [
                [
                    'id' => self::LICENSE_ID,
                    'users_limit' => 3,
                    'workspaces_limit' => 0,
                    'storage_limit' => 0
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
            'subscriptions' => [
                [
                    'id' => 1,
                    'portal_id' => self::PORTAL_ID,
                    'license_id' => self::LICENSE_ID,
                    'state' => Subscription::STATE_TRIAL_ENDED
                ]
            ],
            'portal_allowed_clients' => [
                array(
                    'id' => 1,
                    'uuid' => $uuid1,
                    'portal_id' => self::PORTAL_ID,
                    'user_id' => self::USER_ID,
                    'mobile' => false,
                    'desktop' => true
                )
            ]
        ));
    }
}
