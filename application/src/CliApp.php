<?php

namespace iCoordinator;

use iCoordinator\Config\AppSettingsLoader;
use iCoordinator\Config\CliAppConfig;

class CliApp
{
    /**
     * @param array $settings
     * @return \Slim\App
     */
    public static function create($settings = [])
    {
        if (!isset($settings['applicationPath'])) {
            throw new \InvalidArgumentException('applicationPath setting is not defined');
        }

        $settingsLoader = new AppSettingsLoader($settings['applicationPath']);
        $settings = array_merge($settings, $settingsLoader->loadFiles(array(
            'global.php', 'db.php', 'filestorage.php', 'email.php', 'redis.php'
        )));

        //override global settings with local for development
        if ($settingsLoader->getApplicationEnv() == $settingsLoader::APPLICATION_ENV_DEVELOPMENT) {
            $settings = array_merge($settings, $settingsLoader->loadFile('local.php', false));
        }

        $app = new \Slim\App(['settings' => $settings]);

        $appConfig = new CliAppConfig();
        $appConfig->configure($app);

        return $app;
    }
}
