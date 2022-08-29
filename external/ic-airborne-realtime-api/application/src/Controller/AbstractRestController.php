<?php

namespace iCoordinator\Controller;

use iCoordinator\ContainerAwareTrait;
use iCoordinator\Controller\Helper\AbstractControllerHelper;
use iCoordinator\Factory\EntityManagerFactory;
use iCoordinator\Permissions\Acl;
use Psr\Container\ContainerInterface;
use Laminas\Authentication\AuthenticationService;

#use Interop\Container\ContainerInterface;

abstract class AbstractRestController
{
    use ContainerAwareTrait;

    const STATUS_OK = 200;
    const STATUS_CREATED = 201;
    const STATUS_ACCEPTED = 202;
    const STATUS_NO_CONTENT = 204;

    const STATUS_MULTIPLE_CHOICES = 300;
    const STATUS_MOVED_PERMANENTLY = 301;
    const STATUS_FOUND = 302;
    const STATUS_NOT_MODIFIED = 304;
    const STATUS_USE_PROXY = 305;
    const STATUS_TEMPORARY_REDIRECT = 307;

    const STATUS_BAD_REQUEST = 400;
    const STATUS_UNAUTHORIZED = 401;
    const STATUS_FORBIDDEN = 403;
    const STATUS_NOT_FOUND = 404;
    const STATUS_METHOD_NOT_ALLOWED = 405;
    const STATUS_NOT_ACCEPTED = 406;
    const STATUS_CONFLICT = 409;
    const STATUS_PRECONDITION_FAILED = 412;
    const STATUS_LOCKED = 423;

    const STATUS_INTERNAL_SERVER_ERROR = 500;
    const STATUS_NOT_IMPLEMENTED = 501;

    const HEADER_SHARED_LINK_TOKEN = 'iCoordinator-Shared-Link-Token';

    const INVALID_CHARACTERS = '/[\\\<>:*?\/\"|]/';

    /**
     * @var array
     */
    protected $helpers = array();

    public function __construct(ContainerInterface $c)
    {
        $this->container = $c;
        $this->init();
    }

    public function init()
    {
        //init
    }

    /**
     * @return Acl
     */
    public function getAcl()
    {
        return $this->getContainer()->get('acl');
    }

    /**
     * @return AuthenticationService
     */
    public function getAuth()
    {
        return $this->getContainer()->get('auth');
    }

    public function addHelper(AbstractControllerHelper $helper)
    {
        if (!isset($this->helpers[$helper->getHelperId()])) {
            $helper->setContainer($this->getContainer());
            $this->helpers[$helper->getHelperId()] = $helper;
        }
    }

    public function getHelper($helperId)
    {
        if (isset($this->helpers[$helperId])) {
            return $this->helpers[$helperId];
        } else {
            throw new \Exception("Helper with id \"{$helperId}\" is not found");
        }
    }
}
