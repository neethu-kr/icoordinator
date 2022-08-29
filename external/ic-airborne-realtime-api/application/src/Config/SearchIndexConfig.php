<?php

namespace iCoordinator\Config;

use iCoordinator\Search\DocumentType\File;
use iCoordinator\Search\SearchIndex;
use Psr\Container\ContainerInterface;
use Slim\App;

class SearchIndexConfig extends AbstractConfig
{
    public function configure(App $app)
    {
        $c = $app->getContainer();

        $c['searchIndex'] = function (ContainerInterface $c) {
            $config = $c->get('settings');

            if (!isset($config['elasticsearch'])) {
                throw new \RuntimeException('ElasticSearch config is not defined');
            }

            $searchIndex = new SearchIndex($config['elasticsearch']);
            $searchIndex->setContainer($c);
            $searchIndex->setDocumentTypes(array(
                new File()
            ));
            return $searchIndex;
        };
    }
}
