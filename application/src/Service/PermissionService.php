<?php

namespace iCoordinator\Service;

use Doctrine\ORM\Query\Expr\Join;
use iCoordinator\Entity\Acl\AclPermission;
use iCoordinator\Entity\Acl\AclResource;
use iCoordinator\Entity\Acl\AclResource\AclFileResource;
use iCoordinator\Entity\Acl\AclResource\AclPortalResource;
use iCoordinator\Entity\Acl\AclResource\AclWorkspaceResource;
use iCoordinator\Entity\Acl\AclRole;
use iCoordinator\Entity\Acl\AclRole\AclGroupRole;
use iCoordinator\Entity\Acl\AclRole\AclUserRole;
use iCoordinator\Entity\Event\PermissionEvent;
use iCoordinator\Entity\File;
use iCoordinator\Entity\Group;
use iCoordinator\Entity\Permission;
use iCoordinator\Entity\Portal;
use iCoordinator\Entity\User;
use iCoordinator\Entity\Workspace;
use iCoordinator\Permissions\Acl;
use iCoordinator\Permissions\BitMask;
use iCoordinator\Permissions\PermissionType;
use iCoordinator\Permissions\Resource\HavingDynamicPermissionsResourceInterface;
use iCoordinator\Permissions\Role\HavingAclRoleInterface;
use iCoordinator\Service\Exception\NotFoundException;
use iCoordinator\Service\Exception\ValidationFailedException;

class PermissionService extends AbstractService
{
    const PERMISSIONS_LIMIT_DEFAULT = 100;

    /**
     * @var EventService
     */
    private $eventService;

    /**
     * @var HistoryEventService
     */
    private $historyEventService;

    /**
     * @var GroupService
     */
    private $groupService;

    /**
     * @var UserService
     */
    private $userService;

    /**
     * Permissions cache
     *
     * @var \ArrayIterator
     */
    private $bitmaskCache;

    /**
     * @var \ArrayIterator
     */
    private $aclRolesCache;

    /**
     * @var array
     */
    private $aclResourceEntityTypes = array(
        AclFileResource::ACL_RESOURCE_ENTITY_TYPE,
        AclPortalResource::ACL_RESOURCE_ENTITY_TYPE,
        AclWorkspaceResource::ACL_RESOURCE_ENTITY_TYPE
    );

    /**
     * @var array
     */
    private $aclRoleEntityTypes = array(
        AclUserRole::ACL_ROLE_ENTITY_TYPE,
        AclGroupRole::ACL_ROLE_ENTITY_TYPE
    );


    public function __construct()
    {
        $this->clearCache();
    }

    public function clearCache()
    {
        $this->bitmaskCache = new \ArrayIterator();
        $this->aclRolesCache = new \ArrayIterator();
    }

