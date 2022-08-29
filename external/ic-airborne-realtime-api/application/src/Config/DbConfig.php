<?php

namespace iCoordinator\Config;

use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use PDO;
use Psr\Container\ContainerInterface;
use Rhumsaa\Uuid\Doctrine\UuidType;
use Slim\App;

class DbConfig extends AbstractConfig
{
    /**
     * Reuse the same connection if possible
     *
     * @var Connection
     */
    private static $connection = null;

    /**
     * Shared cache implementation for development and test modes
     *
     * @var Cache
     */
    private static $cache = null;

    /**
     * @param App $app
     */
    public function configure(App $app)
    {
        $c = $app->getContainer();

        $c['entityManager'] = function ($c) {
            return $this->createEntityManager($c);
        };

        $c['sqlLogger'] = function ($c) {
            return $c->get('entityManager')->getConfiguration()->getSQLLogger();
        };
    }

    /**
     * @param ContainerInterface $c
     * @return EntityManager
     * @throws \Doctrine\ORM\ORMException
     */
    private function createEntityManager($c)
    {
        $settings = $c['settings'];

        if (!isset($settings['db'])) {
            throw new \InvalidArgumentException('Database connection configuration is not defined');
        }

        if (isset($settings['db']['database_url'])) {
            $connectionOptions = array(
                'url' => $settings['db']['database_url'],
                'charset' => $settings['db']['charset'] ,
                'driverOptions' => array(
                    PDO::MYSQL_ATTR_SSL_CA    => $settings['applicationPath'] . '/config/ssl/rds-combined-ca-bundle.pem'
                )
            );
        } elseif (isset($settings['db']['driver'])) {
            $connectionOptions = $settings['db'];
        } else {
            throw new \InvalidArgumentException('Database connection configuration is not defined');
        }

        if (self::$connection !== null) {
            $connectionOptions['pdo'] = self::$connection;
        }

        $this->overrideDoctrineTypes();

        $config = $this->createEntityManagerConfiguration($settings['applicationPath'], $settings['mode']);

        // obtaining the entity manager
        $evm = new EventManager();
        $entityManager = EntityManager::create($connectionOptions, $config, $evm);

        if (self::$connection === null) {
            self::$connection = $entityManager->getConnection()->getWrappedConnection();
        }

        return $entityManager;
    }

    /**
     * @param $applicationPath
     * @param string $mode
     * @return Configuration
     */
    private function createEntityManagerConfiguration($applicationPath, $mode = 'development')
    {
        $isDevMode = ($mode == 'development');
        $isProductionMode = ($mode == 'production');

        $conf = array(
            'proxyDir' => $applicationPath . '/data/Doctrine/Proxy',
            'proxyNamespace' => 'iCoordinator\Doctrine\Proxy',
            'entityDir' => $applicationPath . '/src/Entity',
            'entityNamespace' => 'iCoordinator\Entity'
        );

        $config = new Configuration();

        // Proxy Configuration
        $config->setProxyDir($conf['proxyDir']);
        $config->setProxyNamespace($conf['proxyNamespace']);
        $config->setAutoGenerateProxyClasses($isDevMode);
        $config->addEntityNamespace('entity', $conf['entityNamespace']);

        // Mapping Configuration
        $annotationDriver = $config->newDefaultAnnotationDriver($conf['entityDir']);
        $config->setMetadataDriverImpl($annotationDriver);

        // Caching Configuration
        if ($isProductionMode) {
            $cache = new ApcuCache();
        } else {
            if (self::$cache === null) {
                self::$cache = new ArrayCache();
            }
            $cache = self::$cache;
        }

        //Logging Configuration
        if ($isDevMode) {
            $logger = new DebugStack();
            $logger->enabled = false;
            $config->setSQLLogger($logger);
        }

        $config->setMetadataCacheImpl($cache);
        $config->setQueryCacheImpl($cache);

        return $config;
    }

    private function overrideDoctrineTypes()
    {
        Type::overrideType(Type::DATE, '\DoctrineExtensions\Types\CarbonDateType');
        Type::overrideType(Type::TIME, '\DoctrineExtensions\Types\CarbonTimeType');
        Type::overrideType(Type::DATETIME, '\DoctrineExtensions\Types\CarbonDateTimeType');
        Type::overrideType(Type::DATETIMETZ, '\DoctrineExtensions\Types\CarbonDateTimeTzType');
        Type::addType(UuidType::NAME, '\Rhumsaa\Uuid\Doctrine\UuidType');
    }
}
