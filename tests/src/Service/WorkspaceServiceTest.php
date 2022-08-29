<?php

namespace iCoordinator;

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

class WorkspaceServiceTest extends TestCase
{
    const USER_ID = 1;
    const USER_ID2 = 2;
    const USERNAME = 'test@user.com';
    const USERNAME2 = 'test2@user.com';
    const PASSWORD = 'password';
    const PUBLIC_CLIENT_ID = 'icoordinator_desktop';
    const TEST_FILE_NAME = 'Document1.pdf';
    const TEST_FILE_NAME2 = 'textfile.txt';
    const ENTRIES_FILE = 'fileEntries.json';
    const SEL_SYNC_DATA_FILE = 'selSyncData.json';
    const PORTAL_ID = 1;
    const PORTAL_ID2 = 2;
    const META_FIELD_ID = 1;
    const FOLDER_ID = 7;
    const LICENSE_ID = 1;
    const GB =  1073741824;
    const WORKSPACE_ID = 1;
    const WORKSPACE_ID2 = 2;

    public function setUp(): void
    {
        parent::setUp();

        FileHelper::initializeFileMocks($this);
        FileHelper::clearTmpStorage($this);
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
                ),
                array(
                    'id' => self::PORTAL_ID2,
                    'name' => 'Another Test portal',
                    'owned_by' => self::USER_ID
                )
            ),
            'workspaces' => array(
                array(
                    'id' => 1,
                    'name' => 'Workspace 4',
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'id' => 2,
                    'name' => 'Workspace 3',
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'id' => 3,
                    'name' => 'Workspace 2',
                    'portal_id' => self::PORTAL_ID2
                ),
                array(
                    'id' => 4,
                    'name' => 'Workspace 1',
                    'portal_id' => self::PORTAL_ID2
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
                ),
                array(
                    'id' => 6,
                    'entity_type' => AclWorkspaceResource::ACL_RESOURCE_ENTITY_TYPE,
                    'entity_id' => 4
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
                    'bit_mask' => $portalBitMask->getBitMask(PermissionType::PORTAL_ADMIN),
                    'portal_id' => self::PORTAL_ID2
                ),
                array(
                    'acl_role_id' => self::USER_ID2,
                    'acl_resource_id' => 2,
                    'bit_mask' => $portalBitMask->getBitMask(PermissionType::PORTAL_ACCESS),
                    'portal_id' => self::PORTAL_ID2
                ),
                //workspace permissions
                array(
                    'acl_role_id' => self::USER_ID,
                    'acl_resource_id' => 3,
                    'bit_mask' => $workspaceBitMask->getBitMask(PermissionType::WORKSPACE_ADMIN),
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'acl_role_id' => self::USER_ID2,
                    'acl_resource_id' => 3,
                    'bit_mask' => $workspaceBitMask->getBitMask(PermissionType::WORKSPACE_ACCESS),
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'acl_role_id' => self::USER_ID,
                    'acl_resource_id' => 4,
                    'bit_mask' => $workspaceBitMask->getBitMask(PermissionType::WORKSPACE_ADMIN),
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'acl_role_id' => self::USER_ID,
                    'acl_resource_id' => 5,
                    'bit_mask' => $workspaceBitMask->getBitMask(PermissionType::WORKSPACE_ACCESS),
                    'portal_id' => self::PORTAL_ID2
                ),
                array(
                    'acl_role_id' => self::USER_ID2,
                    'acl_resource_id' => 5,
                    'bit_mask' => $workspaceBitMask->getBitMask(PermissionType::WORKSPACE_ADMIN),
                    'portal_id' => self::PORTAL_ID2
                ),
                array(
                    'acl_role_id' => self::USER_ID,
                    'acl_resource_id' => 6,
                    'bit_mask' => $workspaceBitMask->getBitMask(PermissionType::WORKSPACE_ACCESS),
                    'portal_id' => self::PORTAL_ID2
                ),
                array(
                    'acl_role_id' => self::USER_ID2,
                    'acl_resource_id' => 6,
                    'bit_mask' => $workspaceBitMask->getBitMask(PermissionType::WORKSPACE_ADMIN),
                    'portal_id' => self::PORTAL_ID2
                )
            ),
            'files' => array(
            ),
            'licenses' => [
                [
                    'id' => self::LICENSE_ID,
                    'users_limit' => 3,
                    'workspaces_limit' => 0,
                    'storage_limit' => 5,
                    'file_size_limit' => 3
                ]
            ],
            'subscriptions' => [
                [
                    'id' => 1,
                    'portal_id' => self::PORTAL_ID,
                    'license_id' => self::LICENSE_ID,
                    'users_allocation' => 5,
                    'state' => 'removed'
                ]
            ],
            'subscription_chargify_mappers' => [],
            'file_versions' => [],
            'file_uploads' => [],
            'events' => [],
            'shared_links' => [],
            'locks' => [],
            'meta_fields' => array(
                array(
                    'id' => self::META_FIELD_ID,
                    'name' => 'Test metafield',
                    'type' => 'list',
                    'options' => "option1\noption2\noption3",
                    'portal_id' => self::PORTAL_ID
                ),
                array(
                    'id' => 2,
                    'name' => 'Test metafield 2',
                    'type' => 'number',
                    'portal_id' => self::PORTAL_ID
                )
            ),
            'meta_fields_values' => array(),
            'meta_fields_criteria' => array(),
            'history_events' => array()
        ));
    }

    public function testRemovePortalPermanently()
    {
        $portalService = $this->getContainer()->get('PortalService');
        $folder1 = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID, null);
        $folder2 = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID, $folder1);
        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, $folder1);
        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, $folder2);
        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, $folder2);
        $folder3 = FileHelper::createFolder($this->getContainer(), 1, self::USER_ID, $folder2);
        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, $folder3);
        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, $folder3);
        $file = FileHelper::createFile($this->getContainer(), 1, self::USER_ID, $folder3);
        $grantTo    = $this->getUserService()->getUser(self::USER_ID2);
        $permission = $this->getPermissionService()->addPermission(
            $folder1,
            $grantTo,
            [PermissionType::FILE_GRANT_READ],
            self::USER_ID,
            $folder1->getWorkspace()->getPortal()
        );
        $permission = $this->getPermissionService()->addPermission(
            $folder2,
            $grantTo,
            [PermissionType::FILE_GRANT_READ],
            self::USER_ID,
            $folder1->getWorkspace()->getPortal()
        );
        $permission = $this->getPermissionService()->addPermission(
            $folder3,
            $grantTo,
            [PermissionType::FILE_NONE],
            self::USER_ID,
            $folder1->getWorkspace()->getPortal()
        );
        $this->getPermissionService()->clearCache();
        $prefix = '../';
        $development = getenv('DEVELOPMENT');
        $applicationEnv = getenv("APPLICATION_ENV");
        $xdebug = "";
        if ($development == 1) {
            $prefix = '/app/';
            $xdebug = 'XDEBUG_CONFIG="idekey=removePortal remote_enable=1 ' .
                'remote_host=docker.for.mac.localhost remote_port=9000 remote_log=/tmp/xdebug.log ' .
                'remote_connect_back=0 " ';
        } elseif ($applicationEnv == "test") {
            $prefix = '';
        }
        $phpCommand = $prefix . 'bin/removePortal remove-portal 1';
        $command = $xdebug. 'nohup ' . $phpCommand;
        exec($command, $output, $return_var);
        //print_r($output);
        $this->assertEquals(15, count($output));

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
}
