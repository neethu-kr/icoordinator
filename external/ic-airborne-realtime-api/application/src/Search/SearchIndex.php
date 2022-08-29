<?php

namespace iCoordinator\Search;

use Elastica\Client;
use Elastica\Document;
use Elastica\Query;
use Elastica\QueryBuilder;
use Elastica\Type\Mapping;
use iCoordinator\Entity\AbstractEntity;
use iCoordinator\EntityManagerAwareTrait;
use iCoordinator\Search\DocumentType\DocumentTypeInterface;

class SearchIndex
{
    use EntityManagerAwareTrait;

    /**
     * @var array
     */
    private $documentTypes = array();

    /**
     * @var array
     */
    private $documentTypesIndex = array();

    /**
     * @var array
     */
    private $documentTypesMapping = array();


    /**
     * @var Client|null
     */
    private $client = null;

    /**
     * @var \Elastica\Index|null
     */
    private $index = null;

    /**
     * @var array
     */
    private $config = array();

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->client = new Client(array(
            'host' => $config['host'],
            'port' => $config['port']
        ));
    }

    /**
     * @return bool
     */
    public function indexExists()
    {
        return $this->getIndex()->exists();
    }

    /**
     * @return \Elastica\Index|null
     */
    public function getIndex()
    {
        if ($this->index === null) {
            $this->index = $this->getClient()->getIndex($this->config['index']);
        }

        return $this->index;
    }

    /**
     * @return Client|null
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @throws \Exception
     */
    public function createIndex()
    {
        $index = $this->getIndex();
        if ($index->exists()) {
            throw new \Exception('Search index already exists on ElasticSearch server');
        }

        //TODO: add options
        $index->create();

        //Create a type
        $documentTypes = $this->getDocumentTypes();
        if (!empty($documentTypes) && is_array($documentTypes)) {
            /** @var DocumentTypeInterface $documentType */
            foreach ($documentTypes as $documentType) {
                $type = $index->getType($documentType->getType());

                // Define mapping
                $mapping = new Mapping();
                $mapping->setType($type);

                $mapping->enableAllField(true);

                // Set mapping
                $mapping->setProperties($documentType->getFieldsConfig());

                // Send mapping to type
                $mapping->send();
            }
        }
    }

    public function getDocumentTypes()
    {
        return $this->documentTypes;
    }

    /**
     * @param array $documentTypes
     */
    public function setDocumentTypes(array $documentTypes)
    {
        $this->documentTypes = $documentTypes;

        /** @var DocumentTypeInterface $documentType */
        foreach ($documentTypes as $documentType) {
            $this->documentTypesIndex[$documentType->getType()] = $documentType;
            foreach ($documentType->getSupportedEntityNames() as $entityName) {
                $this->documentTypesMapping[$entityName] = $documentType;
            }
        }
    }

    /**
     * @return \Elastica\Response
     * @throws \Exception
     */
    public function deleteIndex()
    {
        $index = $this->getIndex();
        if (!$index->exists()) {
            throw new \Exception('Search index already doesn\'t on ElasticSearch server');
        }

        return $index->delete();
    }

    public function addEntity(AbstractEntity $entity)
    {
        if (!isset($this->documentTypesMapping[$entity->getEntityName()])) {
            throw new \Exception('Document type for entity "' . $entity->getEntityName() . '" not specified');
        }

        /** @var DocumentTypeInterface $documentType */
        $documentType = $this->documentTypesMapping[$entity->getEntityName()];
        $data = $documentType->extract($entity);

        $type = $this->getIndex()->getType($documentType->getType());
        $document = new Document($entity->getId(), $data);
        return $type->addDocument($document);
    }

    public function getEntity($entityName, $id)
    {
        if (!isset($this->documentTypesMapping[$entityName])) {
            throw new \Exception('Document type for entity "' . $entityName . '" not specified');
        }

        /** @var DocumentTypeInterface $documentType */
        $documentType = $this->documentTypesMapping[$entityName];

        $type = $this->getIndex()->getType($documentType->getType());
        $document = $type->getDocument($id);

        return $documentType->createEntity($document->getData());
    }

    public function search($query)
    {
        $elasticaQuery = new Query();
        $qb = new QueryBuilder();

        $elasticaQuery->setQuery(
            $qb->query()->match()->setField('_all', $query)
        );

        $response = $this->getIndex()->search($elasticaQuery)->getResponse()->getData();

        $result = array();
        foreach ($response['hits']['hits'] as $hit) {
            $searchResult = new SearchResult($hit['_type'], $hit['_source']);
            $documentType = $this->documentTypesIndex[$searchResult->getType()];
            $entity = $documentType->createEntity($searchResult->getData());
            array_push($result, $entity);
        }

        return $result;
    }

    public function searchByEntityName($entityName, $query, array $searchFilters = array())
    {
        if (!isset($this->documentTypesMapping[$entityName])) {
            throw new \Exception('Document type for entity "' . $entityName . '" not specified');
        }

        /** @var DocumentTypeInterface $documentType */
        $documentType = $this->documentTypesMapping[$entityName];

        $dslQuery = $documentType->getElasticaDSLQuery($query, $searchFilters);
//        var_dump($dslQuery->toArray()); exit;

        $elasticaQuery = new Query();
        $elasticaQuery->setQuery($dslQuery);

        $type = $this->getIndex()->getType($documentType->getType());
        $response = $type->search($elasticaQuery)->getResponse()->getData();

        $result = array();
        foreach ($response['hits']['hits'] as $hit) {
            array_push($result, new SearchResult($hit['_type'], $hit['_source']));
        }

        var_dump($result);
        exit;

        return $result;
    }
}
