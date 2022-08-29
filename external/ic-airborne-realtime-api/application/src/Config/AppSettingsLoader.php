<?php

namespace iCoordinator\Config;

class AppSettingsLoader
{
    const APPLICATION_ENV_DEVELOPMENT = 'development';
    const APPLICATION_ENV_TEST = 'test';
    const APPLICATION_ENV_PRODUCTION = 'production';

    /**
     * @var string
     */
    private $localEnvFile = 'env.php';

    /**
     * @var string
     */
    private $applicationPath;

    /**
     * @var string
     */
    private $applicationEnv = self::APPLICATION_ENV_DEVELOPMENT;

    /**
     * @var string
     */
    private $configPath;

    /**
     * @param string $applicationPath
     */
    public function __construct($applicationPath)
    {
        $this->applicationPath = $applicationPath;

        $applicationEnv = getenv('APPLICATION_ENV');
        if ($applicationEnv) {
            $this->applicationEnv = $applicationEnv;
        } else {
            putenv('APPLICATION_ENV=' . $this->applicationEnv);
        }

        $this->configPath = $this->applicationPath . DIRECTORY_SEPARATOR . 'config';

        if ($this->applicationEnv == self::APPLICATION_ENV_DEVELOPMENT) {
            $this->setLocalEnvironmentVariables();
        }
    }

    /**
     * Load local environment variables from file
     */
    private function setLocalEnvironmentVariables()
    {
        $localEnvFile = $this->configPath . DIRECTORY_SEPARATOR . $this->localEnvFile;

        if (file_exists($localEnvFile)) {
            $envVariables = include $localEnvFile;
            foreach ($envVariables as $key => $value) {
                if (!getenv($key)) {
                    putenv($key . '=' . $value);
                }
            }
        }
    }

    /**
     * @param array $filesPaths
     * @return array
     */
    public function loadFiles(array $filesPaths)
    {
        $settings = array();
        foreach ($filesPaths as $filePath) {
            $settings = array_merge($settings, $this->loadFile($filePath));
        }
        return $settings;
    }

    /**
     * @param $fileName
     * @param bool|true $throwExceptionIfNotExists
     * @return mixed
     */
    public function loadFile($fileName, $throwExceptionIfNotExists = true)
    {
        $path = $this->configPath . DIRECTORY_SEPARATOR . $fileName;

        if (file_exists($path)) {
            return include $path;
        } elseif ($throwExceptionIfNotExists) {
            throw new \RuntimeException(sprintf('Settings file not found at path %s', $path));
        }

        return array();
    }

    /**
     * @return string
     */
    public function getApplicationEnv()
    {
        return $this->applicationEnv;
    }
}
