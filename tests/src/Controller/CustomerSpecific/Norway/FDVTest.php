<?php

namespace iCoordinator;

use Carbon\Carbon;
use Datetime;
use iCoordinator\Config\Route\CustomerSpecific\Norway\FDVRouteConfig;
use iCoordinator\Entity\Acl\AclResource\AclPortalResource;
use iCoordinator\Entity\Acl\AclResource\AclWorkspaceResource;
use iCoordinator\Entity\Acl\AclRole\AclUserRole;
use iCoordinator\Entity\File;
use iCoordinator\Entity\Portal;
use iCoordinator\Entity\Workspace;
use iCoordinator\Permissions\BitMask;
use iCoordinator\Permissions\PermissionType;
use iCoordinator\PHPUnit\DbUnit\DataSet\ArrayDataSet;
use iCoordinator\PHPUnit\Helper\FileHelper;
use iCoordinator\PHPUnit\TestCase;
use Laminas\Json\Json;


class FDVTest extends TestCase
{
    const USER_ID = 1;
    const USER_ID2 = 2;
    const USERNAME = 'test@user.com';
    const USERNAME2 = 'test2@user.com';
    const PASSWORD = 'password';
    const PUBLIC_CLIENT_ID = 'public_test';
    const PORTAL_ID = 1;
    const WORKSPACE_ID = 1;

    public function setUp(): void
    {
        parent::setUp();
        FileHelper::initializeFileMocks($this);
    }


    public function testGetFDVEntriesForPortal()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->get(
            $this->urlFor(FDVRouteConfig::ROUTE_ENTRIES_GET) . '?limit=10',
            array(
                'portal' => 1
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(1, $result->entries);
        $this->assertFalse($result->has_more);
    }

    public function testAddFDVEntry()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->post(
            $this->urlFor(FDVRouteConfig::ROUTE_ENTRY_ADD),
            array(
                'portal' => self::PORTAL_ID,
                'workspace' => self::WORKSPACE_ID,
                'selskapsnr' => '2',
                'selskapsnavn' => 'selskapsnavn',
                'gnrbnr' => '4343',
                'eiendomnavn' => 'eiendomnavn',
                'bygningsnr' => '4567',
                'bygning' => 'bygning',
                'bygningsdel' => 'bygningsdel',
                'systemnavn' => 'systemnavn',
                'systemtypenr' => '77',
                'komponentnr' => '98',
                'komponentnavn' => 'komponentnavn',
                'komponenttypenr' => '5345',
                'komponentkategorinr' => '12',
                'fabrikat' => 'fabrikat',
                'typebetegnelse' => 'tb8989',
                'systemleverandor' => 'systemleverandor',
                'installdato' => Carbon::now()->format(DateTime::ISO8601),
                'garanti' => Carbon::now()->format(DateTime::ISO8601),
                'notat' => 'notat',
                'antal_service_per_ar' => 1,
                'tfm' => 'tfm'
            ),
            $headers
        );
        $this->assertEquals(201, $response->getStatusCode());

    }

    public function testGetLicenseForPortal()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->get(
            $this->urlFor(FDVRouteConfig::ROUTE_PORTAL_LICENSE_GET, array('portal_id' => 1)),
            array(),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotNull($result->license);
    }

