<?php

namespace iCoordinator\Permissions\Resource;

use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Slim\Route;

class RouteResource implements ResourceInterface
{

    /**
     * @var \Slim\Route | string
     */
    private $route = null;

    /**
     * @param $route
     * @throws \Exception
     */
    public function __construct($route)
    {
        if (!is_string($route) && ($route instanceof \Slim\Route) == false) {
            throw new \Exception("\$route should be either string or instance of \\Slim\\Route");
        }
        $this->route = $route;
    }

    public function getResourceId()
    {
        if (is_string($this->route)) {
            $routeName = $this->route;
        } elseif ($this->route instanceof \Slim\Route) {
            $routeName = $this->route->getName();
        }
        return 'route__' . $routeName;
    }
}
