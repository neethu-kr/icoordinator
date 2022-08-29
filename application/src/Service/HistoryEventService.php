<?php

namespace iCoordinator\Service;

use DateInterval;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use iCoordinator\Entity\Acl\AclPermission;
use iCoordinator\Entity\Acl\AclRole\AclGroupRole;
use iCoordinator\Entity\Acl\AclRole\AclUserRole;
use iCoordinator\Entity\Event;
use iCoordinator\Entity\Event\FileEvent;
use iCoordinator\Entity\Event\FolderEvent;
use iCoordinator\Entity\File;
use iCoordinator\Entity\HistoryEvent;
use iCoordinator\Entity\HistoryEvent\GroupHistoryEvent;
use iCoordinator\Entity\HistoryEvent\InvitationHistoryEvent;
use iCoordinator\Entity\HistoryEvent\MetaFieldHistoryEvent;
use iCoordinator\Entity\HistoryEvent\SmartFolderHistoryEvent;
use iCoordinator\Entity\HistoryEvent\UserHistoryEvent;
use iCoordinator\Entity\HistoryEvent\WorkspaceHistoryEvent;
use iCoordinator\Entity\Portal;
use iCoordinator\Entity\User;
use iCoordinator\Entity\Workspace;
use iCoordinator\Service\Exception\ValidationFailedException;

class HistoryEventService extends AbstractService
{

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
     * @var PermissionsService
     */
    private $permissionService;

    /**
     * @var UserService
     */
    private $userService;

