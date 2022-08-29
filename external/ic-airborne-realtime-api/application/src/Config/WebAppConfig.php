<?php

namespace iCoordinator\Config;

class WebAppConfig extends AbstractConfig
{
    public function __construct()
    {
        //NOTE: Order of configurations is important!

        $this->add(new DateTimeConfig())
            ->add(new LoggerConfig())
            ->add(new DiConfig())
            ->add(new ErrorHandlerConfig())
            ->add(new ServicesConfig())
            ->add(new ControllersConfig())
            ->add(new RoutesConfig())
            ->add(new DbConfig())
            ->add(new RedisConfig())
            ->add(new AccessConfig())
            ->add(new SearchIndexConfig())
            ->add(new CORSConfig());
    }
}
