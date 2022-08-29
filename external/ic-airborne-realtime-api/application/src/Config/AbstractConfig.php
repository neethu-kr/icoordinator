<?php

namespace iCoordinator\Config;

use Slim\App;

abstract class AbstractConfig
{
    /**
     * @var array[AbstractConfig]
     */
    private $configs = array();

    public function configure(App $app)
    {
        foreach ($this->configs as $config) {
            $config->configure($app);
        }
    }

    protected function add(AbstractConfig $config)
    {
        $this->configs[] = $config;
        return $this;
    }
}
