<?php

namespace iCoordinator\Service;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Tools\Pagination\Paginator;
use iCoordinator\Entity\Acl\AclPermission;
use iCoordinator\Entity\Event\PermissionEvent;
use iCoordinator\Entity\Group;
use iCoordinator\Entity\GroupMembership;
use iCoordinator\Entity\HistoryEvent\GroupHistoryEvent;
use iCoordinator\Entity\Portal;
use iCoordinator\Entity\User;
use iCoordinator\Entity\Workspace;
use iCoordinator\Service\Exception\ConflictException;
use iCoordinator\Service\Exception\NotFoundException;
use iCoordinator\Service\Exception\ValidationFailedException;
use Laminas\Hydrator\ClassMethodsHydrator;

/**
 * Class GroupService
 * @package iCoordinator\Service
 */
class GroupService extends AbstractService
{
    const GROUPS_LIMIT_DEFAULT = 100;

    const GROUP_MEMBERSHIPS_LIMIT_DEFAULT = 100;

    /**
     * @var EventService
     */
    private $eventService;

    /**
     * @var HistoryEventService
     */
    private $historyEventService;

    /**
     * @var PermissionService
     */
    private $permissionService;

    public function getPortalGroups($portal, $limit = self::GROUPS_LIMIT_DEFAULT, $offset = 0)
    {
        if (is_numeric($portal)) {
            $portalId = $portal;
            /** @var Portal $portal */
            $portal = $this->getEntityManager()->getReference(Portal::getEntityName(), $portalId);
        }

        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select(array('g'))
            ->from(Group\PortalGroup::ENTITY_NAME, 'g')
            ->andWhere('g.scope = :portal')
            ->setParameter('portal', $portal);

        if ($limit !== null) {
            $qb->setFirstResult($offset)
                ->setMaxResults($limit);
        }

        $query = $qb->getQuery();
        $paginator = new Paginator($query, false);

        return $paginator;
    }

    public function getWorkspaceGroups($workspace, $limit = self::GROUPS_LIMIT_DEFAULT, $offset = 0)
    {
        if (is_numeric($workspace)) {
            $workspaceId = $workspace;
            /** @var Workspace $workspace */
            $workspace = $this->getEntityManager()->getReference(Workspace::getEntityName(), $workspaceId);
        }

        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select(array('g'))
            ->from(Group\WorkspaceGroup::ENTITY_NAME, 'g')
            ->andWhere('g.scope = :workspace')
            ->orderBy('g.name', 'ASC')
            ->setParameter('workspace', $workspace);

        if ($limit !== null) {
            $qb->setFirstResult($offset)
                ->setMaxResults($limit);
        }

        $query = $qb->getQuery();
        $paginator = new Paginator($query, false);

        return $paginator;
    }


    /**
     * @param $groupId
     * @return null|Group
     */
    public function getGroup($groupId)
    {
        $group = $this->getEntityManager()->find(Group::ENTITY_NAME, $groupId);
        return $group;
    }

    /**
     * @param $portal
     * @param array $data
     * @return Group\PortalGroup
     * @throws ValidationFailedException
     * @throws \Doctrine\ORM\ORMException
     */
    public function createPortalGroup($portal, array $data, $createdBy)
    {
        if (is_numeric($portal)) {
            $portalId = $portal;
            /** @var Portal $portal */
            $portal = $this->getEntityManager()->getReference(Portal::getEntityName(), $portalId);
        }
        if ($createdBy !== null && is_numeric($createdBy)) {
            $userId = $createdBy;
            /** @var User $createdBy */
            $createdBy = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }
        if (empty($data['name'])) {
            throw new ValidationFailedException();
        }

        $group = new Group\PortalGroup();
        $group->setPortal($portal);
        $group->setScope($portal);

        $hydrator = new ClassMethodsHydrator();
        $hydrator->hydrate($data, $group);

        $this->getEntityManager()->persist($group);
        $this->getEntityManager()->flush();

        $this->getHistoryEventService()->addEvent(
            GroupHistoryEvent::TYPE_CREATE,
            $group,
            $createdBy,
            $group->getName()
        );

        return $group;
    }

