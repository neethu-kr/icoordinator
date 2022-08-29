<?php

namespace iCoordinator\Service;

use Carbon\Carbon;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use iCoordinator\Entity\Acl\AclPermission;
use iCoordinator\Entity\Acl\AclRole\AclGroupRole;
use iCoordinator\Entity\Acl\AclRole\AclUserRole;
use iCoordinator\Entity\Event;
use iCoordinator\Entity\Event\FileEvent;
use iCoordinator\Entity\Event\FolderEvent;
use iCoordinator\Entity\File;
use iCoordinator\Entity\Portal;
use iCoordinator\Entity\RealTimeServer;
use iCoordinator\Entity\User;
use iCoordinator\Entity\Workspace;
use iCoordinator\Service\Exception\ValidationFailedException;
use iCoordinator\Service\Helper\TokenHelper;
use iCoordinator\Service\Helper\UrlHelper;

class EventService extends AbstractService
{
    const NEW_EVENT_CHANNEL_MESSAGE = 'new_event';

    /**
     * @var FileService
     */
    private $fileService;

    /**
     * @var WorkspaceService
     */
    private $workspaceService;

    /**
     * @var PortalService
     */
    private $portalService;

    /**
     * @var GroupService
     */
    private $groupService;

    /**
     * @var PermissionService
     */
    private $permissionService;

    /**
     * @var HistoryEventService
     */
    private $historyEventService;

