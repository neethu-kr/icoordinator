<?php

namespace iCoordinator\Service;

use Doctrine\ORM\EntityNotFoundException;
use iCoordinator\Entity\Acl\AclPermission;
use iCoordinator\Entity\Event;
use iCoordinator\Entity\Group;
use iCoordinator\Entity\GroupMembership;
use iCoordinator\Entity\HistoryEvent;
use iCoordinator\Entity\MetaField;
use iCoordinator\Entity\Portal;
use iCoordinator\Entity\Portal\AllowedClient;
use iCoordinator\Entity\Subscription;
use iCoordinator\Entity\User;
use iCoordinator\Service\Exception\NotTrashedException;
use iCoordinator\Service\Exception\ValidationFailedException;
use Laminas\Hydrator\ClassMethodsHydrator;

class PortalService extends AbstractService
{
    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var PermissionService
     */
    private $permissionService;

    /**
     * @var EventService
     */
    private $eventService;

    /**
     * @var HistoryEventService
     */
    private $historyEventService;

    /**
    * @var signUpService
    */
    private $signUpService;

    /**
     * @var stateService
     */
    private $stateService;

    /**
     * @var workspaceService
     */
    private $workspaceService;

    /**
     * @var fileService
     */
    private $fileService;

    /**
     * @var fdvService
     */
    private $fdvService;