    public function testUpdateFDVEntry()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->put(
            $this->urlFor(FDVRouteConfig::ROUTE_ENTRY_UPDATE, array('fdv_id' => 1)),
            array(
                'workspace' => 2,
                'selskapsnr' => '11'
            ),
            $headers
        );
        $this->assertEquals(200, $response->getStatusCode());

    }

    public function testExportFDVEntries() {

        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->get(
            $this->urlFor(FDVRouteConfig::ROUTE_PORTAL_EXPORT_GET, array('portal_id' => 1)),
            array(),
            $headers
        );

        $bodyContent = $response->getBody();

        $headers = $response->getHeaders();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('Content-Disposition', $headers);
        $this->assertArrayHasKey('Content-Length', $headers);
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertContains('attachment', $response->getHeaderLine('Content-Disposition'));

    }
    protected function tearDown(): void
    {
        parent::tearDown();

        FileHelper::clearTmpStorage($this);
    }

    protected function getDataSet()
    {
        $fileBitMask = new BitMask(File::RESOURCE_ID);
        $workspaceBitMask = new BitMask(Workspace::RESOURCE_ID);
        $portalBitMask = new BitMask(Portal::RESOURCE_ID);
        return new ArrayDataSet(array(
            'oauth_clients' => array(
                array(
                    'client_id' => self::PUBLIC_CLIENT_ID,
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
            'portals' => [
                [
                    'id' => self::PORTAL_ID,
                    'name' => 'Test portal',
                    'owned_by' => self::USER_ID
                ]
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
                )
            ),
            'acl_resources' => array(
                array(
                    'id' => 1,
                    'entity_id' => self::PORTAL_ID,
                    'entity_type' => AclPortalResource::ACL_RESOURCE_ENTITY_TYPE
                ),
                array(
                    'id' => 2,
                    'entity_id' => 1,
                    'entity_type' => AclWorkspaceResource::ACL_RESOURCE_ENTITY_TYPE
                ),
            ),
            'acl_roles' => array(
                array(
                    'id' => 1,
                    'entity_id' => self::USER_ID,
                    'entity_type' => AclUserRole::ACL_ROLE_ENTITY_TYPE
                ),
                array(
                    'id' => 2,
                    'entity_id' => self::USER_ID2,
                    'entity_type' => AclUserRole::ACL_ROLE_ENTITY_TYPE
                )
            ),
            'acl_permissions' => array(
                //workspace permissions
                array(
                    'acl_resource_id' => 1,
                    'acl_role_id' => 1,
                    'bit_mask' => $portalBitMask->getBitMask(PermissionType::PORTAL_ADMIN),
                    'portal_id' => 1
                ),
                array(
                    'acl_resource_id' => 1,
                    'acl_role_id' => 2,
                    'bit_mask' => $portalBitMask->getBitMask(PermissionType::PORTAL_ACCESS),
                    'portal_id' => 1
                ),
                array(
                    'acl_role_id' => 1,
                    'acl_resource_id' => 2,
                    'bit_mask' => $workspaceBitMask->getBitMask(PermissionType::WORKSPACE_ADMIN),
                    'portal_id' => 1
                ),
                array(
                    'acl_role_id' => 2,
                    'acl_resource_id' => 2,
                    'bit_mask' => $workspaceBitMask->getBitMask(PermissionType::WORKSPACE_ACCESS),
                    'portal_id' => 1
                )
            ),
            'files' => array(

            ),
            'file_versions' => array(

            ),
            'file_email_options' => array(

            ),
            'events' => array(

            ),
            'meta_fields_criteria' => array(

            ),
            'locks' => array(),
            'fdv_entries' => array(
                array(
                    'id' => 1,
                    'portal_id' => self::PORTAL_ID,
                    'workspace_id' => self::WORKSPACE_ID,
                    'created_by' => self::USER_ID,
                    'selskapsnr' => '1',
                    'selskapsnavn' => 'selskapsnavn',
                    'gnrbnr' => '1212',
                    'eiendomnavn' => 'eiendomnavn',
                    'bygningsnr' => '1234',
                    'bygning' => 'bygning',
                    'bygningsdel' => 'bygningsdel',
                    'systemnavn' => 'systemnavn',
                    'systemtypenr' => '55',
                    'komponentnr' => '67',
                    'komponentnavn' => 'komponentnavn',
                    'komponenttypenr' => '9876',
                    'komponentkategorinr' => '43',
                    'fabrikat' => 'fabrikat',
                    'typebetegnelse' => 'tb5412',
                    'systemleverandor' => 'systemleverandor',
                    'installdato' => Carbon::now()->toDateTimeString(),
                    'garanti' => Carbon::now()->toDateTimeString(),
                    'notat' => 'notat',
                    'antal_service_per_ar' => 3,
                    'tfm' => 'tfm'
                )
            ),
            'fdv_licenses' => array(
                array(
                    'id' => 1,
                    'portal_id' => self::PORTAL_ID
                )
            )
        ));
    }
}