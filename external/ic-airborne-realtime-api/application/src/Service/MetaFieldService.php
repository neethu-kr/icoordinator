<?php

namespace iCoordinator\Service;

use Carbon\Carbon;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use iCoordinator\Entity\File;
use iCoordinator\Entity\HistoryEvent\MetaFieldHistoryEvent;
use iCoordinator\Entity\HistoryEvent\MetaFieldValueHistoryEvent;
use iCoordinator\Entity\MetaField;
use iCoordinator\Entity\MetaFieldValue;
use iCoordinator\Entity\Portal;
use iCoordinator\Entity\User;
use iCoordinator\Service\Exception\ConflictException;
use iCoordinator\Service\Exception\NotFoundException;
use iCoordinator\Service\Exception\ValidationFailedException;
use Laminas\Hydrator\ClassMethodsHydrator;

/**
 * Class MetaFieldService
 * @package iCoordinator\Service
 */
class MetaFieldService extends AbstractService
{
    const META_FIELDS_LIMIT_DEFAULT = 100;

    /**
     * @var EventService
     */
    private $eventService;

    /**
     * @var HistoryEventService
     */
    private $historyEventService;

    /**
     * @param $metaFieldId
     * @return null|MetaField
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function getMetaField($metaFieldId)
    {
        $metaField = $this->getEntityManager()->find(MetaField::ENTITY_NAME, $metaFieldId);
        return $metaField;
    }

    /**
     * @param Portal|int $portal
     * @param int $limit
     * @param int $offset
     * @return Paginator
     * @throws \Doctrine\ORM\ORMException
     */
    public function getMetaFields($portal, $limit = self::META_FIELDS_LIMIT_DEFAULT, $offset = 0)
    {
        if (is_numeric($portal)) {
            $portalId = $portal;
            /** @var Portal $portal */
            $portal = $this->getEntityManager()->getReference(Portal::getEntityName(), $portalId);
        }

        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select(array('m'))
            ->from(MetaField::ENTITY_NAME, 'm')
            ->where('m.portal = :portal')
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
     * @param $portal
     * @param array $data
     * @param $createdBy
     * @return MetaField
     * @throws ConflictException
     * @throws ValidationFailedException
     * @throws \Doctrine\ORM\ORMException
     */
    public function createMetaField($portal, array $data, $createdBy)
    {
        if (is_numeric($portal)) {
            $portalId = $portal;
            /** @var Portal $portal */
            $portal = $this->getEntityManager()->getReference(Portal::getEntityName(), $portalId);
        }

        if (is_numeric($createdBy)) {
            $userId = $createdBy;
            /** @var User $createdBy */
            $createdBy = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        if (empty($data['name']) || empty($data['type'])) {
            throw new ValidationFailedException();
        }

        if ($this->checkMetaFieldNameExists($data['name'], $portal)) {
            throw new ConflictException('Meta field with this name already exists');
        }

        $metaField = new MetaField();
        $metaField->setPortal($portal);

        $hydrator = new ClassMethodsHydrator();
        $hydrator->hydrate($data, $metaField);

        try {
            $this->getEntityManager()->persist($metaField);
            $this->getEntityManager()->flush();
        } catch (NonUniqueResultException $e) {
            throw new ValidationFailedException();
        }
        $this->getHistoryEventService()->addEvent(
            MetaFieldHistoryEvent::TYPE_CREATE,
            $metaField,
            $createdBy,
            $metaField->getName()
        );
        return $metaField;
    }

    private function checkMetaFieldNameExists($metaFieldName, Portal $portal)
    {
        $repository = $this->getEntityManager()->getRepository(MetaField::ENTITY_NAME);
        $metaField = $repository->findOneBy(array(
            'name' => $metaFieldName,
            'portal' => $portal
        ));

        if ($metaField) {
            return true;
        }

        return false;
    }

    /**
     * @param $metaField
     * @param $data
     * @param $updatedBy
     * @return MetaField
     * @throws ConflictException
     * @throws NotFoundException
     * @throws ValidationFailedException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function updateMetaField($metaField, $data, $updatedBy)
    {
        if (is_numeric($metaField)) {
            $metaFieldId = $metaField;
            /** @var MetaField $metaField */
            $metaField = $this->getEntityManager()->find(MetaField::ENTITY_NAME, $metaFieldId);
        }
        $description = $metaField->getName() . ' -> ' . $data['name'];
        //validation
        if (!empty($data['type'])) {
            if ($data['type'] == $metaField->getType()) {
                unset($data['type']);
            } else {
                if ($data['type'] == MetaField::TYPE_LIST && empty($data['options'])) {
                    throw new ValidationFailedException();
                } else {
                    $data['options'] = null;
                }
            }
        }
        if (!empty($data['name'])) {
            if ($data['name'] != $metaField->getName()) {
                if ($this->checkMetaFieldNameExists($data['name'], $metaField->getPortal())) {
                    throw new ConflictException('Meta field with this name already exists');
                }
            } else {
                unset($data['name']);
            }
        }

        if (is_numeric($updatedBy)) {
            $userId = $updatedBy;
            $updatedBy = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        $hydrator = new ClassMethodsHydrator();

        try {
            $hydrator->hydrate($data, $metaField);
            $this->getEntityManager()->merge($metaField);
            $this->getEntityManager()->flush();
        } catch (EntityNotFoundException $e) {
            throw new NotFoundException();
        }
        $this->getHistoryEventService()->addEvent(
            MetaFieldHistoryEvent::TYPE_CHANGE_NAME,
            $metaField,
            $updatedBy,
            $description
        );
        return $metaField;
    }

    /**
     * @param $metaField
     * @param $deletedBy
     * @throws NotFoundException
     * @throws \Doctrine\ORM\ORMException
     */
    public function deleteMetaField($metaField, $deletedBy)
    {
        if (is_numeric($metaField)) {
            $metaFieldId = $metaField;
            /** @var MetaField $metaField */
            $metaField = $this->getEntityManager()->getReference(MetaField::ENTITY_NAME, $metaFieldId);
        }

        if (is_numeric($deletedBy)) {
            $userId = $deletedBy;
            /** @var User $deletedBy */
            $deletedBy = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }
        $this->getHistoryEventService()->addEvent(
            MetaFieldHistoryEvent::TYPE_DELETE,
            $metaField,
            $deletedBy,
            $metaField->getName()
        );
        try {
            $this->getEntityManager()->remove($metaField);
            $this->getEntityManager()->flush();
        } catch (EntityNotFoundException $e) {
            throw new NotFoundException();
        }
    }

    public function getMetaFieldValue($metaFieldValueId)
    {
        $metaFieldValue = $this->getEntityManager()->find(MetaFieldValue::ENTITY_NAME, $metaFieldValueId);
        return $metaFieldValue;
    }

    public function getMetaFieldsValues($file)
    {
        if (is_numeric($file)) {
            $fileId = $file;
            /** @var File $file */
            $file = $this->getEntityManager()->getReference(File::ENTITY_NAME, $fileId);
        }

        return $file->getMetaFieldsValues();
    }

    /**
     * @param $file
     * @param $data
     * @param $addedBy
     * @return MetaFieldValue
     * @throws \Doctrine\ORM\ORMException
     */
    public function addMetaFieldValue($file, $data, $addedBy)
    {
        if (is_numeric($file)) {
            $fileId = $file;
            /** @var File $file */
            $file = $this->getEntityManager()->getReference(File::ENTITY_NAME, $fileId);
        }

        if (isset($data['meta_field']) && isset($data['meta_field']['id'])) {
            $metaFieldId = $data['meta_field']['id'];
            /** @var MetaField $metaField */
            $metaField = $this->getEntityManager()->getReference(MetaField::ENTITY_NAME, $metaFieldId);
            unset($data['meta_field']);
            //validation
            switch ($metaField->getType()) {
                case MetaField::TYPE_NUMBER:
                    if (!is_numeric($data['value'])) {
                        throw new ValidationFailedException();
                    }
                    break;
                case MetaField::TYPE_DATE:
                    if (!Carbon::createFromFormat(Carbon::ISO8601, $data['value'])) {
                        throw new ValidationFailedException();
                    }
                    break;
                case MetaField::TYPE_LIST:
                    if (!$metaField->getOptions()->contains($data['value'])) {
                        throw new ValidationFailedException();
                    }
                    break;
            }
        }



        if (is_numeric($addedBy)) {
            $userId = $addedBy;
            /** @var User $addedBy */
            $addedBy = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        $metaFieldValue = new MetaFieldValue();
        $metaFieldValue->setResource($file)
                       ->setMetaField($metaField)
                       ->setValue($data['value']);
        $file->getMetaFieldsValues()->add($metaFieldValue);

        $this->getEntityManager()->persist($metaFieldValue);
        $this->getEntityManager()->flush();

        $this->getHistoryEventService()->addEvent(
            MetaFieldValueHistoryEvent::TYPE_VALUE_ASSIGN,
            $metaFieldValue,
            $addedBy,
            $metaField->getId().':'.$data['value'].':'.$file->getId().':'.$metaField->getName()
        );

        return $metaFieldValue;
    }

    /**
     * @param $metaFieldValue
     * @param $data
     * @param $updatedBy
     * @return MetaFieldValue
     * @throws NotFoundException
     * @throws ValidationFailedException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function updateMetaFieldValue($metaFieldValue, $data, $updatedBy)
    {
        if (is_numeric($metaFieldValue)) {
            $metaFieldValueId = $metaFieldValue;
            /** @var MetaFieldValue $metaFieldValue */
            $metaFieldValue = $this->getEntityManager()->find(MetaFieldValue::ENTITY_NAME, $metaFieldValueId);
        }

        if (!isset($data['value'])) {
            throw new ValidationFailedException();
        }

        $value = $data['value'];

        //validation
        switch ($metaFieldValue->getMetaField()->getType()) {
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
                if (!$metaFieldValue->getMetaField()->getOptions()->contains($value)) {
                    throw new ValidationFailedException();
                }
                break;
        }

        if (is_numeric($updatedBy)) {
            $userId = $updatedBy;
            $updatedBy = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }
        $changeHistory = $metaFieldValue->getValue() . " -> " . $value.':'.
            $metaFieldValue->getMetaField()->getId().':'.
            $metaFieldValue->getResource()->getId().':'.
            $metaFieldValue->getMetaField()->getName();
        try {
            $metaFieldValue->setValue($value);
            $this->getEntityManager()->merge($metaFieldValue);
            $this->getEntityManager()->flush();
        } catch (EntityNotFoundException $e) {
            throw new NotFoundException();
        }

        $this->getHistoryEventService()->addEvent(
            MetaFieldValueHistoryEvent::TYPE_VALUE_CHANGE,
            $metaFieldValue,
            $updatedBy,
            $changeHistory
        );

        return $metaFieldValue;
    }