    public function addPermission(
        HavingDynamicPermissionsResourceInterface $resource,
        HavingAclRoleInterface $grantTo,
        $actions,
        $createdBy,
        Portal $portal,
        $commitChanges = true
    ) {
        if ($createdBy !== null && is_numeric($createdBy)) {
            $userId = $createdBy;
            /** @var User $createdBy */
            $createdBy = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        if (!is_array($actions)) {
            $actions = array($actions);
        }

        $aclResource = $this->getAclResource($resource);
        $aclRole = $this->getAcRole($grantTo);

        $aclPermission = $this->getAclResourcePermission($aclResource, $aclRole);
        if ($aclPermission) {
            if ($aclRole->getEntityType() == "group") {
                return $this->updatePermission($aclPermission[0], $actions, $createdBy);
            } else {
                throw new ValidationFailedException('Permission for this user or group already exists');
            }
        }

        $aclPermission = new AclPermission();
        $aclPermission->setAclResource($aclResource)
                      ->setAclRole($aclRole)
                      ->setActions($actions)
                      ->setGrantedBy($createdBy)
                      ->setPortal($portal);

        $this->getEntityManager()->persist($aclPermission);

        //creating event
        if ($createdBy !== null) {
            $this->getEventService()->addEvent(
                PermissionEvent::TYPE_CREATE,
                $aclPermission,
                $createdBy,
                null,
                false
            );

            $this->getHistoryEventService()->addEvent(
                PermissionEvent::TYPE_CREATE,
                $aclPermission,
                $createdBy,
                $aclPermission->getBitmask()
            );
        }

        if ($commitChanges == true) {
            $this->getEntityManager()->flush();
        }

        return $aclPermission;
    }

    /**
     * @param HavingAclRoleInterface $roleOwner
     * @param bool|true $createIfNotFound
     * @return AclRole|AclGroupRole|AclUserRole
     * @throws \Exception
     */
    private function getAcRole(HavingAclRoleInterface $roleOwner, $createIfNotFound = true)
    {
        $aclRole = $roleOwner->getAclRole();

        if (!$aclRole && $createIfNotFound == true) {
            $aclRole = $this->createAclRole($roleOwner);
        }

        return $aclRole;
    }

    /**
     * @param HavingDynamicPermissionsResourceInterface $resource
     * @param bool|true $createIfNotFound
     * @return AclResource|AclFileResource|AclPortalResource|AclWorkspaceResource
     * @throws \Exception
     */
    public function getAclResource(HavingDynamicPermissionsResourceInterface $resource, $createIfNotFound = true)
    {
        $aclResource = $resource->getAclResource();

        if (!$aclResource && $createIfNotFound == true) {
            $aclResource = $this->createAclResource($resource);
        }

        return $aclResource;
    }

    /**
     * @param $aclResource
     * @param $aclRole
     * @return null|AclPermission
     */
    private function getAclResourcePermission($aclResource, $aclRole)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select(array('p'))
            ->from(AclPermission::getEntityName(), 'p')
            ->andWhere('p.acl_role = :acl_role')
            ->setParameter('acl_role', $aclRole)
            ->andWhere('p.acl_resource = :acl_resource')
            ->setParameter('acl_resource', $aclResource)
            ->andWhere('p.is_deleted != 1');

        $query = $qb->getQuery();

        return $query->getResult();
    }

    /**
     * @param HavingDynamicPermissionsResourceInterface $resource
     * @return AclFileResource|AclPortalResource|AclWorkspaceResource
     * @throws \Exception
     */
    private function createAclResource(HavingDynamicPermissionsResourceInterface $resource)
    {
        switch (true) {
            case ($resource instanceof Portal):
                $aclResource = new AclPortalResource();
                $aclResource->setPortal($resource);
                break;
            case ($resource instanceof Workspace):
                $aclResource = new AclWorkspaceResource();
                $aclResource->setWorkspace($resource);
                break;
            case ($resource instanceof File):
                $aclResource = new AclFileResource();
                $aclResource->setFile($resource);
                break;
            default:
                throw new \Exception('Incorrect resource');
                break;
        }

        $resource->setAclResource($aclResource);

        $this->getEntityManager()->persist($aclResource);
        if ($this->getAutoCommitChanges()) {
            $this->getEntityManager()->flush();
        }

        return $aclResource;
    }

