<?php

namespace iCoordinator;

use iCoordinator\Config\Route\WorkspacesRouteConfig;
use iCoordinator\Controller\AbstractRestController;
use iCoordinator\Entity\Acl\AclResource\AclPortalResource;
use iCoordinator\Entity\Acl\AclResource\AclWorkspaceResource;
use iCoordinator\Entity\Acl\AclRole\AclUserRole;
use iCoordinator\Entity\Portal;
use iCoordinator\Entity\Workspace;
use iCoordinator\Permissions\BitMask;
use iCoordinator\Permissions\PermissionType;
use iCoordinator\PHPUnit\DbUnit\DataSet\ArrayDataSet;
use iCoordinator\PHPUnit\Helper\FileHelper;
use iCoordinator\PHPUnit\TestCase;


class HeadersTest extends TestCase
{
    const USER_ID = 1;
    const USER_ID2 = 2;
    const USERNAME = 'test@user.com';
    const USERNAME2 = 'test2@user.com';
    const PASSWORD = 'password';
    const PUBLIC_CLIENT_ID = 'public_test';
    const PORTAL_ID = 1;

    public function setUp(): void
    {
        parent::setUp();

        FileHelper::initializeFileMocks($this);
    }

    public function testContentLengthHeaders()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->get(
            $this->urlFor(WorkspacesRouteConfig::ROUTE_WORKSPACE_GET, array('workspace_id' => 1)),
            array(),
            $headers
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty((string)$response->getBody());
        $this->assertTrue($response->hasHeader('Content-Length'));
        $this->assertEquals(mb_strlen((string)$response->getBody()), $response->getHeaderLine('Content-Length'));
    }

    public function testIncorrectAcceptCharsetHeaders()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);
        $headers['ACCEPT_CHARSET'] = 'java.nio.charseticu[utf-8]';

        $response = $this->get(
            $this->urlFor(WorkspacesRouteConfig::ROUTE_WORKSPACE_GET, array('workspace_id' => 1)),
            array(),
            $headers
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCORSHeaders()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);
        $headers['X-Requested-With'] = 'ajax';
        $headers['Content-Type'] = 'application/json';
        $headers['Content-Length'] = 256;
        $headers[AbstractRestController::HEADER_SHARED_LINK_TOKEN] = 'testtoken';

        $response = $this->options('/auth/token');

        $allowedHeaders = $response->getHeaderLine('Access-Control-Allow-Headers');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('X-Requested-With', $allowedHeaders);
        $this->assertContains('Content-Type', $allowedHeaders);
        $this->assertContains('Content-Length', $allowedHeaders);
        $this->assertContains(AbstractRestController::HEADER_SHARED_LINK_TOKEN, $allowedHeaders);
    }

    public function testCORSHeaders2()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);
        $headers['X-Requested-With'] = 'ajax';
        $headers['Content-Type'] = 'application/json';
        $headers['Content-Length'] = 256;

        $response = $this->options('/portals/' . self::PORTAL_ID . '/workspaces');
    }


    protected function getDataSet()
    {
        $workspaceBitMask = new BitMask(Workspace::RESOURCE_ID);
        $portalBitMask = new BitMask(Portal::RESOURCE_ID);
        return new ArrayDataSet(array(
            'oauth_clients' => array(
                array(
                    'client_id' => self::PUBLIC_CLIENT_ID
                )
            ),
            'oauth_access_tokens' => array(),
            'users' => array(
                array(
                    'id' => self::USER_ID,
                    'email' => self::USERNAME,
                    'password' => password_hash(self::PASSWORD, PASSWORD_DEFAULT),
                    'email_confirmed' => 1
                ),
                array(
                    'id' => self::USER_ID2,
                    'email' => self::USERNAME2,
                    'password' => password_hash(self::PASSWORD, PASSWORD_DEFAULT),
                    'email_confirmed' => 1
                )
            ),
            'portals' => array(
                array(
                    'id' => self::PORTAL_ID,
                    'name' => 'Test portal',
                    'owned_by' => self::USER_ID
                )
            ),
            'workspaces' => array(
                array(
                    'id' => 1,
                    'name' => 'Workspace 1',
                    'portal_id' => self::PORTAL_ID
                )
            ),
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
                    'entity_type' => AclWorkspaceResource::ACL_RESOURCE_ENTITY_TYPE,
                    'entity_id' => 1
                )
            ),
            'acl_permissions' => array(
                //workspace permissions
                array(
                    'acl_role_id' => self::USER_ID,
                    'acl_resource_id' => 1,
                    'bit_mask' => $portalBitMask->getBitMask(PermissionType::PORTAL_ADMIN),
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'acl_role_id' => self::USER_ID2,
                    'acl_resource_id' => 2,
                    'bit_mask' => $workspaceBitMask->getBitMask(PermissionType::WORKSPACE_ACCESS),
                    'portal_id' => self::PORTAL_ID
                )
            ),
            'files' => array(

            ),
            'file_versions' => array(

            ),
            'events' => array(

            ),
            'meta_fields_criteria' => array(

            )
        ));
    }
}
