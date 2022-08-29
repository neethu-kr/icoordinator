<?php

namespace iCoordinator;

use Carbon\Carbon;
use iCoordinator\Config\Route\FoldersRouteConfig;
use iCoordinator\Config\Route\InboundEmailsRouteConfig;
use iCoordinator\Config\Route\WorkspacesRouteConfig;
use iCoordinator\Entity\Acl\AclResource\AclPortalResource;
use iCoordinator\Entity\Acl\AclResource\AclWorkspaceResource;
use iCoordinator\Entity\Acl\AclRole\AclUserRole;
use iCoordinator\Entity\Error;
use iCoordinator\Entity\File;
use iCoordinator\Entity\Portal;
use iCoordinator\Entity\Workspace;
use iCoordinator\Permissions\BitMask;
use iCoordinator\Permissions\PermissionType;
use iCoordinator\PHPUnit\DbUnit\DataSet\ArrayDataSet;
use iCoordinator\PHPUnit\Helper\FileHelper;
use iCoordinator\PHPUnit\TestCase;
use Laminas\Json\Json;


class InboundEmailsTest extends TestCase
{
    const USER_ID = 1;
    const USER_ID2 = 2;
    const USERNAME = 'test@user.com';
    const USERNAME2 = 'test2@user.com';
    const PASSWORD = 'password';
    const PUBLIC_CLIENT_ID = 'public_test';
    const PORTAL_ID = 1;
    const WORKSPACE_ID = 1;

    private function decode7Bit($text) {
        // If there are no spaces on the first line, assume that the body is
        // actually base64-encoded, and decode it.
        $lines = explode("\r\n", $text);
        $first_line_words = explode(' ', $lines[0]);
        if ($first_line_words[0] == $lines[0]) {
            $text = base64_decode($text);
        }

        // Manually convert common encoded characters into their UTF-8 equivalents.
        $characters = array(
            '=20' => ' ', // space.
            '=E2=80=99' => "'", // single quote.
            '=0A' => "\r\n", // line break.
            '=A0' => ' ', // non-breaking space.
            '=C2=A0' => ' ', // non-breaking space.
            "=\r\n" => '', // joined line.
            '=E2=80=A6' => '…', // ellipsis.
            '=E2=80=A2' => '•', // bullet.
        );

        // Loop through the encoded characters and replace any that are found.
        foreach ($characters as $key => $value) {
            $text = str_replace($key, $value, $text);
        }

        return $text;
    }

    public function setUp(): void
    {
        parent::setUp();

        FileHelper::initializeFileMocks($this);
        FileHelper::clearTmpStorage($this);
    }