    /**
     * @param HavingAclRoleInterface $roleOwner
     * @return AclGroupRole|AclUserRole
     * @throws \Exception
     */
    private function createAclRole(HavingAclRoleInterface $roleOwner)
    {
        switch (true) {
            case ($roleOwner instanceof User):
                $aclRole = new AclUserRole();
                $aclRole->setUser($roleOwner);
                $roleOwner->setAclRole($aclRole);
                break;
            case ($roleOwner instanceof Group):
                $aclRole = new AclGroupRole();
                $aclRole->setGroup($roleOwner);
                $roleOwner->setAclRole($aclRole);
                break;
            default:
                throw new \Exception('Incorrect role owner');
                break;
        }

        $this->getEntityManager()->persist($aclRole);
        $this->getEntityManager()->flush();

        return $aclRole;
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
     * @param $aclPermission
     * @param $actions
     * @param null $updatedBy
     * @return null|object
     * @throws NotFoundException
     * @throws ValidationFailedException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function updatePermission($aclPermission, $actions, $updatedBy = null)
    {
        if (!is_array($actions)) {
            $actions = array($actions);
        }

        if (is_numeric($aclPermission)) {
            $aclPermissionId = $aclPermission;
            $aclPermission = $this->getEntityManager()->find(AclPermission::getEntityName(), $aclPermissionId);
            if (!$aclPermission) {
                throw new NotFoundException('Permission not found');
            }
        }

        if ($updatedBy !== null && is_numeric($updatedBy)) {
            $userId = $updatedBy;
            $updatedBy = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }
        $bitMask = new BitMask($aclPermission->getAclResource()->getAclResourceEntityType());
        $description = $aclPermission->getBitmask(). ':' . $bitMask->getBitMask($actions);
        $aclPermission->setActions($actions);

        $this->getEventService()->addEvent(
            PermissionEvent::TYPE_CHANGE,
            $aclPermission,
            $updatedBy,
            null,
            false
        );

        $this->getHistoryEventService()->addEvent(
            PermissionEvent::TYPE_CHANGE,
            $aclPermission,
            $updatedBy,
            $description
        );

        $this->getEntityManager()->flush();

        return $aclPermission;
    }

    /**
     * @param $aclPermission
     * @param null $deletedBy
     * @throws NotFoundException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function deletePermission($aclPermission, $deletedBy = null)
    {
        if (is_numeric($aclPermission)) {
            $aclPermissionId = $aclPermission;
            $aclPermission = $this->getEntityManager()->find(AclPermission::getEntityName(), $aclPermissionId);
            if (!$aclPermission) {
                throw new NotFoundException('Permission not found');
            }
        }

        if ($deletedBy !== null && is_numeric($deletedBy)) {
            $userId = $deletedBy;
            $deletedBy = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        $aclPermission->setIsDeleted(true);

        $this->getEventService()->addEvent(
            PermissionEvent::TYPE_DELETE,
            $aclPermission = $this->getEntityManager()->getReference(
                AclPermission::getEntityName(),
                $aclPermission->getId()
            ),
            $deletedBy
        );

        $this->getEntityManager()->flush();
    }

    /**
     * @param $permissionId
     * @return null|AclPermission
     */
    public function getPermission($permissionId)
    {
        /** @var AclPermission $permission */
        $permission = $this->getEntityManager()->find(AclPermission::getEntityName(), $permissionId);
        return $permission;
    }

    public function getPortalPermissions(User $user)
    {
        $aclRoles = $this->getAclRolesForUser($user);

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select(array('p', 'res'))
            ->from(AclPermission::getEntityName(), 'p')
            ->innerJoin(
                'p.acl_resource',
                'res',
                Join::WITH,
                'res INSTANCE OF entity:Acl\AclResource\AclPortalResource'
            )
            ->andWhere('p.is_deleted != 1')
            ->andWhere('p.acl_role IN (:acl_roles)')
            ->setParameter('acl_roles', $aclRoles)
            ->andWhere('p.bit_mask > 0');

        $query = $qb->getQuery();

        return $query->getResult();
    }

    public function getWorkspacePermissions(User $user, Portal $portal)
    {
        $aclRoles = $this->getAclRolesForUser($user);

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select(array('p', 'res'))
            ->from(AclPermission::getEntityName(), 'p')
            ->innerJoin(
                'p.acl_resource',
                'res',
                Join::WITH,
                'res INSTANCE OF entity:Acl\AclResource\AclWorkspaceResource'
            )
            ->andWhere('p.is_deleted != 1')
            ->andWhere('p.portal = :portal')
            ->setParameter('portal', $portal)
            ->andWhere('p.acl_role IN (:acl_roles)')
            ->setParameter('acl_roles', $aclRoles)
            ->andWhere('p.bit_mask > 0');

        $query = $qb->getQuery();

        $result = $query->getResult();

        return $result;
    }

    public function getFilePermissions(User $user, Workspace $workspace, $excludeNone = true)
    {
        $aclRoles = $this->getAclRolesForUser($user);

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select(array('p', 'res'))
            ->from(AclPermission::getEntityName(), 'p')
            ->innerJoin(
                'p.acl_resource',
                'res',
                Join::WITH,
                'res INSTANCE OF entity:Acl\AclResource\AclFileResource'
            )
            ->innerJoin(File::getEntityName(), 'f', Join::WITH, 'res.entity_id = f.id')
            ->andWhere('f.workspace = :workspace')
            ->setParameter('workspace', $workspace)
            ->andWhere('p.is_deleted != 1')
            ->andWhere('f.is_deleted != 1')
            ->andWhere('p.portal = :portal')
            ->setParameter('portal', $workspace->getPortal())
            ->andWhere('p.acl_role IN (:acl_roles)')
            ->setParameter('acl_roles', $aclRoles)
            ->andWhere('p.bit_mask > 0');
        if ($excludeNone) {
            $qb->
            andWhere('p.bit_mask <> ' .
                PermissionType::getPermissionTypeBitMask(File::RESOURCE_ID, PermissionType::FILE_NONE));
        }
        $query = $qb->getQuery();

        $result = $query->getResult();

        return $result;
    }

    public function getRootFilePermissions(User $user, Workspace $workspace)
    {
        $aclRoles = $this->getAclRolesForUser($user);

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select(array('p', 'res'))
            ->from(AclPermission::getEntityName(), 'p')
            ->innerJoin(
                'p.acl_resource',
                'res',
                Join::WITH,
                'res INSTANCE OF entity:Acl\AclResource\AclFileResource'
            )
            ->innerJoin(File::getEntityName(), 'f', Join::WITH, 'res.entity_id = f.id')
            ->andWhere('f.parent IS NULL')
            ->andWhere('f.workspace = :workspace')
            ->setParameter('workspace', $workspace)
            ->andWhere('p.is_deleted != 1')
            ->andWhere('f.is_deleted != 1')
            ->andWhere('p.portal = :portal')
            ->setParameter('portal', $workspace->getPortal())
            ->andWhere('p.acl_role IN (:acl_roles)')
            ->setParameter('acl_roles', $aclRoles)
            ->andWhere('p.bit_mask > 0')
            ->andWhere('p.bit_mask <> ' .
                PermissionType::getPermissionTypeBitMask(File::RESOURCE_ID, PermissionType::FILE_NONE));

        $query = $qb->getQuery();

        $result = $query->getResult();

        return $result;
    }

    public function getFolderFilePermissions(User $user, File $folder)
    {
        $aclRoles = $this->getAclRolesForUser($user);

        $qb = $this->getEntityManager()->createQueryBuilder();
        $portal = $folder->getWorkspace()->getPortal();
        $qb->select(array('p', 'res'))
            ->from(AclPermission::getEntityName(), 'p')
            ->innerJoin(
                'p.acl_resource',
                'res',
                Join::WITH,
                'res INSTANCE OF entity:Acl\AclResource\AclFileResource'
            )
            ->innerJoin(File::getEntityName(), 'f', Join::WITH, 'res.entity_id = f.id')
            ->andWhere('f.parent = :folder')
            ->setParameter('folder', $folder)
            ->andWhere('p.is_deleted != 1')
            ->andWhere('f.is_deleted != 1')
            ->andWhere('p.portal = :portal')
            ->setParameter('portal', $portal)
            ->andWhere('p.acl_role IN (:acl_roles)')
            ->setParameter('acl_roles', $aclRoles)
            ->andWhere('p.bit_mask > 0');

        $query = $qb->getQuery();

        $result = $query->getResult();

        return $result;
    }

    public function getResourcePermissions(File $resource)
    {

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select(array('p', 'res'))
            ->from(AclPermission::getEntityName(), 'p')
            ->innerJoin(
                'p.acl_resource',
                'res',
                Join::WITH,
                'res INSTANCE OF entity:Acl\AclResource\AclFileResource'
            )
            ->innerJoin(File::getEntityName(), 'f', Join::WITH, 'res.entity_id = ' . $resource->getId())
            ->andWhere('p.is_deleted != 1')
            ->andWhere('f.is_deleted != 1');

        $query = $qb->getQuery();

        $result = $query->getResult();

        return $result;
    }

    public function getAllResourcePermissions(File $resource)
    {

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select(array('p', 'res'))
            ->from(AclPermission::getEntityName(), 'p')
            ->innerJoin(
                'p.acl_resource',
                'res',
                Join::WITH,
                'res INSTANCE OF entity:Acl\AclResource\AclFileResource'
            )
            ->innerJoin(File::getEntityName(), 'f', Join::WITH, 'res.entity_id = ' . $resource->getId());

        $query = $qb->getQuery();

        $result = $query->getResult();

        return $result;
    }

    public function getAllWorkspacePermissions(Workspace $resource)
    {

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select(array('p', 'res'))
            ->from(AclPermission::getEntityName(), 'p')
            ->innerJoin(
                'p.acl_resource',
                'res',
                Join::WITH,
                'res INSTANCE OF entity:Acl\AclResource\AclWorkspaceResource'
            )
            ->innerJoin(Workspace::getEntityName(), 'w', Join::WITH, 'res.entity_id = ' . $resource->getId());

        $query = $qb->getQuery();

        $result = $query->getResult();

        return $result;
    }
    public function getFilePermissionsForWorkspace(Workspace $workspace)
    {
        $em = $this->getEntityManager();
        $sql = "select p.bit_mask, r.entity_id as resource_entity_id,"
        ." ro.entity_id as role_entity_id, ro.entity_type as role_entity_type"
        ." from acl_permissions p, acl_resources r, acl_roles ro, files f"
        ." where f.workspace_id = ". $workspace->getId() ." and r.entity_id = f.id"
        ." and r.entity_type='file' and p.acl_resource_id = r.id and p.is_deleted = 0 and p.acl_role_id = ro.id";
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $permissions = $stmt->fetchAll();
        $permissionsArray = array();
        foreach ($permissions as $permission) {
            $permissionsArray[$permission['resource_entity_id']][] = array(
                'bit_mask' => $permission['bit_mask'],
                'role_entity_id' => $permission['role_entity_id'],
                'role_entity_type' => $permission['role_entity_type']
            );
        }
        return $permissionsArray;
    }

    private function getAclRolesForUser(User $user, Portal $portal = null)
    {
        if ($this->aclRolesCache->offsetExists($user->getId())) {
            return $this->aclRolesCache->offsetGet($user->getId());
        }

        $userAclRole = $user->getAclRole();
        $groupAclRoles = $this->getAclGroupRoles($user, $portal);

        $aclRoles = array();

        if ($groupAclRoles) {
            $aclRoles = array_merge($aclRoles, $groupAclRoles);
        }

        if ($userAclRole) {
            array_push($aclRoles, $userAclRole);
        }

        $this->aclRolesCache->offsetSet($user->getId(), $aclRoles);

        return $aclRoles;
    }

    private function getAclGroupRoles(User $user, Portal $portal = null)
    {
        $userService = $this->getContainer()->get('UserService');
        $userGroups = $userService->getUserGroups($user, $portal);

        $groupsAclRoles = $this->getEntityManager()->getRepository(AclGroupRole::getEntityName())->findBy(array(
            'group' => $userGroups
        ));

        return $groupsAclRoles;
    }

    /**
     * @param HavingDynamicPermissionsResourceInterface $resource
     * @return array
     */
    public function getResourceUsers(HavingDynamicPermissionsResourceInterface $resource)
    {
        $userIds = $this->getResourceUserIds($resource);

        $userService = $this->getUserService();

        if (empty($userIds)) {
            return array();
        }

        $users = $userService->getUsersByIds($userIds);
        return $users;
    }

    /**
     * @param HavingDynamicPermissionsResourceInterface $resource
     * @return array
     */
    public function getResourceUserIds(HavingDynamicPermissionsResourceInterface $resource)
    {
        $userIds = array();

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select(array('ar',))
            ->from(AclRole::getEntityName(), 'ar')
            ->innerJoin('ar.acl_permissions', 'p')
            ->where('p.is_deleted != 1')
            ->andWhere('p.acl_resource = :acl_resource')
            ->setParameter('acl_resource', $resource->getAclResource());

        $aclRoles = $qb->getQuery()->getResult();

        /** @var AclRole $aclRole */
        foreach ($aclRoles as $aclRole) {
            switch ($aclRole->getEntityType()) {
                case AclUserRole::ACL_ROLE_ENTITY_TYPE:
                    $userId = $aclRole->getUser()->getId();
                    if (!in_array($userId, $userIds)) {
                        array_push($userIds, $userId);
                    }
                    break;
                case AclGroupRole::ACL_ROLE_ENTITY_TYPE:
                    $groupService = $this->getGroupService();
                    $groupMemberships = $groupService->getGroupMemberships($aclRole->getGroup());
                    foreach ($groupMemberships as $groupMembership) {
                        $userId = $groupMembership->getUser()->getId();
                        if (!in_array($userId, $userIds)) {
                            array_push($userIds, $userId);
                        }
                    }
                    break;
            }
        }

        return $userIds;
    }

    /**
     * @param $uid
     * @param HavingDynamicPermissionsResourceInterface $resource
     * @return boolean
     */
    public function isInResourceUserIds($uid, HavingDynamicPermissionsResourceInterface $resource)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select(array('ar',))
            ->from(AclRole::getEntityName(), 'ar')
            ->innerJoin('ar.acl_permissions', 'p')
            ->where('p.is_deleted != 1')
            ->andWhere('p.bit_mask > 0')
            ->andWhere('p.bit_mask <> ' .
                PermissionType::getPermissionTypeBitMask(File::RESOURCE_ID, PermissionType::FILE_NONE))
            ->andWhere('p.acl_resource = :acl_resource')
            ->setParameter('acl_resource', $resource->getAclResource());

        $aclRoles = $qb->getQuery()->getResult();

        /** @var AclRole $aclRole */
        foreach ($aclRoles as $aclRole) {
            switch ($aclRole->getEntityType()) {
                case AclUserRole::ACL_ROLE_ENTITY_TYPE:
                    $userId = $aclRole->getUser()->getId();
                    if ($userId == $uid) {
                        return true;
                    }
                    break;
                case AclGroupRole::ACL_ROLE_ENTITY_TYPE:
                    $groupService = $this->getGroupService();
                    $groupMemberships = $groupService->getGroupMemberships($aclRole->getGroup());
                    foreach ($groupMemberships as $groupMembership) {
                        $userId = $groupMembership->getUser()->getId();
                        if ($userId == $uid) {
                            return true;
                        }
                    }
                    break;
            }
        }

        return false;
    }

