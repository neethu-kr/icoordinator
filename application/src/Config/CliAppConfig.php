<?php

namespace iCoordinator\Config;

class CliAppConfig extends AbstractConfig
{
    public function __construct()
    {
        $this->add(new DateTimeConfig())
            ->add(new DiConfig())
            ->add(new ServicesConfig())
            ->add(new ControllersConfig())
            ->add(new RoutesConfig())
            ->add(new DbConfig())
            ->add(new RedisConfig())
            ->add(new DbMigrationsConfig())
            ->add(new AccessConfig());
    }
}
