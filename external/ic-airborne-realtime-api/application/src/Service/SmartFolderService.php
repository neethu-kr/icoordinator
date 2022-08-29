<?php

namespace iCoordinator\Service;

use Carbon\Carbon;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Tools\Pagination\Paginator;
use iCoordinator\Entity\File;
use iCoordinator\Entity\Folder;
use iCoordinator\Entity\HistoryEvent\SmartFolderHistoryEvent;
use iCoordinator\Entity\MetaField;
use iCoordinator\Entity\MetaFieldCriterion;
use iCoordinator\Entity\Permission;
use iCoordinator\Entity\SmartFolder;
use iCoordinator\Entity\User;
use iCoordinator\Entity\Workspace;
use iCoordinator\Service\Exception\ConflictException;
use iCoordinator\Service\Exception\NotFoundException;
use iCoordinator\Service\Exception\ValidationFailedException;
use iCoordinator\Service\Helper\FileServiceHelper;
use Laminas\Hydrator\ClassMethodsHydrator;

/**
 * Class SmartFolderService
 * @package iCoordinator\Service
 */
class SmartFolderService extends DriveItemService
{
    const SMART_FOLDER_CHILDREN_LIMIT_DEFAULT = 100;
    const ROOT_FOLDER_ID = 0;

    /**
     * @var HistoryEventService
     */
    protected $historyEventService;

    /**
     * @param $smartFolder
     * @return array
     */
    public function getSmartFolderUsers($smartFolder)
    {
        return $this->getDriveItemUsers($smartFolder);
    }

    /**
     * @param $smartFolder
     * @return array
     */
    public function getSmartFolderUserIds($smartFolder)
    {
        return $this->getDriveItemUserIds($smartFolder);
    }

    /**
     * @param array $data
     * @param $workspaceId
     * @param $userId
     * @return SmartFolder
     * @throws \InvalidArgumentException
     * @throws ValidationFailedException
     */
    public function createSmartFolder(array $data, $workspaceId, $userId)
    {
        if (empty($data['name'])) {
            throw new ValidationFailedException();
        }

        $smartFolder = new SmartFolder();

        if (!empty($data['parent'])) {
            FileServiceHelper::updateParentFolder($smartFolder, $data['parent'], $this->getEntityManager());
            unset($data['parent']);
        }

        $createdBy = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        $workspace = $this->getEntityManager()->getReference(Workspace::ENTITY_NAME, $workspaceId);

        $smartFolder->setCreatedBy($createdBy)
            ->setWorkspace($workspace);

        $data['size'] = 0;

        $hydrator = new ClassMethodsHydrator();
        $hydrator->hydrate($data, $smartFolder);

        $this->getEntityManager()->persist($smartFolder);

        if ($this->getAutoCommitChanges()) {
            $this->getEntityManager()->flush();
        }

        //add permissions
        if ($smartFolder->getParent()) {
            $smartFolder->setOwnedBy($smartFolder->getParent()->getOwnedBy());
        } else {
            $smartFolder->setOwnedBy($createdBy);
        }
        $this->getHistoryEventService()->addEvent(
            SmartFolderHistoryEvent::TYPE_CREATE,
            $smartFolder,
            $createdBy,
            $smartFolder->getName()
        );
        return $smartFolder;
    }