    public function testWorkspaceGetInboundEmail()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->get(
            $this->urlFor(WorkspacesRouteConfig::ROUTE_WORKSPACE_INBOUND_EMAIL_GET, array('workspace_id' => 1)),
            array(),
            $headers
        );
        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($result->email);
    }

    public function testFolderGetInboundEmail()
    {
        $folder = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID);

        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->get(
            $this->urlFor(FoldersRouteConfig::ROUTE_FOLDER_INBOUND_EMAIL_GET, array('folder_id' => $folder->getId())),
            array(),
            $headers
        );
        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($result->email);
    }

    public function testInboundEmailsFileUploadedToWorkspaceSuccessfully()
    {
        $c = $this->getContainer();

        $webhookMock = file_get_contents($c->get('settings')['testsPath'] . '/data/mandrill-webhooks/inbound_with_pdf_attachment.json');

        $response = $this->post(
            $this->urlFor(InboundEmailsRouteConfig::ROUTE_INBOUND_EMAIL_PROCESS),
            array(
                'mandrill_events' => $webhookMock
            )
        );


        $webhookObj = Json::decode($webhookMock, true);
        $content = base64_decode($webhookObj[0]['msg']['attachments']['=?utf-8?B?VMOkc3RmaWxlMi5kb2N4?=']['content']);
        $contentSize = strlen($content);

        $result = Json::decode($response->getBody());

        $this->assertCount(1, $result);
        $this->assertNotEmpty($result[0]->id);

        $result = $result[0];

        //check if was properly uploaded, trying to download
        $fileService = $this->getContainer()->get('FileService');
        $downloader = $fileService->getFileDownloader($result->id);

        $headers = $downloader->getHeaders();

        $this->assertEquals(200, $downloader->getStatusCode());
        $this->assertEquals($contentSize, $result->size);
        $this->assertEquals($contentSize, $headers->offsetGet('Content-Length'));
        $this->assertEquals($result->mime_type, $headers->offsetGet('Content-Type'));
    }

    public function testInboundEmailsLargeFileUploadedToWorkspaceSuccessfully()
    {
        $c = $this->getContainer();

        $webhookMock = file_get_contents($c->get('settings')['testsPath'] . '/data/mandrill-webhooks/inbound_with_large_zip_attachment.json');

        $response = $this->post(
            $this->urlFor(InboundEmailsRouteConfig::ROUTE_INBOUND_EMAIL_PROCESS),
            array(
                'mandrill_events' => $webhookMock
            )
        );


        $webhookObj = Json::decode($webhookMock, true);
        $content = base64_decode($webhookObj[0]['msg']['attachments']['ifceditor.zip']['content']);
        $contentSize = strlen($content);

        $result = Json::decode($response->getBody());

        $this->assertCount(1, $result);
        $this->assertNotEmpty($result[0]->id);

        $result = $result[0];

        //check if was properly uploaded, trying to download
        $fileService = $this->getContainer()->get('FileService');
        $downloader = $fileService->getFileDownloader($result->id);

        $headers = $downloader->getHeaders();

        $this->assertEquals(200, $downloader->getStatusCode());
        $this->assertEquals($contentSize, $result->size);
        $this->assertEquals($contentSize, $headers->offsetGet('Content-Length'));
        $this->assertEquals($result->mime_type, $headers->offsetGet('Content-Type'));
    }

    public function testInboundEmailsFileUploadedToFolderSuccessfully()
    {
        $c = $this->getContainer();

        $response = $this->post(
            $this->urlFor(InboundEmailsRouteConfig::ROUTE_INBOUND_EMAIL_PROCESS),
            array(
                'mandrill_events' => file_get_contents($c->get('settings')['testsPath'] . '/data/mandrill-webhooks/inbound_with_pdf_attachment2.json')
            )
        );

        $result = Json::decode($response->getBody());

        $this->assertCount(1, $result);
        $this->assertNotEmpty($result[0]->id);

        $result = $result[0];

        //check if was properly uploaded, trying to download
        $fileService = $this->getContainer()->get('FileService');
        $downloader = $fileService->getFileDownloader($result->id);

        $headers = $downloader->getHeaders();

        $this->assertEquals(200, $downloader->getStatusCode());
        $this->assertEquals($result->size, $headers->offsetGet('Content-Length'));
        $this->assertEquals($result->mime_type, $headers->offsetGet('Content-Type'));
    }

    public function testInboundEmailsFileVersionUploadedToFolderSuccessfully()
    {
        $c = $this->getContainer();

        $folder = $this->getContainer()->get('FolderService')->getFolder(1);
        FileHelper::createFile($this->getContainer(), self::WORKSPACE_ID, self::USER_ID, $folder, 'Document1.pdf');

        $response = $this->post(
            $this->urlFor(InboundEmailsRouteConfig::ROUTE_INBOUND_EMAIL_PROCESS),
            array(
                'mandrill_events' => file_get_contents($c->get('settings')['testsPath'] . '/data/mandrill-webhooks/inbound_with_pdf_attachment2.json')
            )
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertCount(1, $result);
        $this->assertNotEmpty($result[0]->id);

        $result = $result[0];

        //check if was properly uploaded, trying to download
        $fileService = $this->getContainer()->get('FileService');
        $downloader = $fileService->getFileDownloader($result->id);

        $headers = $downloader->getHeaders();

        $this->assertEquals(200, $downloader->getStatusCode());
        $this->assertEquals($result->size, $headers->offsetGet('Content-Length'));
        $this->assertEquals($result->mime_type, $headers->offsetGet('Content-Type'));
    }

    public function testInboundEmailsInternalServerError()
    {
        $c = $this->getContainer();

        $response = $this->post(
            $this->urlFor(InboundEmailsRouteConfig::ROUTE_INBOUND_EMAIL_PROCESS),
            array(
                'mandrill_events' => file_get_contents($c->get('settings')['testsPath'] . '/data/mandrill-webhooks/inbound_with_wrong_format.json')
            )
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(Error::VALIDATION_FAILED, $result->type);
    }

    public function testInboundEmailsWrongEmailError()
    {
        $c = $this->getContainer();

        $response = $this->post(
            $this->urlFor(InboundEmailsRouteConfig::ROUTE_INBOUND_EMAIL_PROCESS),
            array(
                'mandrill_events' => file_get_contents($c->get('settings')['testsPath'] . '/data/mandrill-webhooks/inbound_with_wrong_email.json')
            )
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(Error::VALIDATION_FAILED, $result->type);
    }

    public function testInboundEmailsFileUploadedToTrashedFolder()
    {
        $c = $this->getContainer();
        $folder = $this->getContainer()->get('FolderService')->getFolder(2);
        $folder->setIsTrashed(1);
        $this->getEntityManager()->merge($folder);
        $this->getEntityManager()->flush();
        $response = $this->post(
            $this->urlFor(InboundEmailsRouteConfig::ROUTE_INBOUND_EMAIL_PROCESS),
            array(
                'mandrill_events' => file_get_contents($c->get('settings')['testsPath'] . '/data/mandrill-webhooks/inbound_with_pdf_attachment3.json')
            )
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    protected function getDataSet()
    {
        $fileBitMask = new BitMask(File::RESOURCE_ID);
        $workspaceBitMask = new BitMask(Workspace::RESOURCE_ID);
        $portalBitMask = new BitMask(Portal::RESOURCE_ID);
        return new ArrayDataSet(array(
            'oauth_clients' => array(
                array(
                    'client_id' => self::PUBLIC_CLIENT_ID
                ),
                array(
                    'client_id' => 'icoordinator_desktop'
                )
            ),
            'oauth_access_tokens' => array(),
            'users' => array(
                array(
                    'id' => self::USER_ID,
                    'name' => 'Test User',
                    'email' => self::USERNAME,
                    'password' => password_hash(self::PASSWORD, PASSWORD_DEFAULT),
                    'uuid' => '067e6162-3b6f-4ae2-a171-2470b63dff00',
                    'email_confirmed' => 1
                ),
                array(
                    'id' => self::USER_ID2,
                    'email' => self::USERNAME2,
                    'password' => password_hash(self::PASSWORD, PASSWORD_DEFAULT),
                    'uuid' => '54947df8-0e9e-4471-a2f9-9af509fb5889',
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
                    'id' => self::WORKSPACE_ID,
                    'name' => 'Workspace 1',
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'id' => 2,
                    'name' => 'Workspace 2',
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
                ),
                array(
                    'acl_role_id' => self::USER_ID,
                    'acl_resource_id' => 2,
                    'bit_mask' => $workspaceBitMask->getBitMask(PermissionType::WORKSPACE_ADMIN),
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'user_id' => self::USER_ID,
                    'resource_type' => File::RESOURCE_ID,
                    'resource_id' => 7,
                    'bit_mask' => $fileBitMask->getBitMask(array(
                        PermissionType::FILE_READ,
                        PermissionType::FILE_EDIT,
                        PermissionType::FILE_GRANT_READ,
                        PermissionType::FILE_GRANT_EDIT
                    )),
                    'portal_id' => self::PORTAL_ID
                ),
            ),
            'files' => array(
                array(
                    'id' => 1,
                    'type' => 'folder',
                    'name' => 'Folder 1',
                    'workspace_id' => self::WORKSPACE_ID,
                    'size' => 0,
                    'etag' => 1,
                    'created_by' => self::USER_ID,
                    'owned_by' => self::USER_ID,
                    'content_created_at' => Carbon::now()->toDateTimeString(),
                    'content_modified_at' => Carbon::now()->toDateTimeString()
                ),
                array(
                    'id' => 2,
                    'type' => 'folder',
                    'name' => 'Folder 2',
                    'workspace_id' => self::WORKSPACE_ID,
                    'size' => 0,
                    'etag' => 1,
                    'created_by' => self::USER_ID,
                    'owned_by' => self::USER_ID,
                    'content_created_at' => Carbon::now()->toDateTimeString(),
                    'content_modified_at' => Carbon::now()->toDateTimeString(),
                    'is_trashed' => 1
                )
            ),
            'file_versions' => array(),
            'events' => array(),
            'history_events' => array()
        ));
    }
}
