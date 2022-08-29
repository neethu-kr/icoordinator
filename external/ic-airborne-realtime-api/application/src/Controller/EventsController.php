<?php

namespace iCoordinator\Controller;

use iCoordinator\Entity\Acl\AclPermission;
use iCoordinator\Entity\File;
use iCoordinator\Entity\Workspace;
use iCoordinator\Permissions\Resource\Factory\ResourceFactory;
use iCoordinator\Permissions\Resource\FileResource;
use iCoordinator\Service\EventService;
use Slim\Http\Request;
use Slim\Http\Response;

class EventsController extends AbstractRestController
{
    public function getEventsForObjectAction(Request $request, Response $response, $args)
    {
        $auth = $this->getAuth();

        $sourceId = $request->getParam('source_id', 0);
        $sourceType = $request->getParam('source_type', '');
        $cursorPosition = $request->getParam('cursor_position', 0);
        $limit = $request->getParam('limit', 100);

        $eventService = $this->getEventService();

        $hasMore = true;
        $hydratedEntries = array();

        $userId = $auth->getIdentity();
        $entries = $eventService->getUserEventsForObject($userId, $sourceId, $sourceType, $cursorPosition, $limit);
        if (count($entries) < $limit) {
            $hasMore = false;
        }
        if (count($entries)) {
            $lastEvent = $entries[count($entries) - 1];
            $cursorPosition = $lastEvent["id"];
            foreach ($entries as $index => $entry) {
                $source = $eventService->getAnyReference(File::ENTITY_NAME, $entry['source_id']);
                $entry["source"] = $source;
                array_push($hydratedEntries, $entry);
            }
        }
        $result = array(
            'has_more' => $hasMore,
            'next_cursor_position' => (int) $cursorPosition,
            'entries' => $entries
        );

        return $response->withJson($result);
    }

    public function getHistoryAction(Request $request, Response $response, $args)
    {
        $auth = $this->getAuth();

        $limit = $request->getParam('limit', 100);

        $eventService = $this->getEventService();

        $hasMore = true;
        $entries = array();

        $paginator = $eventService->getUserHistory($auth->getIdentity(), $limit);

        if ($paginator->count() <= $limit) {
            $hasMore = false;
        }

        $entries = $paginator->getIterator()->getArrayCopy();

        $result = array(
            'has_more' => $hasMore,
            'entries' => $entries
        );

        return $response->withJson($result);
    }

    public function getEventsAction(Request $request, Response $response, $args)
    {
        $auth = $this->getAuth();

        $cursorPosition = $request->getParam('cursor_position', 0);
        $limit = $request->getParam('limit', 100);

        $eventService = $this->getEventService();

        $hasMore = true;
        $entries = array();
        $selSyncData = null;
        $serverParams = $request->getServerParams();
        $token = str_replace("Bearer ", "", $serverParams['HTTP_AUTHORIZATION']);
        $accessToken = $this->getContainer()['entityManager']
            ->getRepository('\iCoordinator\Entity\OAuthAccessToken')
            ->findOneBy(
                array(
                    'accessToken' => $token
                )
            );
        $client_id = $accessToken->getClientId();
        if ($cursorPosition < 2) {
            $cursorPosition = $eventService->getCursorPosition();
            $hasMore = false;
        } else {
            $userId = $auth->getIdentity();
            if ($client_id == 'icoordinator_desktop') {
                $selSyncData = $this->getSelectiveSyncService()->getSelectiveSyncFileIds($userId);
                $hydrate = ($selSyncData == null);
            } else {
                $hydrate = true;
            }
            $entries = $eventService->getUserEvents($userId, $cursorPosition, $limit, $hydrate);

            if (count($entries) < $limit) {
                $hasMore = false;
            }
            if (count($entries)) {
                $lastEvent = $entries[count($entries) - 1];
                $cursorPosition = $hydrate ? $lastEvent->getId() : $lastEvent["id"];
            }

            if ($client_id == 'icoordinator_desktop') {
                $selectedEntries = array();
                if ($selSyncData != null) {
                    foreach ($entries as $index => $entry) {
                        if ($entry['source_type'] == 'file' || $entry['source_type'] == 'folder') {
                            $source = $eventService->getAnyReference(File::ENTITY_NAME, $entry['source_id']);
                            if ($entry['type'] != 'FILE_SELECTIVESYNC_CREATE' &&
                                $entry['type'] != 'FOLDER_SELECTIVESYNC_CREATE'
                            ) {
                                if ($this->getSelectiveSyncService()->getSelectiveSync(
                                    $entry['source_id'],
                                    $userId,
                                    false,
                                    $selSyncData
                                )) {
                                } else {
                                    $entry['source'] = $source;
                                    array_push($selectedEntries, $entry);
                                }
                            } else {
                                $entry['source'] = $source;
                                array_push($selectedEntries, $entry);
                            }
                        } elseif ($entry['source_type'] == 'permission') {
                            $source = $eventService->getAnyReference(
                                AclPermission::getEntityName(),
                                $entry['source_id']
                            );
                            $type = $source->getAclResource()->getAclResourceEntityType();
                            if ($type == 'file' || $type == 'folder') {
                                $permissionSource = $source->getAclResource()->getResource();
                                if ($this->getSelectiveSyncService()->getSelectiveSync(
                                    $permissionSource,
                                    $userId,
                                    false,
                                    $selSyncData
                                )) {
                                } else {
                                    $entry['source'] = $source;
                                    array_push($selectedEntries, $entry);
                                }
                            } else {
                                $entry['source'] = $source;
                                array_push($selectedEntries, $entry);
                            }
                        } elseif ($entry['source_type'] == 'workspace') {
                            $entry['source'] = $eventService->getAnyReference(
                                Workspace::ENTITY_NAME,
                                $entry['source_id']
                            );
                            array_push($selectedEntries, $entry);
                        }
                    }
                    $entries = $selectedEntries;
                }
            }
        }

        $result = array(
            'has_more' => $hasMore,
            'next_cursor_position' => (int) $cursorPosition,
            'entries' => $entries
        );

        return $response->withJson($result);
    }

    public function getEventsRealTimeServerAction(Request $request, Response $response, $args)
    {
        $realTimeServer = $this->getEventService()->getRealTimeServer($this->getAuth()->getIdentity());
        return $response->withJson($realTimeServer);
    }

    /**
     * @return EventService
     */
    public function getEventService()
    {
        return $eventService = $this->getContainer()->get('EventService');
    }

    /**
     * @return SelectiveSyncService
     */
    private function getSelectiveSyncService()
    {
        return $this->getContainer()->get('SelectiveSyncService');
    }
}
