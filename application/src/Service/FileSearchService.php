<?php

namespace iCoordinator\Service;

use iCoordinator\Entity\Event;
use iCoordinator\Entity\File;
use iCoordinator\Search\DocumentType\File as FileDocument;
use iCoordinator\Search\SearchIndex;
use iCoordinator\Service\Helper\FilterParamsParser;

class FileSearchService extends AbstractService
{
    public function search($params)
    {
        if (isset($params['query']) && !empty($params['query'])) {
            $query = $params['query'];
        } else {
            $query = null;
        }

        $filters = array();

        foreach ($params as $paramName => $value) {
            $filter = null;
            switch ($paramName) {
                case FileDocument::FILTER_SIZE_RANGE:
                    $filter = FilterParamsParser::parse($value, FilterParamsParser::PARAM_TYPE_NUMERIC_RANGE);
                    break;
                case FileDocument::FILTER_OWNER_USER_IDS:
                    $filter = FilterParamsParser::parse($value, FilterParamsParser::PARAM_TYPE_NUMERIC_LIST);
                    break;
            }

            if ($filter !== null) {
                $filters[$paramName] = $filter;
            }
        }

        $this->getSearchIndex()->searchByEntityName(File::getEntityName(), $query, $filters);
    }


    /**
     * @return SearchIndex
     */
    public function getSearchIndex()
    {
        return $this->getApp()->searchIndex;
    }
}
