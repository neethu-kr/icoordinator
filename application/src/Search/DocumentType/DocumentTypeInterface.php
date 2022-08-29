<?php

namespace iCoordinator\Search\DocumentType;

use iCoordinator\Entity\AbstractEntity;
use iCoordinator\Search\SearchFilter;

interface DocumentTypeInterface
{
    public function getType();
    public function getSupportedEntityNames();
    public function setFieldsConfig(array $config);

    public function extract(AbstractEntity $entity);
    public function hydrate(array $data, AbstractEntity $entity);
    public function createEntity(array $data);

    /**
     * @param $query
     * @param array $searchFilters
     * @return \Elastica\Query\AbstractQuery
     */
    public function getElasticaDSLQuery($query, array $searchFilters = array());
}
