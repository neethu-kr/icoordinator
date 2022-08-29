<?php

namespace iCoordinator;

use iCoordinator\Entity\Acl\AclResource\AclPortalResource;
use iCoordinator\Entity\Acl\AclResource\AclWorkspaceResource;
use iCoordinator\Entity\Acl\AclRole\AclUserRole;
use iCoordinator\Entity\File;
use iCoordinator\Entity\Portal;
use iCoordinator\Entity\RealTimeServer;
use iCoordinator\Entity\Workspace;
use iCoordinator\Permissions\BitMask;
use iCoordinator\Permissions\PermissionType;
use iCoordinator\PHPUnit\DbUnit\DataSet\ArrayDataSet;
use iCoordinator\PHPUnit\Helper\FileHelper;
use iCoordinator\PHPUnit\TestCase;
use iCoordinator\Service\EventService;
use Predis\Client;
use Predis\PubSub\Consumer;
use Predis\PubSub\DispatcherLoop;


class EventServiceTest extends TestCase
{
    const USER_ID = 1;
    const USER_ID2 = 2;
    const USERNAME = 'test@user.com';
    const USERNAME2 = 'test2@user.com';
    const PASSWORD = 'password';
    const PUBLIC_CLIENT_ID = 'public_test';
    const TEST_FILE_NAME = 'Document1.pdf';
    const PORTAL_ID = 1;

    protected function getDataSet()
    {
        $fileBitMask = new BitMask(File::RESOURCE_ID);
        $workspaceBitMask = new BitMask(Workspace::RESOURCE_ID);
        $portalBitMask = new BitMask(Portal::RESOURCE_ID);
        return new ArrayDataSet(array(
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
            'acl_resources' => [
                array(
                    'id' => 1,
                    'entity_type' => AclPortalResource::ACL_RESOURCE_ENTITY_TYPE,
                    'entity_id' => self::PORTAL_ID
                ),
                array(
                    'id' => 2,
                    'entity_type' => AclWorkspaceResource::ACL_RESOURCE_ENTITY_TYPE,
                    'entity_id' => 1
                ),
                array(
                    'id' => 3,
                    'entity_type' => AclWorkspaceResource::ACL_RESOURCE_ENTITY_TYPE,
                    'entity_id' => 2
                ),
            ],
            'acl_permissions' => [
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
                ),
            ],
            'files' => array(

            ),
            'events' => array(

            )
        ));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->getRedis()->flushall();

        FileHelper::initializeFileMocks($this);
    }

    public function testGetRealTimeServer()
    {
        $eventService = $this->getEventService();

        $realTimeServer = $eventService->getRealTimeServer(self::USER_ID);

        $this->assertInstanceOf(RealTimeServer::class, $realTimeServer);
        $this->assertCount(1, $eventService->getUserChannels(self::USER_ID));
    }

    public function testCreateFileEventWithActiveChannel()
    {
        $eventService = $this->getEventService();

        $realTimeServer = $eventService->getRealTimeServer(self::USER_ID);

        $parts = explode('/', $realTimeServer->getUrl());
        $token = array_pop($parts);

        $redisSettings = $this->getContainer()->get('settings')['redis'];

        $producer = $this->getRedis();
        $consumer = new Client($redisSettings);

        $producer->connect();
        $consumer->connect();

        $pubsub = new Consumer($consumer);
        $dispatcher = new DispatcherLoop($pubsub);

        $callback = $this->getMock('stdClass', array('__invoke'));
        $callback->expects($this->exactly(1))
            ->method('__invoke')
            ->with($this->equalTo(EventService::NEW_EVENT_CHANNEL_MESSAGE))
            ->will($this->returnCallback(function ($arg) use ($dispatcher) {
                $dispatcher->stop();
            }));

        $dispatcher->attachCallback($token, $callback);

        FileHelper::createFile($this->getContainer(), 1, self::USER_ID);

        $dispatcher->run();

        $this->assertEquals('PONG', $consumer->ping());
    }

    /**
     * @return EventService
     */
    private function getEventService()
    {
        return $this->getContainer()->get('EventService');
    }

    /**
     * @return \Predis\Client
     */
    private function getRedis()
    {
        return $this->getContainer()->get('redis');
    }
}
