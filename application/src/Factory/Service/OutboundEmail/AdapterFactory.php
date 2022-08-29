<?php

namespace iCoordinator\Factory\Service\OutboundEmail;

use iCoordinator\Service\OutboundEmail\Adapter\AdapterInterface;
use iCoordinator\Service\OutboundEmail\Adapter\MandrillAdapter;
use Psr\Container\ContainerInterface;

class AdapterFactory
{
    const ADAPTER_TYPE_MANDRILL = 'mandrill';

    /**
     * @param ContainerInterface $c
     * @return AdapterInterface
     * @throws \Exception
     */
    public static function createAdapter(ContainerInterface $c)
    {
        $settings = $c->get('settings');

        if (!isset($settings['email'])) {
            throw new \RuntimeException('Email config is not defined');
        }

        $config = $settings['email'];
        $config['templates_path'] = $settings['applicationPath'] . $config['templates_path'];
        $config['locale_path'] = $settings['applicationPath'] . $config['locale_path'];
        if (!isset($config['adapter'])) {
            throw new \RuntimeException('Email adapter is not set');
        }

        switch ($config['adapter']) {
            case self::ADAPTER_TYPE_MANDRILL:
                return new MandrillAdapter($config);
                break;
            default:
                throw new \RuntimeException('Email adapter "' . $config['adapter'] . '" not found');
                break;
        }
    }
}