    /**
     * @param $smartFolder
     * @param int $limit
     * @param int $offset
     * @return Paginator
     */
    public function getSmartFolderChildren(
        $smartFolder,
        $limit = self::SMART_FOLDER_CHILDREN_LIMIT_DEFAULT,
        $offset = 0
    ) {
        if (!$smartFolder instanceof SmartFolder) {
            $smartFolderId = $smartFolder;
            $smartFolder = $this->getSmartFolder($smartFolderId);
        }
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select(array('f'))
            ->from(File::ENTITY_NAME, 'f')
            ->andWhere('f.workspace = :workspace')
            ->setParameter('workspace', $smartFolder->getWorkspace())
            ->andWhere('f.is_trashed != 1')
            ->andWhere('f.is_deleted != 1');

        $criteria = $smartFolder->getMetaFieldsCriteria();
        $metaFieldsIds = array();

        foreach ($criteria as $criterion) {
            /** @var MetaFieldCriterion $criterion */
            $type = null;
            $isNumericValue = ($criterion->getMetaField()->getType() == MetaField::TYPE_NUMBER);
            $isInequalityCondition = in_array($criterion->getConditionType(), array(
                MetaFieldCriterion::CONDITION_LESS,
                MetaFieldCriterion::CONDITION_LESS_OR_EQUALS,
                MetaFieldCriterion::CONDITION_GREATER,
                MetaFieldCriterion::CONDITION_GREATER_OR_EQUALS
            ));
            if ($isInequalityCondition && $isNumericValue) {
                if (strstr($criterion->getValue(), '.') >= 0) {
                    $type = Type::FLOAT;
                } else {
                    $type = Type::INTEGER;
                }
            }

            $metaFieldId = $criterion->getMetaField()->getId();
            array_push($metaFieldsIds, $metaFieldId);
            $fieldName = 'm' . $metaFieldId . '.value';
            $paramName = 'c' . $criterion->getId();
            $paramValue = $criterion->getValue();
            switch ($criterion->getConditionType()) {
                case MetaFieldCriterion::CONDITION_EQUALS:
                    $qb->andWhere($fieldName . ' = :' . $paramName);
                    break;
                case MetaFieldCriterion::CONDITION_NOT_EQUALS:
                    $qb->andWhere($fieldName . ' != :' . $paramName);
                    break;
                case MetaFieldCriterion::CONDITION_CONTAINS:
                    $qb->andWhere($fieldName . ' LIKE :' . $paramName);
                    $paramValue = '%' . $paramValue . '%';
                    break;
                case MetaFieldCriterion::CONDITION_NOT_CONTAINS:
                    $qb->andWhere($fieldName . ' NOT LIKE :' . $paramName);
                    $paramValue = '%' . $paramValue . '%';
                    break;
                case MetaFieldCriterion::CONDITION_LESS:
                    $qb->andWhere($fieldName . ' < :' . $paramName);
                    break;
                case MetaFieldCriterion::CONDITION_GREATER:
                    $qb->andWhere($fieldName . ' > :' . $paramName);
                    break;
                case MetaFieldCriterion::CONDITION_LESS_OR_EQUALS:
                    $qb->andWhere($fieldName . ' <= :' . $paramName);
                    break;
                case MetaFieldCriterion::CONDITION_GREATER_OR_EQUALS:
                    $qb->andWhere($fieldName . ' >= :' . $paramName);
                    break;
            }
            $qb->setParameter($paramName, $paramValue, $type);
        }

        $metaFieldsIds = array_unique($metaFieldsIds);
        foreach ($metaFieldsIds as $metaFieldId) {
            $alias = 'm' . $metaFieldId;
            $qb->join(
                'f.meta_fields_values',
                $alias,
                Join::WITH,
                $alias . '.meta_field = ' . $metaFieldId,
                $alias . '.id'
            );
        }

        if ($limit !== null) {
            $qb->setFirstResult($offset)
                ->setMaxResults($limit);
        }

        $query = $qb->getQuery();
        $paginator = new Paginator($query, false);

        return $paginator;
    }

