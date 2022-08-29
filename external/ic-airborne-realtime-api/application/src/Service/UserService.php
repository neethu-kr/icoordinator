<?php

namespace iCoordinator\Service;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use iCoordinator\Entity\Group;
use iCoordinator\Entity\GroupMembership;
use iCoordinator\Entity\HistoryEvent\UserHistoryEvent;
use iCoordinator\Entity\Portal;
use iCoordinator\Entity\User;
use iCoordinator\Service\Exception\ConflictException;
use iCoordinator\Service\Exception\NotFoundException;
use iCoordinator\Service\Exception\ValidationFailedException;
use Laminas\Hydrator\ClassMethodsHydrator;

/**
 * Class UserService
 * @package iCoordinator\Service
 */
class UserService extends AbstractService
{
    const USERS_LIMIT_DEFAULT = 100;
    const GROUPS_LIMIT_DEFAULT = 100;
    const GROUP_MEMBERSHIPS_LIMIT_DEFAULT = 100;

    /**
     * @var PermissionService
     */
    private $permissionService;

    /**
     * @var OutboundEmailService
     */
    private $outboundEmailService;

    /**
     * @var HistoryEventService
     */
    private $historyEventService;

    /**
     * @param $userId
     * @return null|User
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function getUser($userId)
    {
        $user = $this->getEntityManager()->find(User::ENTITY_NAME, $userId);

        return $user;
    }

    /**
     * @param $uuid
     * @return null|User
     */
    public function getUserByUuid($uuid)
    {
        $user = $this->getEntityManager()->getRepository(User::ENTITY_NAME)->findOneBy(array(
           'uuid' => $uuid
        ));

        return $user;
    }

    public function getUsers($limit = self::USERS_LIMIT_DEFAULT, $offset = 0)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select(array('u'))
            ->from(User::ENTITY_NAME, 'u')
            ->where('u.is_deleted != 1');

        if ($limit !== null) {
            $qb->setFirstResult($offset)
                ->setMaxResults($limit);
        }

        $query = $qb->getQuery();
        $paginator = new Paginator($query, false);