    public function getUserHistory($user, $limit = 100)
    {
        if (is_numeric($user)) {
            $userId = $user;
            /** @var User $user */
            $user = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select(array('e'))
            ->from(Event::ENTITY_NAME, 'e')
            ->andWhere('e.user = :user')
            ->setParameter('user', $user)
            ->orderBy('e.created_at', 'DESC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        $query = $qb->getQuery();
        $paginator = new Paginator($query, false);

        return $paginator;
    }

    public function getAnyReference($entityName, $id)
    {
        return $this->getEntityManager()->getReference($entityName, $id);
    }

    public function getUserEventsForObject($user, $sourceId, $sourceType, $cursorPosition = 0, $limit = 100)
    {
        if (is_numeric($user)) {
            $userId = $user;
            /** @var User $user */
            $user = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        $em = $this->getEntityManager();
        $sql = "SELECT e.* FROM events e where id > ".
            $cursorPosition . " AND user_id=" . $user->getId() .
            " AND source_id=" . $sourceId . " AND source_type=\"" . $sourceType . "\"" .
            " ORDER BY id ASC";
        if ($limit !== null) {
            $sql .= " LIMIT " . $limit;
        }
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();

        /*
           $qb = $this->getEntityManager()->createQueryBuilder();
           $qb->select(array('e'))
            ->from(Event::ENTITY_NAME, 'e')
            ->andWhere('e.id > :cursorPosition')
            ->setParameter('cursorPosition', $cursorPosition)
            ->andWhere('e.user = :user')
            ->setParameter('user', $user)
            ->andWhere('e.source = :sourceId')
            ->setParameter('sourceId', $sourceId)
            ->andWhere('e.source_type = :sourceType')
            ->setParameter('sourceType', $sourceType)
            ->orderBy('e.id', 'ASC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        $query = $qb->getQuery();
        $paginator = new Paginator($query, false);

        return $paginator->getIterator()->getArrayCopy();*/
    }

    public function getUserEvents($user, $cursorPosition = 0, $limit = 100, $hydrate = true)
    {
        if (is_numeric($user)) {
            $userId = $user;
            /** @var User $user */
            $user = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        if ($hydrate) {
            $qb = $this->getEntityManager()->createQueryBuilder();

            $qb->select(array('e'))
                ->from(Event::ENTITY_NAME, 'e')
                ->andWhere('e.id > :cursorPosition')
                ->setParameter('cursorPosition', $cursorPosition)
                ->andWhere('e.user = :user')
                ->setParameter('user', $user)
                ->orderBy('e.id', 'ASC');

            if ($limit !== null) {
                $qb->setMaxResults($limit);
            }

            $query = $qb->getQuery();
            $paginator = new Paginator($query, false);

            return $paginator->getIterator()->getArrayCopy();
        } else {
            $em = $this->getEntityManager();
            $sql = "SELECT e.* FROM events e where id > ".
                $cursorPosition . " AND user_id=" . $user->getId() . " ORDER BY id ASC";
            if ($limit !== null) {
                $sql .= " LIMIT " . $limit;
            }
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        }
    }

    /**
     * @param $type
     * @param $source
     * @param $createdBy
     * @param null $userIds
     * @return Event\FileEvent|Event\FolderEvent|Event\PermissionEvent|Event\WorkspaceEvent
     * @throws ValidationFailedException
     * @throws \Doctrine\ORM\ORMException
     */
    public function addEvent($type, $source, $createdBy, $userIds = null, $historyEvent = true, $newName = null)
    {
        switch (true) {
            case (in_array($type, Event\PortalEvent::getEventTypes())):
                $event = new Event\PortalEvent();
                break;

            case (in_array($type, Event\WorkspaceEvent::getEventTypes())):
                $event = new Event\WorkspaceEvent();
                break;

            case (in_array($type, Event\FileEvent::getEventTypes())):
                $event = new Event\FileEvent();
                if ($type == FileEvent::TYPE_SELECTIVESYNC_CREATE || $type == FileEvent::TYPE_SELECTIVESYNC_DELETE) {
                    $userIds = array($createdBy->getId());
                }
                break;

            case (in_array($type, Event\FolderEvent::getEventTypes())):
                $event = new Event\FolderEvent();
                if ($type == FolderEvent::TYPE_SELECTIVESYNC_CREATE
                    || $type == FolderEvent::TYPE_SELECTIVESYNC_DELETE) {
                    $userIds = array($createdBy->getId());
                }
                break;

            case (in_array($type, Event\PermissionEvent::getEventTypes())):
                $event = new Event\PermissionEvent();
                break;

            default:
                throw new ValidationFailedException('Event with type "' . $type . '" not found');
        }

        $event->setType($type)
            ->setSource($source)
            ->setCreatedBy($createdBy);


        //if user ids are not specified - define them automatically
        if (empty($userIds)) {
            $userIds = $this->getEventSourceUserIds($source);
        } elseif (!is_array($userIds)) {
            $userIds = array($userIds);
        }

        if (!empty($userIds)) {
            foreach ($userIds as $userId) {
                if ($source instanceof File) {
                    if (!$this->getPermissionService()->isWorkspaceAdmin($userId, $source->getWorkspace())) {
                        if (!$source->getParent() &&
                            ($event->getType() == FolderEvent::TYPE_CREATE ||
                                $event->getType() == FileEvent::TYPE_CREATE)) {
                            continue;
                        }
                    }
                }
                $newEvent = clone $event;
                $newEvent->setUser($this->getEntityManager()->getReference(User::getEntityName(), $userId));
                $this->getEntityManager()->persist($newEvent);
                $this->publishEvent($newEvent);
            }
        }
        if ($historyEvent) {
            $this->getHistoryEventService()->addEvent($type, $source, $createdBy, null, $newName);
        }
        return $event;
    }

    private function getEventSourceUserIds($source)
    {
        $userIds = array();
        switch (true) {
            case ($source instanceof Portal):
                $userIds = $this->getPortalService()->getPortalUserIds($source);
                break;
            case ($source instanceof Workspace):
                $userIds = $this->getWorkspaceService()->getWorkspaceUserIds($source);
                break;
            case ($source instanceof File):
                $userIds = $this->getFileService()->getFileUserIds($source);
                break;
            case ($source instanceof AclPermission):
                $aclRole = $source->getAclRole();
                switch ($aclRole->getEntityType()) {
                    case AclUserRole::ACL_ROLE_ENTITY_TYPE:
                        $userIds = array($aclRole->getUser()->getId());
                        break;
                    case AclGroupRole::ACL_ROLE_ENTITY_TYPE:
                        $groupService = $this->getGroupService();
                        $groupMemberships = $groupService->getGroupMemberships($aclRole->getGroup());
                        foreach ($groupMemberships as $groupMembership) {
                            array_push($userIds, $groupMembership->getUser()->getId());
                        }
                        break;
                }
                break;
            default:
                throw new ValidationFailedException('Unknown event source type - "' . get_class($source) . '"');
        }

        return $userIds;
    }

    public function getRealTimeServer($userId)
    {
        $redis      = $this->getRedis();
        $key        = $userId;

        $realTimeServer = new RealTimeServer();

        $expires    = Carbon::now()->addSeconds($realTimeServer->getTtl())->getTimestamp();

        do {
            $channelName = TokenHelper::getSecureToken();
        } while ($redis->exists($channelName));

        $result = $redis->zadd($key, [$channelName => $expires]);

        $realTimeServer->setUrl(UrlHelper::getRealTimeServerUrl($this->getContainer(), $channelName));

        return $realTimeServer;
    }

    private function publishEvent(Event $event)
    {
        $redis  = $this->getRedis();
        $key    = $event->getUser()->getId();
        $now    = Carbon::now()->getTimestamp();

        if ($redis->exists($key)) {
            $channels = $this->getUserChannels($event->getUser()->getId());
            foreach ($channels as $channel) {
                $this->getRedis()->publish($channel, self::NEW_EVENT_CHANNEL_MESSAGE);
            }
        }
    }

    public function getUserChannels($userId)
    {
        $redis  = $this->getRedis();
        $key    = $userId;
        $now    = Carbon::now()->getTimestamp();

        //clear expired channels
        $result = $redis->zremrangebyscore($key, 0, ($now - 1));

        $result = $redis->zrangebyscore($key, $now, '+inf');

        return $result;
    }

    /**
     * @return PortalService
     */
    public function getPortalService()
    {
        if (!$this->portalService) {
            $this->portalService = $this->getContainer()->get('PortalService');
        }
        return $this->portalService;
    }

    /**
     * @return WorkspaceService
     */
    public function getWorkspaceService()
    {
        if (!$this->workspaceService) {
            $this->workspaceService = $this->getContainer()->get('WorkspaceService');
        }
        return $this->workspaceService;
    }

    /**
     * @return FileService
     */
    public function getFileService()
    {
        if (!$this->fileService) {
            $this->fileService = $this->getContainer()->get('FileService');
        }
        return $this->fileService;
    }

    /**
     * @return GroupService
     */
    public function getGroupService()
    {
        if (!$this->groupService) {
            $this->groupService = $this->getContainer()->get('GroupService');
        }
        return $this->groupService;
    }

    /**
     * @return PermissionService
     */
    protected function getPermissionService()
    {
        if (!$this->permissionService) {
            $this->permissionService = $this->getContainer()->get('PermissionService');
        }
        return $this->permissionService;
    }

    /**
     * @return HistoryEventService
     */
    protected function getHistoryEventService()
    {
        if (!$this->historyEventService) {
            $this->historyEventService = $this->getContainer()->get('HistoryEventService');
        }
        return $this->historyEventService;
    }
    public function getCursorPosition()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select(array('e.id'))
            ->from(Event::ENTITY_NAME, 'e')
            ->orderBy('e.id', 'DESC')
            ->setMaxResults(1);

        try {
            $cursorPosition = $qb->getQuery()->getSingleScalarResult();
            return $cursorPosition;
        } catch (NoResultException $e) {
            return 0;
        }
    }

    /**
     * @return \Predis\Client
     */
    public function getRedis()
    {
        return $this->getContainer()->get('redis');
    }
}