    /**
     * @param $workspace
     * @param array $data
     * @return Group\WorkspaceGroup
     * @throws ValidationFailedException
     * @throws \Doctrine\ORM\ORMException
     */
    public function createWorkspaceGroup($workspace, array $data, $createdBy)
    {
        if (is_numeric($workspace)) {
            $workspaceId = $workspace;
            /** @var Workspace $workspace */
            $workspace = $this->getEntityManager()->getReference(Workspace::getEntityName(), $workspaceId);
        }
        if ($createdBy !== null && is_numeric($createdBy)) {
            $userId = $createdBy;
            /** @var User $createdBy */
            $createdBy = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }
        if (empty($data['name'])) {
            throw new ValidationFailedException();
        }

        $group = new Group\WorkspaceGroup();
        $group->setPortal($workspace->getPortal());
        $group->setScope($workspace);

        $hydrator = new ClassMethodsHydrator();
        $hydrator->hydrate($data, $group);

        $this->getEntityManager()->persist($group);
        $this->getEntityManager()->flush();

        $this->getHistoryEventService()->addEvent(
            GroupHistoryEvent::TYPE_CREATE,
            $group,
            $createdBy,
            $group->getName()
        );

        return $group;
    }

    /**
     * @param $group
     * @throws ConflictException
     * @throws NotFoundException
     * @throws \Doctrine\ORM\ORMException
     */
    public function deleteGroup($group, $createdBy)
    {
        if (is_numeric($group)) {
            $groupId = $group;
            $group = $this->getEntityManager()->getReference(Group::ENTITY_NAME, $groupId);
        }
        if ($createdBy !== null && is_numeric($createdBy)) {
            $userId = $createdBy;
            /** @var User $createdBy */
            $createdBy = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }
        if ($this->hasGroupMemberships($group)) {
            throw new ConflictException('Delete group memberships first');
        }
        // Removing permissions connected to the group
        $permissions = $this->getGroupPermissions($group);
        foreach ($permissions as $permission) {
            $this->getPermissionService()->deletePermission($permission);
        }
        $this->getHistoryEventService()->addEvent(
            GroupHistoryEvent::TYPE_DELETE,
            $group,
            $createdBy,
            $group->getName()
        );
        try {
            $this->getEntityManager()->remove($group);
        } catch (EntityNotFoundException $e) {
            throw new NotFoundException();
        }
        $this->getEntityManager()->flush();
    }

    /**
     * @param $group
     * @return bool
     * @throws \Doctrine\ORM\ORMException
     */
    public function hasGroupMemberships($group)
    {
        if (is_numeric($group)) {
            $groupId = $group;
            /** @var Group $group */
            $group = $this->getEntityManager()->getReference(Group::ENTITY_NAME, $groupId);
        }

        $repository = $this->getEntityManager()->getRepository(GroupMembership::ENTITY_NAME);
        $groupMembership = $repository->findOneBy(array(
            'group' => $group
        ));

        if ($groupMembership) {
            return true;
        }

        return false;
    }

    /**
     * @param $group
     * @param array $data
     * @return Group
     * @throws NotFoundException
     * @throws \Doctrine\ORM\ORMException
     */
    public function updateGroup($group, array $data, $updatedBy)
    {
        if (is_numeric($group)) {
            $groupId = $group;
            $group = $this->getEntityManager()->getReference(Group::ENTITY_NAME, $groupId);
        }
        if ($updatedBy !== null && is_numeric($updatedBy)) {
            $userId = $updatedBy;
            /** @var User $$updatedBy */
            $updatedBy = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }
        $groupName = $group->getName();
        if ($data["name"] != "" && $data["name"] != $groupName) {
            // Using name temporary to pass name change information to history events
            // will be hydrated to new name down below
            $this->getHistoryEventService()->addEvent(
                GroupHistoryEvent::TYPE_CHANGE_NAME,
                $group,
                $updatedBy,
                null,
                $data['name']
            );
        }
        $hydrator = new ClassMethodsHydrator();

        try {
            $hydrator->hydrate($data, $group);
            $this->getEntityManager()->merge($group);
            $this->getEntityManager()->flush();
        } catch (EntityNotFoundException $e) {
            throw new NotFoundException();
        }

        return $group;
    }