        return $paginator;
    }

    /**
     * @param array $userIds
     * @return array
     */
    public function getUsersByIds(array $userIds)
    {
        if (empty($userIds)) {
            return array();
        }

        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select(array('u'))
            ->from(User::ENTITY_NAME, 'u')
            ->where($qb->expr()->in('u.id', $userIds));

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array $data
     * @return User
     * @throws ConflictException
     * @throws ValidationFailedException
     */
    public function createUser(array $data)
    {
        if (empty($data['email'])) {
            throw new ValidationFailedException();
        }

        if ($this->checkEmailExists($data['email'])) {
            throw new ConflictException();
        }

        if (empty($data['password'])) {
            $data['password'] = $this->generatePassword();
        }

        $hydrator = new ClassMethodsHydrator();

        $user = new User();

        if (!empty($data["locale"])) {
            $locale = $user->getLocale();
            $hydrator->hydrate($data["locale"], $locale);
            $user->setLocale($locale);
            unset($data["locale"]);
        }

        $hydrator->hydrate($data, $user);

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
        $this->getHistoryEventService()->addEvent(
            UserHistoryEvent::TYPE_CREATE,
            $user,
            null
        );

        return $user;
    }

    /**
     * @param $email
     * @return bool
     */
    public function checkEmailExists($email)
    {
        $repository = $this->getEntityManager()->getRepository(User::ENTITY_NAME);
        $user = $repository->findOneBy(array(
            'email' => $email
        ));

        if ($user) {
            return true;
        }

        return false;
    }

    /**
     * @param $email
     * @return null|User
     */
    public function getUserByEmail($email)
    {
        $user = $this->getEntityManager()->getRepository(User::ENTITY_NAME)->findOneBy(array(
            'email' => $email
        ));

        return $user;
    }

    /**
     * @return OutboundEmailService
     */
    private function getOutboundEmailService()
    {
        if (!$this->outboundEmailService) {
            $this->outboundEmailService = $this->getContainer()->get('OutboundEmailService');
        }

        return $this->outboundEmailService;
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
     * @param $user
     * @param $data
     * @param $updatedBy
     * @return User
     * @throws NotFoundException
     * @throws \Doctrine\ORM\ORMException
     */
    public function updateUser($user, $data, $updatedBy)
    {
        if (is_numeric($user)) {
            $userId = $user;
            /** @var User $user */
            $user = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        if (is_numeric($updatedBy)) {
            $userId = $updatedBy;
            $updatedBy = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        $hydrator = new ClassMethodsHydrator();
        if (!empty($data["locale"])) {
            $locale = $user->getLocale();
            $hydrator->hydrate($data["locale"], $locale);
            $user->setLocale($locale);
            unset($data["locale"]);
        }
        try {
            $hydrator->hydrate($data, $user);
            $this->getEntityManager()->merge($user);
            $this->getEntityManager()->flush();
        } catch (EntityNotFoundException $e) {
            throw new NotFoundException();
        }

        return $user;
    }

    /**
     * @param $user
     * @param $deletedBy
     * @throws NotFoundException
     * @throws \Doctrine\ORM\ORMException
     */
    public function deleteUser($user, $deletedBy)
    {
        if (is_numeric($user)) {
            $userId = $user;
            /** @var User $user */
            $user = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        if (is_numeric($deletedBy)) {
            $userId = $deletedBy;
            /** @var User $deletedBy */
            $deletedBy = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        try {
            $user->setIsDeleted(true);
            $this->getEntityManager()->merge($user);
        } catch (EntityNotFoundException $e) {
            throw new NotFoundException();
        }

        $this->getEntityManager()->flush();
    }

    /**
     * @param $user
     * @param $portal
     * @param int $limit
     * @param int $offset
     * @return Paginator
     * @throws \Doctrine\ORM\ORMException
     */
    public function getUserGroupMemberships($user, $portal, $limit = null, $offset = 0)
    {
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

        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select(array('gm'))
            ->from(GroupMembership::ENTITY_NAME, 'gm')
            ->innerJoin('gm.group', 'g')
            ->andWhere('gm.user = :user')
            ->setParameter('user', $user)
            ->andWhere('g.portal = :portal')
            ->setParameter('portal', $portal);

        if ($limit !== null) {
            $qb->setFirstResult($offset)
                ->setMaxResults($limit);
        }

        $query = $qb->getQuery();
        $paginator = new Paginator($query, false);

        return $paginator;
    }

    /**
     * @param $user
     * @param $portal
     * @param int $limit
     * @param int $offset
     * @return Paginator
     * @throws \Doctrine\ORM\ORMException
     */
    public function getUserGroups($user, $portal = null, $limit = null, $offset = 0)
    {
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

        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select(array('g'))
            ->from(Group::ENTITY_NAME, 'g')
            ->innerJoin('g.group_memberships', 'gm')
            ->andWhere('gm.user = :user')
            ->setParameter('user', $user);

        if ($portal !== null) {
            $qb->andWhere('g.portal = :portal')
               ->setParameter('portal', $portal);
        }

        if ($limit !== null) {
            $qb->setFirstResult($offset)
                ->setMaxResults($limit);

            $query = $qb->getQuery();
            $paginator = new Paginator($query, false);

            return $paginator;
        } else {
            return $qb->getQuery()->getResult();
        }
    }

    /**
     * @param $token
     * @return User
     * @throws NotFoundException
     */
    public function userPasswordReset($user)
    {


        $newPassword = $this->generatePassword();

        $user->setPassword($newPassword);
        $this->getEntityManager()->merge($user);
        $this->getEntityManager()->flush();



        //send password reset email
        $this->getOutboundEmailService()
            ->setTo($user->getEmail())->setLang($user->getLocale()->getLang())
            ->sendPasswordResetNotification($user, $newPassword);

        return $user;
    }

    /**
     * @return string
     */
    private function generatePassword()
    {
        $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
        $pass = array();
        $alphaLength = strlen($alphabet) - 1;
        for ($i = 0; $i < 8; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass);
    }
}