    private function getClient()
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
            return '';
        }
    }

    /**
     * @param $portalId
     * @return null|Portal
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function getPortal($portalId)
    {
        $portal = $this->getEntityManager()->find(Portal::getEntityName(), $portalId);
        return $portal;
    }

    public function getPortalsAvailableForUser($user, $includeState = false, $slimState = false)
    {
        if (is_numeric($user)) {
            $userId = $user;
            /** @var User $user */
            $user = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        $portalIds = [];

        $portalPermissions = $this->getPermissionService()->getPortalPermissions($user);
        /** @var AclPermission $portalPermission */
        foreach ($portalPermissions as $portalPermission) {
            $portalId = $portalPermission->getAclResource()->getPortal()->getId();
            $portalIds[] = $portalId;
        }

        $portals = $this->getPortalsByIds($portalIds);
        $ownedPortals = $this->getPortalsOwnedByUser($user);

        foreach ($ownedPortals as $ownedPortal) {
            if (!in_array($ownedPortal->getId(), $portalIds)) {
                array_push($portals, $ownedPortal);
            }
        }

        $client = $this->getClient();
        $portalsList = array();
        if ($client == 'icoordinator_mobile' || $client == 'icoordinator_desktop') {
            foreach ($portals as $key => $portal) {
                $allowedClients = $portal->getAllowedClient($user->getUuid());
                if ($allowedClients) {
                    if ($client == 'icoordinator_mobile') {
                        if ($allowedClients->getMobile()) {
                            $portalsList[] = $portal;
                        }
                    } else {
                        if ($allowedClients->getDesktop()) {
                            $portalsList[] = $portal;
                        }
                    }
                } else {
                    $portalsList[] = $portal;
                }
            }
        } else {
            $portalsList = $portals;
        }
        if ($includeState) {
            foreach ($portalsList as $key => $portal) {
                $portalState = $this->getStateService()->getPortalState(
                    $portal,
                    $user,
                    ($client == 'icoordinator_desktop'),
                    $slimState
                );
                $portal->setState($portalState);
                $portalsList[$key] = $portal;
            }
        }
        return $portalsList;
    }

    /**
     * @param array $portalIds
     * @return array
     */
    private function getPortalsByIds(array $portalIds)
    {
        if (empty($portalIds)) {
            return array();
        }

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('p')
            ->from(Portal::getEntityName(), 'p')
            ->where($qb->expr()->in('p.id', $portalIds));

        $result = $qb->getQuery()->getResult();

        return $result;
    }

    //TODO cache results either in Redis or in var

    /**
     * @param User $user
     * @return Portal[]
     */
    private function getPortalsOwnedByUser(User $user)
    {
        return $this->getEntityManager()->getRepository(Portal::getEntityName())->findBy(array(
            'owned_by' => $user
        ));
    }

    public function getPortalUsers($portal)
    {
        $allowedClients = $this->getAllowedClients($portal);
        foreach ($allowedClients as $allowedClient) {
            $user = $allowedClient->getUser();
            $user->setIsOwner($user->getId() == $portal->getOwnedBy()->getId());
            $user->setDesktop($allowedClient->getDesktop());
            $user->setMobile($allowedClient->getMobile());
            $users[] = $user;
        }
        /*$portalUserIds = $this->getPortalUserIds($portal);
        $users = $this->getUserService()->getUsersByIds($portalUserIds);
        foreach ($users as $user) {
            $user->setIsOwner($user->getId() == $portal->getOwnedBy()->getId());
        }*/
        return $users;
    }

    public function getPortalUserIds($portal)
    {
        if (is_numeric($portal)) {
            $portalId = $portal;
            /** @var Portal $portal */
            $portal = $this->getEntityManager()->getReference(Portal::getEntityName(), $portalId);
        }

        $userIds = $this->getPermissionService()->getResourceUserIds($portal);

        //adding portal owner to users list
        $ownerId = $portal->getOwnedBy()->getId();
        if (!in_array($ownerId, $userIds)) {
            array_push($userIds, $ownerId);
        }

        return $userIds;
    }

    public function getAllowedClients($portal)
    {

        if (is_numeric($portal)) {
            $portalId = $portal;
            /** @var Portal $portal */
            $portal = $this->getEntityManager()->getReference(Portal::getEntityName(), $portalId);
        }
        $allowedClients = $portal->getAllowedClients();
        $userIds = $this->getPortalUserIds($portal);
        $setUserIds = array();
        $persist = false;
        foreach ($allowedClients as $key => $allowedClient) {
            if (!in_array($allowedClient->getUser()->getId(), $userIds)) {
                unset($allowedClients[$key]);
                $persist = true;
            } else {
                $setUserIds[] = $allowedClient->getUser()->getId();
            }
        }
        foreach ($userIds as $userId) {
            if (!in_array($userId, $setUserIds)) {
                $user = $this->getUserService()->getUser($userId);
                $newAllowedClients = new AllowedClient();
                $newAllowedClients->setUser($user);
                $newAllowedClients->setUuid($user->getUuid());
                $newAllowedClients->setPortal($portal);
                $newAllowedClients->setMobile(true);
                $newAllowedClients->setDesktop(true);
                $allowedClients[$user->getUuid()] = $newAllowedClients;
                $persist = true;
            }
        }
        if ($persist) {
            $portal->setAllowedClients($allowedClients);
            $this->getEntityManager()->persist($portal);
            $this->getEntityManager()->flush();
            //$allowedClients = $portal->getAllowedClients();
        }

        foreach ($allowedClients as $aClient) {
            $allowedList[] = $aClient;
        }
        return $allowedList;
    }

    public function setAllowedClients($portal, $data)
    {
        if (is_numeric($portal)) {
            $portalId = $portal;
            /** @var Portal $portal */
            $portal = $this->getEntityManager()->getReference(Portal::getEntityName(), $portalId);
        }

        if (empty($data['allowed_clients'])) {
            throw new ValidationFailedException();
        }
        foreach ($data['allowed_clients'] as $key => $allowedClient) {
            $user = $this->getUserService()->getUser($allowedClient['user']);
            if ($user) {
                $hydrator = new ClassMethodsHydrator();
                if ($alClient = $portal->getAllowedClient($user->getUuid())) {
                    $allowedClient['portal'] = $portal;
                    $allowedClient['user'] = $user;
                    $hydrator->hydrate($allowedClient, $alClient);
                } else {
                    $alClient = new AllowedClient();
                    $alClient->setPortal($portal);
                    $alClient->setUser($user);
                    $alClient->setUuid($user->getUuid());
                    $alClient->setMobile($allowedClient['mobile']);
                    $alClient->setDesktop($allowedClient['desktop']);
                    $portal->addAllowedClient($alClient);
                }
            }
        }

        $this->getEntityManager()->persist($portal);
        $this->getEntityManager()->flush();

        return $portal;
    }

    public function updateAllowedClients($allowedClient, $data, $updatedBy)
    {
        if (is_numeric($updatedBy)) {
            $userId = $updatedBy;
            /** @var User $ownedBy */
            $updatedBy = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }
        if (is_numeric($allowedClient)) {
            $allowedClientId = $allowedClient;
            /** @var $allowedClient $allowedClient */
            $allowedClient = $this->getEntityManager()->getReference(AllowedClient::getEntityName(), $allowedClientId);
        }
        if (isset($data['desktop'])) {
            //creating events
            $this->getHistoryEventService()->addEvent(
                HistoryEvent\PortalHistoryEvent::TYPE_USER_ALLOWED_CLIENTS_UPDATE,
                $allowedClient,
                $updatedBy,
                'desktop:' . ($allowedClient->getDesktop() ? 1:0) . ' -> ' . ($data['desktop'] ? 1:0)
            );
            /*
            TODO: This should be adding/removing permission for portal
            $this->getEventService()->addEvent(
                ($data['desktop'] ? Event\PortalEvent::TYPE_CREATE:Event\PortalEvent::TYPE_DELETE),
                $allowedClient->getPortal(),
                $updatedBy,
                $allowedClient->getUser()->getId(),
                false
            );*/
        }
        if (isset($data['mobile'])) {
            $this->getHistoryEventService()->addEvent(
                HistoryEvent\PortalHistoryEvent::TYPE_USER_ALLOWED_CLIENTS_UPDATE,
                $allowedClient,
                $updatedBy,
                'mobile:' . ($allowedClient->getMobile() ? 1:0) . ' -> ' . ($data['mobile'] ? 1:0)
            );
        }
        $hydrator = new ClassMethodsHydrator();
        $hydrator->hydrate($data, $allowedClient);
        $this->getEntityManager()->persist($allowedClient);
        $this->getEntityManager()->flush();

        return $allowedClient;
    }
    /**
     * @param $portal
     * @return int
     */
    public function getUsedStorage($portal)
    {
        if (is_numeric($portal)) {
            $portalId = $portal;
            /** @var Portal $portal */
            $portal = $this->getEntityManager()->getReference(Portal::ENTITY_NAME, $portalId);
        }
        $em = $this->getEntityManager();
        $sql = 'select sum(size) as total from workspaces w, files f where w.portal_id=' . $portal->getId() .
        ' and w.is_deleted=\'0\' and f.workspace_id=w.id and f.is_trashed=\'0\'' .
        ' and f.is_deleted=\'0\'';

        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();
        return $result[0]["total"];
    }
    /**
     * @return PermissionService
     */
    public function getPermissionService()
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

    public function createPortal($data, $ownedBy, $autoCommit = true)
    {
        if (is_numeric($ownedBy)) {
            $userId = $ownedBy;
            /** @var User $ownedBy */
            $ownedBy = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        $portal = new Portal();

        if (empty($data['name'])) {
            throw new ValidationFailedException();
        }

        $portal->setName($data['name'])
            ->setOwnedBy($ownedBy);


        $this->getEntityManager()->persist($portal);

        //creating event
        $this->getEventService()->addEvent(Event\PortalEvent::TYPE_CREATE, $portal, $ownedBy);

        if ($autoCommit) {
            $this->getEntityManager()->flush();
        }

        return $portal;
    }

    public function addRemovePortalUsersFromArray($portal, $userList)
    {
        $portalUsers = $this->getPortalUsers($portal);
        $portalUsersList = array();
        foreach ($portalUsers as $portalUser) {
            $portalUsersList[] = $portalUser->getEmail();
        }
        // Add users
        foreach ($userList as $user) {
            if (!in_array($user['email'], $portalUsersList)) {
                // Add user
                $data = array();
                $data['email'] = $user['email'];
                $data['first_name'] = $user['name'];
                $data['portal']['id'] = $portal->getId();
                $this->getSignUpService()->inviteUser($data, $portal->getOwnedBy(), false);
            }
        }
        // Remove users
        foreach ($userList as $user) {
            $emailList[] = $user['email'];
        }
        foreach ($portalUsers as $portalUser) {
            if (!in_array($portalUser->getEmail(), $emailList)) {
                $portalPermissions = $this->getPermissionService()->getPortalPermissions($portalUser);
                foreach ($portalPermissions as $portalPermission) {
                    $portalId = $portalPermission->getAclResource()->getPortal()->getId();
                    if ($portalId == $portal->getId()) {
                        $this->getPermissionService()->deletePermission($portalPermission);
                    }
                }
            }
        }
    }

    public function permanentRemovePortal($portal)
    {

        if (is_numeric($portal)) {
            $portalId = $portal;
            $portal = $this->getEntityManager()->getReference(Portal::ENTITY_NAME, $portalId);
        }
        //$portal = $this->getPortal($portalId);
        if ($portal) {
            $state = $portal->getSubscription()->getState();
            if ($state != Subscription::STATE_ACTIVE && $state != Subscription::STATE_TRIALING) {
                $workspaces = $this->getWorkspaceService()->getAllWorkspacesForPortal($portal);
                //Remove workspaces
                foreach ($workspaces as $workspace) {
                    $this->getWorkspaceService()->permanentRemoveWorkspace($workspace, true);
                    //permissions(portal_id), resources(entity_id, entity_type), roles(entity_id, entity_type)
                    // groups(portal_id), group memberships(group_id), invitation_workspace_groups(group_id),
                    // meta_fields(portal_id), meta_field_criteria(meta_field_id, smart_folder_id)
                    // files, file_versions(file_id), meta_field_values(file_id), locks(file_id),
                    // shared links(file_id), selective sync(file_id), events(source_id,source_type)
                }
                $fdvLicense = $this->getFDVService()->getPortalLicense($portal);
                if ($fdvLicense) {
                    try {
                        $this->getEntityManager()->remove($fdvLicense);
                    } catch (EntityNotFoundException $e) {
                    }
                }
                $invitations = $this->getSignUpService()->getPortalInvitations($portal);
                if (is_array($invitations)) {
                    foreach ($invitations as $invitation) {
                        try {
                            $this->getEntityManager()->remove($invitation);
                        } catch (EntityNotFoundException $e) {
                        }
                    }
                }
                $repository = $this->getEntityManager()->getRepository(AclPermission::getEntityName());
                $permissions = $repository->findBy(array(
                    'portal' => $portal
                ));
                if (is_array($permissions)) {
                    foreach ($permissions as $permission) {
                        try {
                            $this->getEntityManager()->remove($permission);
                        } catch (EntityNotFoundException $e) {
                        }
                    }
                }
                $repository = $this->getEntityManager()->getRepository(MetaField::getEntityName());
                $metaFields = $repository->findBy(array(
                    'portal' => $portal
                ));
                if (is_array($metaFields)) {
                    foreach ($metaFields as $metaField) {
                        try {
                            $this->getEntityManager()->remove($metaField);
                        } catch (EntityNotFoundException $e) {
                        }
                    }
                }
                $repository = $this->getEntityManager()->getRepository(AllowedClient::getEntityName());
                $allowedClients = $repository->findBy(array(
                    'portal' => $portal
                ));
                if (is_array($allowedClients)) {
                    foreach ($allowedClients as $allowedClient) {
                        try {
                            $this->getEntityManager()->remove($allowedClient);
                        } catch (EntityNotFoundException $e) {
                        }
                    }
                }
                $repository = $this->getEntityManager()->getRepository(Group::getEntityName());
                $groups = $repository->findBy(array(
                    'portal' => $portal
                ));
                if (is_array($groups)) {
                    foreach ($groups as $group) {
                        $repository = $this->getEntityManager()->getRepository(GroupMembership::getEntityName());
                        $groupMemberships = $repository->findBy(array(
                            'group' => $group
                        ));
                        if (is_array($groupMemberships)) {
                            foreach ($groupMemberships as $groupMembership) {
                                try {
                                    $this->getEntityManager()->remove($groupMembership);
                                } catch (EntityNotFoundException $e) {
                                }
                            }
                        }
                        try {
                            $this->getEntityManager()->remove($group);
                        } catch (EntityNotFoundException $e) {
                        }
                    }
                }
                $this->getHistoryEventService()->removeHistoryForPortal($portal);
                try {
                    $this->getEntityManager()->remove($portal);
                } catch (EntityNotFoundException $e) {
                }
                $this->getEntityManager()->flush();
                // invitation_workspace(workspace_id)
                // event_notifications(portal_id), invitations(portal_id)
            } else {
                throw new NotTrashedException();
            }
        }
    }
    /**
     * @return EventService
     */
    public function getEventService()
    {
        if (!$this->eventService) {
            $this->eventService = $this->getContainer()->get('EventService');
        }
        return $this->eventService;
    }

    /**
     * @return HistoryEventService
     */
    public function getHistoryEventService()
    {
        if (!$this->historyEventService) {
            $this->historyEventService = $this->getContainer()->get('HistoryEventService');
        }
        return $this->historyEventService;
    }

    /**
     * @return SignUpService
     */
    public function getSignUpService()
    {
        if (!$this->signUpService) {
            $this->signUpService = $this->getContainer()->get('SignUpService');
        }
        return $this->signUpService;
    }

    /**
     * @return StateService
     */
    public function getStateService()
    {
        if (!$this->stateService) {
            $this->stateService = $this->getContainer()->get('StateService');
        }
        return $this->stateService;
    }

    /**
     * @return StateService
     */
    public function getWorkspaceService()
    {
        if (!$this->workspaceService) {
            $this->workspaceService = $this->getContainer()->get('WorkspaceService');
        }
        return $this->workspaceService;
    }

    /**
     * @return StateService
     */
    public function getFileService()
    {
        if (!$this->fileService) {
            $this->fileService = $this->getContainer()->get('FileService');
        }
        return $this->fileService;
    }

    /**
     * @return FDVService
     */
    private function getFDVService()
    {
        if (!$this->fdvService) {
            $this->fdvService = $this->getContainer()->get('CustomerSpecific\Norway\FDVService');
        }

        return $this->fdvService;
    }
}
