<?php

namespace iCoordinator\Config;

use iCoordinator\ContainerAwareTrait;
use iCoordinator\Factory\Service\ServiceFactoryInterface;
use phpseclib\Crypt\AES;
use Psr\Container\ContainerInterface;
use Slim\App;
use Laminas\Authentication\Adapter\Http\Exception\RuntimeException;

class ServicesConfig extends AbstractConfig
{
    use ContainerAwareTrait;

    private $serviceFactoryNamespace = 'iCoordinator\\Factory\\Service\\';

    public function configure(App $app)
    {
        $c = $this->container = $app->getContainer();

        $c['TokenEncryptor'] =  function ($c) {
            $settings = $c->get('settings');
            $encryptor = new AES();
            $encryptor->setKey($settings['token_aes_key']);
            return $encryptor;
        };

        $this->addService('DownloadTokenService', 'DownloadTokenServiceFactory')
            ->addService('DownloadZipTokenService', 'DownloadZipTokenServiceFactory')
            ->addService('EventService', 'EventServiceFactory')
            ->addService('HistoryEventService', 'HistoryEventServiceFactory')
            ->addService('EventNotificationService', 'EventNotificationServiceFactory')
            ->addService('FileEmailOptionsService', 'FileEmailOptionsServiceFactory')
            ->addService('FileSearchService', 'FileSearchServiceFactory')
            ->addService('FileService', 'FileServiceFactory')
            ->addService('FolderService', 'FolderServiceFactory')
            ->addService('GroupService', 'GroupServiceFactory')
            ->addService('InboundEmailService', 'InboundEmailServiceFactory')
            ->addService('LockService', 'LockServiceFactory')
            ->addService('MetaFieldService', 'MetaFieldServiceFactory')
            ->addService('OutboundEmailService', 'OutboundEmailServiceFactory')
            ->addService('PermissionService', 'PermissionServiceFactory')
            ->addService('PortalService', 'PortalServiceFactory')
            ->addService('SelectiveSyncService', 'SelectiveSyncServiceFactory')
            ->addService('SharedLinkService', 'SharedLinkServiceFactory')
            ->addService('SmartFolderService', 'SmartFolderServiceFactory')
            ->addService('UserService', 'UserServiceFactory')
            ->addService('SignUpService', 'SignUpServiceFactory')
            ->addService('WorkspaceService', 'WorkspaceServiceFactory')
            ->addService('EmailConfirmationService', 'EmailConfirmationServiceFactory')
            ->addService('SubscriptionService', 'SubscriptionServiceFactory')
            ->addService('StateService', 'StateServiceFactory')
            ->addService('ChargifyWebhookService', 'ChargifyWebhookServiceFactory')
            ->addService('CustomerSpecific\\Norway\\FDVService', 'CustomerSpecific\\Norway\\FDVServiceFactory');
    }

    /**
     * @param $serviceName
     * @param $serviceFactoryName
     * @return $this
     */
    private function addService($serviceName, $serviceFactoryName)
    {
        /** @var ContainerInterface $c */
        $c = $this->getContainer();
        $c[$serviceName] = function ($c) use ($serviceFactoryName) {
            $factoryClass = $this->serviceFactoryNamespace . $serviceFactoryName;
            if (class_exists($factoryClass)) {
                $factory = new $factoryClass($c);
                if ($factory instanceof ServiceFactoryInterface) {
                    return $factory->createService($c);
                } else {
                    throw new RuntimeException(
                        'Service factory class should be instance of
                        \\iCoordinator\\Factory\\Service\\ServiceFactoryInterface'
                    );
                }
            } else {
                throw new RuntimeException('Service factory class "' . $factoryClass . '" not found');
            }
        };

        return $this;
    }
}