    /**
     * @param $workspace
     */
    public function removeHPermissionForPortal($portal)
    {
        $em = $this->getEntityManager();
        $pdo = $em->getConnection();
        $sql = 'DELETE FROM acl_permissions where portal_id='.$portal->getId();

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
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
     * @return UserService
     */
    public function getUserService()
    {
        if (!$this->userService) {
            $this->userService = $this->getContainer()->get('UserService');
        }
        return $this->userService;
    }

    /**
     * @param $user
     * @param Portal $portal
     * @return bool
     */
    public function isPortalAdmin($user, Portal $portal)
    {
        return $this->hasPermission(
            $user,
            $portal,
            PermissionType::PORTAL_ADMIN,
            $portal
        );
    }

    /**
     * @param $user
     * @param Workspace $workspace
     * @return bool
     */
    public function isWorkspaceAdmin($user, Workspace $workspace)
    {
        return $this->hasPermission(
            $user,
            $workspace,
            PermissionType::WORKSPACE_ADMIN,
            $workspace->getPortal()
        );
    }
    
    /**
     * @param $user
     * @param HavingDynamicPermissionsResourceInterface $resource
     * @param $permissionTypes
     * @param Portal $portal
     * @return bool
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function hasPermission(
        $user,
        HavingDynamicPermissionsResourceInterface $resource,
        $permissionTypes,
        Portal $portal = null
    ) {
        if (is_numeric($user)) {
            $userId = $user;
        } else {
            $userId = $user->getId();
        }

        $cacheKey = 'user' . $userId . '#' . $resource->getResourceId() . $resource->getId();

        if ($this->bitmaskCache->offsetExists($cacheKey)) {
            //getting permissions from cache
            $totalPermissionsBitMask = $this->bitmaskCache->offsetGet($cacheKey);
        } else {
            //getting permissions from database
            if (is_numeric($user)) {
                /** @var User $user */
                $user = $this->getEntityManager()->find(User::getEntityName(), $userId);
            }