    /**
     * @param $metaFieldValue
     * @param $deletedBy
     * @throws NotFoundException
     * @throws \Doctrine\ORM\ORMException
     */
    public function deleteMetaFieldValue($metaFieldValue, $deletedBy)
    {
        if (is_numeric($metaFieldValue)) {
            $metaFieldValueId = $metaFieldValue;
            /** @var MetaFieldValue $metaFieldValue */
            $metaFieldValue = $this->getEntityManager()->getReference(MetaFieldValue::ENTITY_NAME, $metaFieldValueId);
        }

        if (is_numeric($deletedBy)) {
            $userId = $deletedBy;
            /** @var User $deletedBy */
            $deletedBy = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }
        $mixed = $metaFieldValue->getMetaField()->getId()
            . ":" . $metaFieldValue->getResource()->getId()
            . ":" . $metaFieldValue->getValue()
            . ":" . $metaFieldValue->getMetaField()->getName();
        $this->getHistoryEventService()->addEvent(
            MetaFieldValueHistoryEvent::TYPE_VALUE_REMOVE,
            $metaFieldValue,
            $deletedBy,
            $mixed
        );
        try {
            $this->getEntityManager()->remove($metaFieldValue);
            $this->getEntityManager()->flush();
        } catch (EntityNotFoundException $e) {
            throw new NotFoundException();
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
}