    /**
     * @param $group
     * @param $user
     * @param bool|true $commitChanges
     * @return GroupMembership
     * @throws ValidationFailedException
     * @throws \Doctrine\ORM\ORMException
     */
    public function createGroupMembership($group, $user, $createdBy, $commitChanges = true)
    {
        if (is_numeric($group)) {
            $groupId = $group;
            /** @var Group $group */
            $group = $this->getEntityManager()->getReference(Group::ENTITY_NAME, $groupId);
        }

        if (is_numeric($user)) {
            $userId = $user;
            /** @var User $user */
            $user = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        if ($createdBy !== null && is_numeric($createdBy)) {
            $userId = $createdBy;
            /** @var User $createdBy */
            $createdBy = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        $groupMembership = new GroupMembership();
        $groupMembership->setUser($user)
                        ->setGroup($group);

        $this->getEntityManager()->persist($groupMembership);

        $this->getHistoryEventService()->addEvent(
            GroupHistoryEvent::TYPE_ADD_USER,
            $group,
            ($createdBy != null ? $createdBy : $group->getPortal()->getOwnedBy()),
            $user
        );

        //create events
        $groupPermissions = $this->getGroupPermissions($group);
        foreach ($groupPermissions as $permission) {
            $this->getEventService()->addEvent(
                PermissionEvent::TYPE_CREATE,
                $permission,
                $group->getPortal()->getOwnedBy(),
                array($user->getId()),
                false // Do not create history events
            );
        }

        if ($commitChanges) {
            $this->getEntityManager()->flush();
        }

        return $groupMembership;
    }

    public function getGroupPermissions(Group $group)
    {
        $aclRole = $group->getAclRole();

        $repository = $this->getEntityManager()->getRepository(AclPermission::getEntityName());

        $permissions = $repository->findBy(array(
            'acl_role' => $aclRole
        ));

        return $permissions;
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
     * @param $groupMembershipId
     * @return null|GroupMembership
     */
    public function getGroupMembership($groupMembershipId)
    {
        $groupMembership = $this->getEntityManager()->find(GroupMembership::ENTITY_NAME, $groupMembershipId);
        return $groupMembership;
    }

    /**
     * @param $groupMembership
     * @throws NotFoundException
     * @throws \Doctrine\ORM\ORMException
     */
    public function deleteGroupMembership($groupMembership, $createdBy)
    {
        if (is_numeric($groupMembership)) {
            $groupMembershipId = $groupMembership;
            $groupMembership = $this->getEntityManager()->getReference(
                GroupMembership::ENTITY_NAME,
                $groupMembershipId
            );
        }
        if ($createdBy !== null && is_numeric($createdBy)) {
            $userId = $createdBy;
            /** @var User $createdBy */
            $createdBy = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        $group = $groupMembership->getGroup();
        $user = $groupMembership->getUser();

        $this->getEntityManager()->remove($groupMembership);

        $this->getHistoryEventService()->addEvent(
            GroupHistoryEvent::TYPE_REMOVE_USER,
            $group,
            $createdBy,
            $user
        );

        //create events
        $groupPermissions = $this->getGroupPermissions($group);
        foreach ($groupPermissions as $permission) {
            $this->getEventService()->addEvent(
                PermissionEvent::TYPE_DELETE,
                $permission,
                $group->getPortal()->getOwnedBy(),
                array($user->getId()),
                false
            );
        }

        try {
            $this->getEntityManager()->flush();
        } catch (EntityNotFoundException $e) {
            throw new NotFoundException();
        }
    }

    /**
     * @param $group
     * @param int $limit
     * @param int $offset
     * @return Paginator
     * @throws \Doctrine\ORM\ORMException
     */
    public function getGroupMemberships($group, $limit = null, $offset = 0)
    {
        if (is_numeric($group)) {
            $groupId = $group;
            /** @var Group $group */
            $group = $this->getEntityManager()->getReference(Group::ENTITY_NAME, $groupId);
        }

        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select(array('gm'))
            ->from(GroupMembership::ENTITY_NAME, 'gm')
            ->andWhere('gm.group = :group')
            ->setParameter('group', $group);

        if ($limit !== null) {
            $qb->setFirstResult($offset)
                ->setMaxResults($limit);
        }

        $query = $qb->getQuery();
        $paginator = new Paginator($query, false);

        return $paginator;
    }

    /**
     * @param $group
     * @param int $limit
     * @param int $offset
     * @return Paginator
     * @throws \Doctrine\ORM\ORMException
     */
    public function getGroupUsers($group, $limit = null, $offset = 0)
    {
        if (is_numeric($group)) {
            $groupId = $group;
            /** @var Group $group */
            $group = $this->getEntityManager()->getReference(Group::ENTITY_NAME, $groupId);
        }

        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select(array('u'))
            ->from(User::ENTITY_NAME, 'u')
            ->innerJoin(GroupMembership::ENTITY_NAME, 'gm', Join::WITH, 'u.id = gm.user')
            ->andWhere('gm.group = :group')
            ->setParameter('group', $group);

        if ($limit !== null) {
            $qb->setFirstResult($offset)
                ->setMaxResults($limit);
        }

        $query = $qb->getQuery();
        $paginator = new Paginator($query, false);

        return $paginator;
    }
}