            $permissions = $this->getPermissions($resource, $user, $portal);

            $totalPermissionsBitMask = 0;
            foreach ($permissions as $permission) {
                $totalPermissionsBitMask |= $permission->getBitMask();
            }
            //save permissions to cache
            $this->bitmaskCache->offsetSet($cacheKey, $totalPermissionsBitMask);
        }

        //calculating required permissions bitmask
        if (!is_array($permissionTypes)) {
            $permissionTypes = array($permissionTypes);
        }
        $totalRequiredPermissionsBitMask = 0;
        foreach ($permissionTypes as $permissionType) {
            $permissionTypeBitMask = PermissionType::getPermissionTypeBitMask(
                $resource->getResourceId(),
                $permissionType
            );
            $totalRequiredPermissionsBitMask |= $permissionTypeBitMask;
        }

        //comparing actual and required permission bitmasks
        $result = (($totalPermissionsBitMask & $totalRequiredPermissionsBitMask) != 0);

        return $result;
    }

    /**
     * @param HavingDynamicPermissionsResourceInterface $resource
     * @param HavingAclRoleInterface $grantedTo
     * @param null $portal
     * @return array
     */
    public function getPermissions(
        HavingDynamicPermissionsResourceInterface $resource,
        HavingAclRoleInterface $grantedTo = null,
        $portal = null
    ) {

        $aclResource = $resource->getAclResource();

        $repository = $this->getEntityManager()->getRepository(AclPermission::getEntityName());

        if ($grantedTo) {
            if ($grantedTo instanceof User) {
                $aclRoles = $this->getAclRolesForUser($grantedTo, $portal);
            } else {
                $aclRoles = $grantedTo->getAclRole();
            }

            $permissions = $repository->findBy(array(
                'acl_resource' => $aclResource,
                'acl_role' => $aclRoles,
                'is_deleted' => 0
            ));
        } else {
            $permissions = $repository->findBy(array(
                'acl_resource' => $aclResource,
                'is_deleted' => 0
            ));
        }

        return $permissions;
    }

    /**
     * @param HavingDynamicPermissionsResourceInterface $resource
     * @return array
     */
    public function getAllPermissions(
        HavingDynamicPermissionsResourceInterface $resource
    ) {
        $aclResource = $resource->getAclResource();
        $repository = $this->getEntityManager()->getRepository(AclPermission::getEntityName());
        $permissions = $repository->findBy(array(
            'acl_resource' => $aclResource
        ));
        return $permissions;
    }
    /**
     * @param int|User $user
     * @param AclRole $aclRole
     * @param Portal $portal
     * @return bool
     */
    public function hasAclRole($user, $aclRole, $portal = null)
    {
        if (is_numeric($user)) {
            $userId = $user;
            /** @var User $user */
            $user = $this->getEntityManager()->find(User::getEntityName(), $userId);
        }

        $userAclRoles = $this->getAclRolesForUser($user, $portal);

        foreach ($userAclRoles as $userAclRole) {
            if ($userAclRole->getId() == $aclRole->getId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param File $resource
     * @param User $user
     * @param Portal $portal
     * @return array
     */
    public function getPermissionsIncludingInherited(File $resource, User $user = null, Portal $portal = null)
    {
        if ($resource->getParent()) {
            $inheritedPermissions = $this->getPermissionsIncludingInherited($resource->getParent(), $user, $portal);
        } else {
            $inheritedPermissions = array();
        }

        $permissions = $this->getPermissions($resource, $user, $portal);

        if ($inheritedPermissions) {
            $permissions = array_merge($permissions, $inheritedPermissions);
        }

        return $permissions;
    }

    /**
     * @param File $resource
     * @param User $user
     * @param Portal $portal
     * @return array
     */
    public function getFirstFoundPermissions(File $resource, User $user = null, Portal $portal = null)
    {
        if ($resource->getParent()) {
            $inheritedPermissions = $this->getFirstFoundPermissions($resource->getParent(), $user, $portal);
        } else {
            $inheritedPermissions = array();
        }
        $permissions = $this->getPermissions($resource, $user, $portal);
        if ($inheritedPermissions) {
            if ($permissions) {
                foreach ($permissions as $permission) {
                    foreach ($inheritedPermissions as $key => $inheritedPermission) {
                        if ($inheritedPermission->getAclRole() == $permission->getAclRole()) {
                            unset($inheritedPermissions[$key]);
                            continue;
                        }
                    }
                }
            }
            $permissions = array_merge($permissions, $inheritedPermissions);
        }
        return $permissions;
    }

    /**
     * @return Acl
     */
    public function getAcl()
    {
        return $this->getApp()->acl;
    }
}