    private function getClientId()
    {
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $token = str_replace("Bearer ", "", $_SERVER['HTTP_AUTHORIZATION']);
            $accessToken = $this->getContainer()['entityManager']
                ->getRepository('\iCoordinator\Entity\OAuthAccessToken')
                ->findOneBy(
                    array(
                        'accessToken' => $token
                    )
                );
            return $accessToken->getClientId();
        } else {
            return "";
        }
    }

    public function getClientVersion()
    {
        if (isset($_SERVER['HTTP_CLIENT_VERSION'])) {
            return $_SERVER['HTTP_CLIENT_VERSION'];
        } else {
            return "";
        }
    }
    /**
     * @param $type
     * @param $source
     * @param $createdBy
     * @param null $userIds
     * @return HistoryEvent\FileHistoryEvent|HistoryEvent\FolderHistoryEvent|
     * HistoryEvent\PermissionHistoryEvent|HistoryEvent\WorkspaceHistoryEvent
     * @throws ValidationFailedException
     * @throws \Doctrine\ORM\ORMException
     */
    public function addEvent($type, $source, $createdBy, $mixed = null, $newName = null)
    {
        switch (true) {
            case (in_array($type, Event\UserEvent::getEventTypes())
                || in_array($type, HistoryEvent\UserHistoryEvent::getEventTypes())):
                $historyEvent = new HistoryEvent\UserHistoryEvent();
                break;

            case (in_array($type, HistoryEvent\GroupHistoryEvent::getEventTypes())):
                $historyEvent = new HistoryEvent\GroupHistoryEvent();
                $historyEvent->setPortal($source->getPortal());
                if ($source->getScope()->getResourceId() == 'workspace') {
                    $historyEvent->setWorkspace($source->getScope());
                }
                if ($type == HistoryEvent\GroupHistoryEvent::TYPE_CHANGE_NAME) {
                    // If rename store old name in description
                    $historyEvent->setDescription($source->getName() . ' -> ' . $newName);
                } else {
                    if ($mixed instanceof User) {
                        $historyEvent->setGroupUser($mixed);
                        $historyEvent->setDescription($source->getName());
                    } else {
                        $historyEvent->setDescription($mixed);
                    }
                }
                break;
            case (in_array($type, Event\PortalEvent::getEventTypes())):
                $historyEvent = new HistoryEvent\PortalHistoryEvent();
                $historyEvent->setPortal($source);
                break;
            case (in_array($type, HistoryEvent\PortalHistoryEvent::getEventTypes())):
                $historyEvent = new HistoryEvent\PortalHistoryEvent();
                if ($type == HistoryEvent\PortalHistoryEvent::TYPE_USER_ALLOWED_CLIENTS_UPDATE) {
                    $historyEvent->setGroupUser($source->getUser());
                    $source = $source->getPortal();
                    $historyEvent->setPortal($source);
                } else {
                    $historyEvent->setPortal($source);
                }
                $historyEvent->setDescription($mixed);
                break;
            case (in_array($type, Event\WorkspaceEvent::getEventTypes())
                || in_array($type, HistoryEvent\WorkspaceHistoryEvent::getEventTypes())):
                $historyEvent = new HistoryEvent\WorkspaceHistoryEvent();
                $historyEvent->setPortal($source->getPortal())
                    ->setWorkspace($source);
                if ($type == Event\WorkspaceEvent::TYPE_RENAME) {
                    // If rename store old name in description
                    $historyEvent->setDescription($source->getName() . ' -> ' . $newName);
                } else {
                    $historyEvent->setDescription($mixed);
                }
                break;

            case (in_array($type, Event\FileEvent::getEventTypes())):
                $historyEvent = new HistoryEvent\FileHistoryEvent();
                if ($type == FileEvent::TYPE_SELECTIVESYNC_CREATE
                    || $type == FileEvent::TYPE_SELECTIVESYNC_DELETE) {
                    return $historyEvent;
                }
                $historyEvent->setPortal($source->getWorkspace()->getPortal())
                    ->setWorkspace($source->getWorkspace());
                if ($type == Event\FileEvent::TYPE_RENAME) {
                    // If rename store old name in description
                    // Due to a buggfix in event creation for folders and files
                    // these variables are now reversed
                    // keeping old $newName since this is also used by portal, workspace, group
                    $historyEvent->setDescription($newName . ' -> ' . $source->getName());
                } else {
                    $historyEvent->setDescription($mixed);
                }
                break;
            case (in_array($type, HistoryEvent\FileHistoryEvent::getEventTypes())):
                $historyEvent = new HistoryEvent\FileHistoryEvent();
                $historyEvent->setPortal($source->getWorkspace()->getPortal())
                    ->setWorkspace($source->getWorkspace());
                $historyEvent->setDescription($mixed);
                break;
            case (in_array($type, Event\FolderEvent::getEventTypes())):
                $historyEvent = new HistoryEvent\FolderHistoryEvent();
                if ($type == FolderEvent::TYPE_SELECTIVESYNC_CREATE
                    || $type == FolderEvent::TYPE_SELECTIVESYNC_DELETE) {
                    return $historyEvent;
                }
                $historyEvent->setPortal($source->getWorkspace()->getPortal())
                    ->setWorkspace($source->getWorkspace());
                if ($type == Event\FolderEvent::TYPE_RENAME) {
                    // If rename store old name in description
                    // Due to a buggfix in event creation for folders and files
                    // these variables are now reversed
                    // keeping old $newName since this is also used by portal, workspace, group
                    $historyEvent->setDescription($newName . ' -> ' . $source->getName());
                } else {
                    $historyEvent->setDescription($mixed);
                }
                break;

            case (in_array($type, HistoryEvent\SmartFolderHistoryEvent::getEventTypes())):
                $historyEvent = new HistoryEvent\SmartFolderHistoryEvent();
                $historyEvent->setPortal($source->getWorkspace()->getPortal())
                    ->setWorkspace($source->getWorkspace());
                $historyEvent->setDescription($mixed);
                break;
            case (in_array($type, Event\PermissionEvent::getEventTypes())):
                $permResource = $source->getAclResource();
                if ($permResource instanceof \iCoordinator\Entity\Acl\AclResource\AclFileResource) {
                    $historyEvent = new HistoryEvent\PermissionHistoryEvent();
                    $historyEvent->setWorkspace($permResource->getFile()->getWorkspace());
                    $historyEvent->setPortal($permResource->getFile()->getWorkspace()->getPortal());
                } elseif ($permResource instanceof \iCoordinator\Entity\Acl\AclResource\AclWorkspaceResource) {
                    $historyEvent = new HistoryEvent\PermissionHistoryEvent();
                    $historyEvent->setWorkspace($permResource->getWorkspace());
                    $historyEvent->setPortal($permResource->getWorkspace()->getPortal());
                } elseif ($permResource instanceof \iCoordinator\Entity\Acl\AclResource\AclPortalResource) {
                    $historyEvent = new HistoryEvent\PermissionHistoryEvent();
                    $historyEvent->setPortal($permResource->getResource());
                }
                $historyEvent->setDescription($mixed);
                break;
            case (in_array($type, HistoryEvent\MetaFieldValueHistoryEvent::getEventTypes())):
                $historyEvent = new HistoryEvent\MetaFieldValueHistoryEvent();
                $historyEvent->setPortal($source->getResource()->getWorkspace()->getPortal());
                $historyEvent->setWorkspace($source->getResource()->getWorkspace());
                $historyEvent->setDescription($mixed);
                break;
            case (in_array($type, HistoryEvent\MetaFieldHistoryEvent::getEventTypes())):
                $historyEvent = new HistoryEvent\MetaFieldHistoryEvent();
                $historyEvent->setPortal($source->getPortal());
                $historyEvent->setDescription($mixed);
                break;
            case (in_array($type, HistoryEvent\InvitationHistoryEvent::getEventTypes())):
                $historyEvent = new HistoryEvent\InvitationHistoryEvent();
                $historyEvent->setPortal($source->getPortal());
                $historyEvent->setDescription($mixed);
                break;
            default:
                throw new ValidationFailedException('History event with type "' . $type . '" not found');
        }

        $historyEvent->setType($type)
            ->setSource($source)
            ->setCreatedBy($createdBy)
            ->setClientId($this->getClientId())
            ->setClientVersion($this->getClientVersion());

        $newEvent = clone $historyEvent;
        $this->getEntityManager()->persist($newEvent);
        $this->getEntityManager()->flush();

        return $historyEvent;
    }

    public function getEntity($entityName, $id)
    {
        try {
            if ($id) {
                return $this->getEntityManager()->find($entityName, $id);
            } else {
                return null;
            }
        } catch (EntityNotFoundException $e) {
            return null;
        }
    }

    public function getUserHistoryEvents(
        $user,
        $startDate,
        $portal = null,
        $workspace = null,
        $limit = 500,
        $offset = 0
    ) {
        if (is_numeric($user)) {
            $userId = $user;
            /** @var User $user */
            $user = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }
        if (is_numeric($portal)) {
            $portalId = $portal;
            /** @var Portal $portal */
            $portal = $this->getEntityManager()->getReference(Portal::ENTITY_NAME, $portalId);
        }
        if (is_numeric($workspace)) {
            $workspaceId = $workspace;
            /** @var Workspace $workspace */
            $workspace = $this->getEntityManager()->getReference(Workspace::ENTITY_NAME, $workspaceId);
        }
        /*switch ($period) {
            case "week":
                $afterDate = new DateTime('-1 week');
                break;
            case "month":
                $afterDate = new DateTime('-1 month');
                break;
            case "year":
                $afterDate = new DateTime('-1 year');
                break;
            case "all":
                $afterDate = new DateTime('2017-01-01');
                break;
            default:
                $afterDate = new DateTime('-1 day');
                break;
        }*/
        $startDate = $startDate->add(new DateInterval('P1D'));
        if (false) {
            $qb = $this->getEntityManager()->createQueryBuilder();

            $qb->select(array('he'))
                ->from(HistoryEvent::ENTITY_NAME, 'he')
                ->orderBy('he.id', 'ASC');

            if ($portal != null) {
                $qb->andWhere('he.portal = :portal')
                    ->setParameter('portal', $portal->getId());
            }
            if ($workspace != null) {
                $qb->andWhere('he.workspace = :workspace')
                    ->setParameter('workspace', $workspace->getId());
            }
            $qb->andWhere('he.created_at < :afterDate')
                ->setParameter('afterDate', $startDate->format('Y-m-d'));

            $query = $qb->getQuery();
            $paginator = new Paginator($query, false);

            return $paginator;
        } else {
            $em = $this->getEntityManager();
            $sql = 'SELECT * FROM history_events where portal_id='. $portal->getId();
            if ($workspace != null) {
                $sql .= ' AND workspace_id=' . $workspace->getId();
            }
            $sql .= ' AND created_at<"'.$startDate->format('Y-m-d').'"';
            if ($offset) {
                $sql .= ' AND id<='.$offset;
            }
            $sql .= ' ORDER BY id DESC';
            $sql .= ' LIMIT '. $limit;


            $stmt = $em->getConnection()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        }
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
                throw new ValidationFailedException('Unknown history event source type - "' . get_class($source) . '"');
        }

        return $userIds;
    }

    /**
     * @param $workspace
     */
    public function removeHistoryForWorkspace($workspace)
    {
        $em = $this->getEntityManager();
        $pdo = $em->getConnection();
        $sql = 'DELETE FROM history_events where workspace_id='.$workspace->getId();

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    }

    /**
     * @param $workspace
     */
    public function removeHistoryForPortal($portal)
    {
        $em = $this->getEntityManager();
        $pdo = $em->getConnection();
        $sql = 'DELETE FROM history_events where portal_id='.$portal->getId();

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
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
     * @return UserService
     */
    public function getUserService()
    {
        if (!$this->userService) {
            $this->userService = $this->getContainer()->get('UserService');
        }
        return $this->userService;
    }
}