    /**
     * @param $smartFolderId
     * @return null|SmartFolder
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function getSmartFolder($smartFolderId)
    {
        $smartFolder = $this->getEntityManager()->find(SmartFolder::ENTITY_NAME, $smartFolderId);
        return $smartFolder;
    }

    /**
     * @param int|SmartFolder $smartFolder
     * @param $user
     * @param bool $permanently
     * @throws NotFoundException
     * @throws \Doctrine\ORM\ORMException
     */
    public function deleteSmartFolder($smartFolder, $user, $permanently = false)
    {
        if (is_numeric($user)) {
            $userId = $user;
            /** @var User $user */
            $user = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        if (is_numeric($smartFolder)) {
            $smartFolderId = $smartFolder;
            $smartFolder = $this->getEntityManager()->getReference(SmartFolder::ENTITY_NAME, $smartFolderId);
        }

        if ($permanently) {
            $smartFolder->setIsDeleted(true);
        } else {
            $smartFolder->setIsTrashed(true);
        }

        try {
            $this->getEntityManager()->merge($smartFolder);
        } catch (EntityNotFoundException $e) {
            throw new NotFoundException();
        }
        $this->getHistoryEventService()->addEvent(
            SmartFolderHistoryEvent::TYPE_DELETE,
            $smartFolder,
            $user,
            $smartFolder->getName()
        );
        if ($this->getAutoCommitChanges()) {
            $this->getEntityManager()->flush();
        }
    }


    /**
     * @param $smartFolder
     * @param array $data
     * @param $user
     * @return bool|\Doctrine\Common\Proxy\Proxy|null|object
     * @throws ConflictException
     * @throws NotFoundException
     * @throws \Doctrine\ORM\ORMException
     * @TODO: should not be possible to change workspace
     * @TODO: should not be possible to move parent folder into the child folder
     */
    public function updateSmartFolder($smartFolder, array $data, $user)
    {
        $historyInfo = '';
        if (is_numeric($user)) {
            $userId = $user;
            /** @var User $user */
            $user = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        if (is_numeric($smartFolder)) {
            $smartFolderId = $smartFolder;
            $smartFolder = $this->getEntityManager()->getReference(SmartFolder::ENTITY_NAME, $smartFolderId);
        }

        if (isset($data['parent'])) {
            $parentFolder = FileServiceHelper::updateParentFolder(
                $smartFolder,
                $data['parent'],
                $this->getEntityManager()
            );
            unset($data['parent']);
        } else {
            $parentFolder = $smartFolder->getParent();
        }

        if (!empty($data['name'])) {
            if ($this->checkNameExists(
                $data['name'],
                $smartFolder->getWorkspace(),
                $parentFolder,
                $smartFolder->getId()
            )) {
                throw new ConflictException();
            } else {
                $historyInfo = $smartFolder->getName() . " -> " . $data['name'];
            }
        }
        $this->getHistoryEventService()->addEvent(
            SmartFolderHistoryEvent::TYPE_UPDATE,
            $smartFolder,
            $user,
            $historyInfo
        );
        $hydrator = new ClassMethodsHydrator();
        $hydrator->hydrate($data, $smartFolder);

        try {
            $this->getEntityManager()->merge($smartFolder);
        } catch (EntityNotFoundException $e) {
            throw new NotFoundException();
        }

        if ($this->getAutoCommitChanges()) {
            $this->getEntityManager()->flush();
        }

        return $smartFolder;
    }

    /**
     * @param $name
     * @param Workspace $workspace
     * @param Folder $folder
     * @return bool
     */
    private function checkSmartFolderNameExists($name, Workspace $workspace, Folder $folder = null)
    {
        return FileServiceHelper::checkFileNameExists(
            $name,
            $workspace,
            $folder,
            $this->getEntityManager()
        );
    }

    public function restoreSmartFolder($smartFolder, $user, array $data = null)
    {
        if (is_numeric($user)) {
            $userId = $user;
            /** @var User $user */
            $user = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        if (is_numeric($smartFolder)) {
            $smartFolderId = $smartFolder;
            $smartFolder = $this->getEntityManager()->find(SmartFolder::ENTITY_NAME, $smartFolderId);
            if (!$smartFolder) {
                throw new NotFoundException();
            }
        }

        if (isset($data['parent'])) {
            $parentFolder = FileServiceHelper::updateParentFolder(
                $smartFolder,
                $data['parent'],
                $this->getEntityManager()
            );
            unset($data['parent']);
        } else {
            $parentFolder = $smartFolder->getParent();
            if ($parentFolder->getIsTrashed() || $parentFolder->getIsDeleted()) {
                throw new ConflictException();
            }
        }

        if (!empty($data['name'])) {
            $smartFolder->setName($data['name']);
        }

        if ($this->checkNameExists($smartFolder->getName(), $smartFolder->getWorkspace(), $parentFolder)) {
            throw new ConflictException();
        }

        $smartFolder->setIsTrashed(false);

        if ($this->getAutoCommitChanges()) {
            $this->getEntityManager()->flush();
        }

        return $smartFolder;
    }

    public function getSmartFolderCriterion($metaFieldCriterionId)
    {
        $metaFieldCriterion = $this->getEntityManager()->find(MetaFieldCriterion::ENTITY_NAME, $metaFieldCriterionId);
        return $metaFieldCriterion;
    }

    public function getSmartFolderCriteria($smartFolder)
    {
        if (is_numeric($smartFolder)) {
            $smartFolderId = $smartFolder;
            /** @var SmartFolder $smartFolder */
            $smartFolder = $this->getEntityManager()->getReference(SmartFolder::ENTITY_NAME, $smartFolderId);
        }

        return $smartFolder->getMetaFieldsCriteria();
    }

    /**
     * @param $smartFolder
     * @param $data
     * @param null $addedBy
     * @return MetaFieldCriterion
     * @throws ValidationFailedException
     * @throws \Doctrine\ORM\ORMException
     */
    public function addSmartFolderCriterion($smartFolder, $data, $addedBy = null)
    {
        if (is_numeric($smartFolder)) {
            $smartFolderId = $smartFolder;
            $smartFolder = $this->getEntityManager()->getReference(SmartFolder::ENTITY_NAME, $smartFolderId);
        }

        if (!isset($data['value']) || !isset($data['condition_type'])) {
            throw new ValidationFailedException();
        }

        if (isset($data['meta_field']) && isset($data['meta_field']['id'])) {
            $metaFieldId = $data['meta_field']['id'];
            /** @var MetaField $metaField */
            $metaField = $this->getEntityManager()->getReference(MetaField::ENTITY_NAME, $metaFieldId);
            unset($data['meta_field']);
        }

        if (is_numeric($addedBy)) {
            $userId = $addedBy;
            /** @var User $deletedBy */
            $addedBy = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        $metaFieldCriterion = new MetaFieldCriterion();
        $metaFieldCriterion->setSmartFolder($smartFolder)
            ->setMetaField($metaField)
            ->setConditionType($data['condition_type'])
            ->setValue($data['value']);
        $smartFolder->getMetaFieldsCriteria()->add($metaFieldCriterion);
        $this->getHistoryEventService()->addEvent(
            SmartFolderHistoryEvent::TYPE_ADD_CRITERION,
            $smartFolder,
            $addedBy,
            $data['condition_type'].':'.$data['value']
        );
        $this->getEntityManager()->persist($metaFieldCriterion);
        $this->getEntityManager()->flush();

        return $metaFieldCriterion;
    }

    /**
     * @param $metaFieldCriterion
     * @param $data
     * @param null $updatedBy
     * @return MetaFieldCriterion
     * @throws NotFoundException
     * @throws ValidationFailedException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function updateSmartFolderCriterion($metaFieldCriterion, $data, $updatedBy = null)
    {
        if (is_numeric($metaFieldCriterion)) {
            $metaFieldCriterionId = $metaFieldCriterion;
            /** @var MetaFieldCriterion $metaFieldCriterion */
            $metaFieldCriterion = $this->getEntityManager()->find(
                MetaFieldCriterion::ENTITY_NAME,
                $metaFieldCriterionId
            );
        }

        if (!isset($data['value']) && !isset($data['condition'])) {
            throw new ValidationFailedException();
        }

        if (isset($data['meta_field'])) {
            unset($data['meta_field']); //not possible to update meta field reference
        }

        if (isset($data['value'])) {
            $value = $data['value'];
            //validation
            switch ($metaFieldCriterion->getMetaField()->getType()) {
                case MetaField::TYPE_NUMBER:
                    if (!is_numeric($value)) {
                        throw new ValidationFailedException();
                    }
                    break;
                case MetaField::TYPE_DATE:
                    if (!Carbon::createFromFormat(Carbon::ISO8601, $value)) {
                        throw new ValidationFailedException();
                    }
                    break;
                case MetaField::TYPE_LIST:
                    if (!$metaFieldCriterion->getMetaField()->getOptions()->contains($value)) {
                        throw new ValidationFailedException();
                    }
                    break;
            }
        }

        if (is_numeric($updatedBy)) {
            $userId = $updatedBy;
            $updatedBy = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }
        $this->getHistoryEventService()->addEvent(
            SmartFolderHistoryEvent::TYPE_UPDATE_CRITERION,
            $metaFieldCriterion->getSmartFolder(),
            $updatedBy,
            $metaFieldCriterion->getConditionType().
            (isset($data['condition_type'])?' -> '.$data['condition_type']:'')
            .':'.$metaFieldCriterion->getValue().' -> '.$value
        );
        try {
            $hydrator = new ClassMethodsHydrator();
            $hydrator->hydrate($data, $metaFieldCriterion);

            $this->getEntityManager()->merge($metaFieldCriterion);
            $this->getEntityManager()->flush();
        } catch (EntityNotFoundException $e) {
            throw new NotFoundException();
        }

        return $metaFieldCriterion;
    }

    public function deleteSmartFolderCriterion($metaFieldCriterion, $deletedBy = null)
    {
        if (is_numeric($metaFieldCriterion)) {
            $metaFieldCriterionId = $metaFieldCriterion;
            /** @var MetaFieldCriterion $metaFieldCriterion */
            $metaFieldCriterion = $this->getEntityManager()->getReference(
                MetaFieldCriterion::ENTITY_NAME,
                $metaFieldCriterionId
            );
        }

        if (is_numeric($deletedBy)) {
            $userId = $deletedBy;
            /** @var User $deletedBy */
            $deletedBy = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }
        $this->getHistoryEventService()->addEvent(
            SmartFolderHistoryEvent::TYPE_DELETE_CRITERION,
            $metaFieldCriterion->getSmartFolder(),
            $deletedBy,
            $metaFieldCriterion->getConditionType() .':'. $metaFieldCriterion->getValue()
        );
        try {
            $this->getEntityManager()->remove($metaFieldCriterion);
            $this->getEntityManager()->flush();
        } catch (EntityNotFoundException $e) {
            throw new NotFoundException();
        }
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
}
