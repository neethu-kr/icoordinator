<?php

namespace iCoordinator;

use iCoordinator\Config\Route\WorkspacesRouteConfig;
use iCoordinator\Entity\Acl\AclResource\AclPortalResource;
use iCoordinator\Entity\Acl\AclResource\AclWorkspaceResource;
use iCoordinator\Entity\Acl\AclRole\AclUserRole;
use iCoordinator\Entity\Error;
use iCoordinator\Entity\Portal;
use iCoordinator\Entity\Workspace;
use iCoordinator\Permissions\BitMask;
use iCoordinator\Permissions\PermissionType;
use iCoordinator\PHPUnit\DbUnit\DataSet\ArrayDataSet;
use iCoordinator\PHPUnit\TestCase;
use Laminas\Json\Json;

class LicenseTest extends TestCase
{
    const USER_ID = 1;
    const USER_ID2 = 2;
    const USER_ID3 = 3;
    const PORTAL_ID = 1;
    const LICENSE_ID = 1;
    const TEST_FILE_NAME = 'Document1.pdf';
    const USERNAME = 'test@icoordinator.com';
    const USERNAME2 = 'test2@icoordinator.com';
    const USERNAME3 = 'test3@icoordinator.com';
    const USERNAME4 = 'test4@icoordinator.com';
    const PASSWORD = 'password';
    const PUBLIC_CLIENT_ID = 'public_test';
    const INVITATION_TOKEN1 = 'xxxx';
    const INVITATION_TOKEN2 = 'yyyy';
    const WEBSITE_ID = 'ic_eur';

    protected function getDataSet()
    {
        $portalBitMask = new BitMask(Portal::RESOURCE_ID);
        $workspaceBitMask = new BitMask(Workspace::RESOURCE_ID);
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

                )
            ),
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
                ),
                array(
                    'id' => 2,
                    'entity_type' => AclWorkspaceResource::ACL_RESOURCE_ENTITY_TYPE,
                    'entity_id' => 1
                )
            ),
            'acl_permissions' => array(
                array(
                    'acl_role_id' => self::USER_ID,
                    'acl_resource_id' => 1,
                    'bit_mask' => $portalBitMask->getBitMask(PermissionType::PORTAL_ADMIN),
                    'portal_id' => self::PORTAL_ID
                ),
                //workspace permissions
                array(
                    'acl_role_id' => self::USER_ID,
                    'acl_resource_id' => 2,
                    'bit_mask' => $workspaceBitMask->getBitMask(PermissionType::WORKSPACE_ADMIN),
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
                    'license_id' => self::LICENSE_ID
                ]
            ],
            'subscription_chargify_mappers' => [],
            'invitations' => [
                [
                    'id' => 1,
                    'email' => 'constantine.yurevich@designtech.se',
                    'portal_id' => self::PORTAL_ID,
                    'created_by' => self::USER_ID,
                    'token' => self::INVITATION_TOKEN1
                ],
                [
                    'id' => 2,
                    'email' => self::USERNAME2,
                    'portal_id' => self::PORTAL_ID,
                    'created_by' => self::USER_ID,
                    'token' => self::INVITATION_TOKEN2
                ]
            ],
            'email_confirmations' => []
        ]);
    }

    public function testInviteNewUserExceedingLicense()
    {
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $email = 'fredrik.lindvall@designtech.se';
        $response = $this->post(
            '/invitations?disable_email_confirmation=1',
            array(
                'portal' => array(
                    'id' => self::PORTAL_ID
                ),
                'email' => $email
            ),
            $headers
        );

        $result = Json::decode($response->getBody());
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertNotEmpty($result->type);
        $this->assertEquals(Error::LICENSE_UPDATE_REQUIRED, $result->type);
    }

    public function testWorkspaceCreateExceedingLicense()
    {
        //create by portal admin
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        $response = $this->post(
            $this->urlFor(WorkspacesRouteConfig::ROUTE_WORKSPACE_ADD, array(
                'portal_id' => self::PORTAL_ID
            )),
            array(
                'name' => 'Test workspace'
            ),
            $headers
        );
        $result = Json::decode($response->getBody());

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertNotEmpty($result->type);
        $this->assertEquals(Error::LICENSE_UPDATE_REQUIRED, $result->type);
    }

    /*public function testFileUploadExceedingLicense()
    {
        //create by portal admin
        $headers = $this->getAuthorizationHeaders(self::USERNAME, self::PASSWORD, self::PUBLIC_CLIENT_ID);

        //mock environment
        $tmpName = tempnam(sys_get_temp_dir(), 'upload_');
        $settings = $this->getContainer()->get('settings');
        copy($settings['testsPath'] . '/data/files/' . self::TEST_FILE_NAME, $tmpName);
        $_FILES = array(
            'file' => array(
                'name' => self::TEST_FILE_NAME,
                'tmp_name' => $tmpName,
                'error' => UPLOAD_ERR_OK
            )
        );

        $fileName = 'Document_File_Upload_Exceeding_License.pdf';
        $response = $this->post(
            $this->urlFor(FilesRouteConfig::ROUTE_FILE_ADD, array('workspace_id' => 1)),
            array(
                'name' => $fileName,
                'parent_id' => 0
            ),
            $headers
        );

        $result = Json::decode($response->getBody());

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertNotEmpty($result->type);
        $this->assertEquals(Error::LICENSE_UPDATE_REQUIRED, $result->type);
    }*/
}