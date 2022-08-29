<?php

namespace iCoordinator\Search\DocumentType;

use Elastica\QueryBuilder;
use iCoordinator\Entity\AbstractEntity;
use iCoordinator\Entity\Folder;
use iCoordinator\Entity\SmartFolder;
use iCoordinator\Entity\User;
use iCoordinator\Search\DocumentFieldType;
use iCoordinator\Search\SearchFilter;
use Laminas\Hydrator\ClassMethodsHydrator;

class File implements DocumentTypeInterface
{
    const FILTER_SIZE_RANGE = 'size_range';
    const FILTER_OWNER_USER_IDS = 'owner_user_ids';
    /**
     * @var File
     */
    private $file;

    /**
     * @var string
     */
    private $type = 'file';

    /**
     * @var array|null
     */
    private $searchFilters = null;

    /**
     * @var null|array
     */
    private $fieldsConfig = null;

    public function __construct()
    {
        $this->setFieldsConfig(array(
            'id'    => array('type' => DocumentFieldType::INTEGER, 'include_in_all' => false),
            'type'  => array('type' => DocumentFieldType::STRING, 'include_in_all' => false, 'not_analyzed' => true),
            'name'  => array('type' => DocumentFieldType::STRING, 'include_in_all' => true),
            'size'  => array('type' => DocumentFieldType::INTEGER, 'include_in_all' => false),
            'modified_at' => array('type' => DocumentFieldType::DATE, 'include_in_all' => false),
            'created_at' => array('type' => DocumentFieldType::DATE, 'include_in_all' => false),
            'owned_by' => array(
                'type' => 'nested',
                'properties' => array(
                    'id'        => array('type' => 'integer', 'include_in_all' => false),
                    'name'  => array('type' => 'string', 'include_in_all' => true)
                ),
            )
        ));
    }

    public function getType()
    {
        return $this->type;
    }

    public function getSupportedEntityNames()
    {
        return array(
            \iCoordinator\Entity\File::ENTITY_NAME,
            Folder::ENTITY_NAME,
            SmartFolder::ENTITY_NAME
        );
    }

    public function getFieldsConfig()
    {
        return $this->fieldsConfig;
    }

    /**
     * @param array $config
     */
    public function setFieldsConfig(array $config)
    {
        $this->fieldsConfig = $config;
    }

    /**
     * @param $query
     * @param array $searchFilters
     * @return \Elastica\Query\AbstractQuery
     * @throws \Exception
     */
    public function getElasticaDSLQuery($query, array $searchFilters = array())
    {
        $qb = new QueryBuilder();

        if (empty($query)) {
            $query = $qb->query()->match_all();
        } else {
            $query = $qb->query()->match()->setField('_all', $query);
        }

        foreach ($searchFilters as $filterName => $filterValue) {
            switch ($filterName) {
                case self::FILTER_SIZE_RANGE:
                    break;
                case self::FILTER_OWNER_USER_IDS:
                    break;
            }
        }

        $filters = array();

        foreach ($searchFilters as $filterName => $searchFilter) {
            $filter = null;
            switch ($filterName) {
                case self::FILTER_SIZE_RANGE:
                    if (!$searchFilter instanceof SearchFilter\NumericRange) {
                        throw new \Exception(
                            '"' . $filterName . '" filter should be instance of SearchFilter\\NumericRange'
                        );
                    }
                    $filter = $qb->filter()->range('size', array(
                        'gte' => $searchFilter->getFrom(),
                        'lte' => $searchFilter->getTo()
                    ));
                    break;
                case self::FILTER_OWNER_USER_IDS:
                    if (!$searchFilter instanceof SearchFilter\Terms) {
                        throw new \Exception(
                            '"' . $filterName . '" filter should be instance of SearchFilter\\NumericRange'
                        );
                    }
                    $filter = $qb->filter()->nested()->setPath('owned_by')->setFilter(
                        $qb->filter()->terms('owned_by.id', $searchFilter->getTerms())
                    );
                    break;
            }

            if ($filter) {
                array_push($filters, $filter);
            }
        }

        if (count($filters) > 0) {
            if (count($filters) > 1) {
                $filter = $qb->filter()->bool_and($filters);
            } else {
                $filter = array_pop($filters);
            }
            return $qb->query()->filtered($query, $filter);
        }

        return $query;
    }

    /**
     * @param AbstractEntity $entity
     * @return array
     * @throws \Exception
     */
    public function extract(AbstractEntity $entity)
    {
        if (!$entity instanceof \iCoordinator\Entity\File) {
            throw new \Exception('$entity should be instance of \iCoordinator\Entity\File');
        }

        if ($entity->getOwnedBy()) {
            $ownedBy = array(
                'id' => $entity->getOwnedBy()->getId(),
                'name' => $entity->getOwnedBy()->getName()
            );
        } else {
            $ownedBy = null;
        }

        return array(
            'id' => $entity->getId(),
            'type' => $entity->getType(),
            'name' => $entity->getName(),
            'size' => $entity->getSize(),
            'created_at' => ($entity->getCreatedAt()) ?
                $entity->getCreatedAt()->format(DateTime::ISO8601) : null,
            'modified_at' => ($entity->getModifiedAt()) ?
                $entity->getModifiedAt()->format(DateTime::ISO8601) : null,
            'owned_by' => $ownedBy
        );
    }

    /**
     * @param array $data
     * @return AbstractEntity
     * @throws \Exception
     */
    public function createEntity(array $data)
    {
        if (!isset($data['type']) || !isset($data['id'])) {
            throw new \Exception('$data["type"] or $data["id"] is not provided');
        }
        switch ($data['type']) {
            case Folder::getType():
                $entity = new Folder();
                break;
            case SmartFolder::getType():
                $entity = new SmartFolder();
                break;
            default:
                $entity = new \iCoordinator\Entity\File();
                break;
        }

        return $this->hydrate($data, $entity);
    }

    /**
     * @param array $data
     * @param AbstractEntity $entity
     * @return AbstractEntity
     * @throws \Exception
     */
    public function hydrate(array $data, AbstractEntity $entity)
    {
        if (!$entity instanceof \iCoordinator\Entity\File) {
            throw new \Exception('$entity should be instance of \iCoordinator\Entity\File');
        }

        $hydrator = new ClassMethodsHydrator();

        if (isset($data['owned_by'])) {
            $ownedBy = new User();
            $hydrator->hydrate($data['owned_by'], $ownedBy);
            unset($data['owned_by']);
        }

        $hydrator->hydrate($data, $entity);

        return $entity;
    }
}
