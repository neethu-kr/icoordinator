<?php

namespace iCoordinator\PHPUnit;

use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\ORM\EntityManager;
use iCoordinator\Config\Route\AuthRouteConfig;
use iCoordinator\WebApp;
use PhpCollection\Map;
use PhpOption\Some;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\App;
use Slim\Container;
use Slim\Http\Body;
use Slim\Http\Environment;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\UploadedFile;
use Slim\Http\Uri;
use Slim\Router;

abstract class TestCase extends \PHPUnit_Extensions_Database_TestCase
{
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_DELETE = 'DELETE';
    const METHOD_PATCH = 'PATCH';
    const METHOD_OPTIONS = 'OPTIONS';
    const METHOD_HEAD = 'HEAD';

    const REQUEST_CONTENT_TYPE_JSON = 'application/json';
    const REQUEST_CONTENT_TYPE_FORM_URLENCODED = 'application/x-www-form-urlencoded';
    /**
     * @var TestCaseRun
     */
    public static $currentTestCaseRun = null;
    /**
     * @var App
     */
    private static $app;
    /**
     * Share the same connection for all tests
     *
     * @var \PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection
     */
    private static $connection = null;
    /**
     * Share same headers between all tests
     *
     * @var array
     */
    private $authHeaders;

    /**
     * @param $requestUri
     * @param $params
     * @param $headers
     * @return ResponseInterface
     */
    public function get($requestUri, $params = array(), $headers = array())
    {
        return $this->request(self::METHOD_GET, $requestUri, $params, $headers);
    }

    /**
     * @param $method
     * @param $requestUri
     * @param array|string $bodyParams
     * @param array $optionalHeaders
     * @return ResponseInterface
     */
    public function request($method, $requestUri, $bodyParams = [], $optionalHeaders = [])
    {
        self::$currentTestCaseRun = new TestCaseRun($method, $requestUri, $bodyParams, $optionalHeaders);

        $app = $this->getApp();

        $response = $app->run(true);

        return $response;
    }

    /**
     * @return App
     */
    public function getApp()
    {
        if (self::$app === null) {
            self::$app = $this->createNewApp();
        }
        return self::$app;
    }

    /**
     * @return App
     */
    private function createNewApp()
    {
        self::$app = WebApp::create(array(
            'applicationPath' => APPLICATION_PATH,
            'testsPath' => TESTS_PATH
        ));

        /** @var Container $c */
        $c = self::$app->getContainer();

        $c['environment'] = $c->factory(function ($c) {
            $testCaseRun = TestCase::$currentTestCaseRun;

            $serverHeaders = array();
            foreach ($testCaseRun->getHeaders() as $key => $value) {
                $serverKey = strtr(strtoupper($key), '-', '_');
                if (strpos($serverKey, 'HTTP_') !== 0) {
                    $serverHeaders['HTTP_' . $serverKey] = $value;
                }
            }

            $urlParts = new Map(parse_url($testCaseRun->getRequestUri()));

            // Prepare request and response objects
            $env = Environment::mock(array_merge([
                'SCRIPT_NAME' => '/index.php',
                'REQUEST_URI' => $testCaseRun->getRequestUri(),
                'PATH_INFO' => $urlParts->get('path')->getOrElse('/'),
                'QUERY_STRING' => $urlParts->get('query')->getOrElse(''),
                'REQUEST_METHOD' => $testCaseRun->getMethod()
            ], $serverHeaders));

            $_SERVER = $env->all();

            return $env;
        });

        $c['request'] = $c->factory(function ($c) {
            $testCaseRun = TestCase::$currentTestCaseRun;

            $env = $c['environment'];
            $uri = Uri::createFromEnvironment($env);
            $headers = Headers::createFromEnvironment($env);
            $uplaodedFiles = UploadedFile::createFromEnvironment($env);
            $cookies = [];
            $serverParams = $env->all();

            $body = $testCaseRun->getBodyParams();

            if (is_array($body)) {
                $body = new Body(fopen('php://temp', 'r+'));
                $parsedBody = $body;
            } else {
                $stream = fopen('php://temp', 'w+');
                fwrite($stream, $body);
                rewind($stream);
                $body = new Body($stream);
                $parsedBody = [];
            }

            $req = new Request(
                $testCaseRun->getMethod(),
                $uri,
                $headers,
                $cookies,
                $serverParams,
                $body,
                $uplaodedFiles
            );

            if (!empty($parsedBody)) {
                $req = $req->withParsedBody($testCaseRun->getBodyParams());
            }

            $_GET = $req->getQueryParams();
            $_POST = Some::fromValue($req->getParsedBody())->getOrElse([]);
            $_REQUEST = array_merge($_POST, $_GET);

            return $req;
        });

        $c['response'] = $c->factory(function ($c) {
            $headers = new Headers(['Content-Type' => 'text/html']);
            $response = new Response(200, $headers);
            return $response->withProtocolVersion($c['settings']['httpVersion']);
        });

        return self::$app;
    }

    /**
     * @param $requestUri
     * @param $params
     * @param $headers
     * @return ResponseInterface
     */
    public function put($requestUri, $params = array(), $headers = array())
    {
        return $this->request(self::METHOD_PUT, $requestUri, $params, $headers);
    }

    /**
     * @param $requestUri
     * @param $params
     * @param $headers
     * @return ResponseInterface
     */
    public function patch($requestUri, $params = array(), $headers = array())
    {
        return $this->request(self::METHOD_PATCH, $requestUri, $params, $headers);
    }

    /**
     * @param $requestUri
     * @param $params
     * @param $headers
     * @return ResponseInterface
     */
    public function delete($requestUri, $params = array(), $headers = array())
    {
        return $this->request(self::METHOD_DELETE, $requestUri, $params, $headers);
    }

    /**
     * @param $requestUri
     * @param $params
     * @param $headers
     * @return ResponseInterface
     */
    public function options($requestUri, $params = array(), $headers = array())
    {
        return $this->request(self::METHOD_OPTIONS, $requestUri, $params, $headers);
    }

    /**
     * @param $requestUri
     * @param $params
     * @param $headers
     * @return ResponseInterface
     */
    public function head($requestUri, $params = array(), $headers = array())
    {
        return $this->request(self::METHOD_HEAD, $requestUri, $params, $headers);
    }

    protected function setUp()
    {
        $this->getConnection()->getConnection()->query("SET foreign_key_checks = 0");
        parent::setUp();
        $this->getConnection()->getConnection()->query("SET foreign_key_checks = 1");

        //clear entity manager
        $this->getEntityManager()->clear();

        //clear acl permissions cache
        $this->getContainer()->get('acl')->clearCache();

        //clear auth storage
        $this->getContainer()->get('auth')->getStorage()->clear();
    }

    final public function getConnection()
    {
        if (self::$connection === null) {
            $pdoConnection = $this->getEntityManager()->getConnection()->getWrappedConnection();
            self::$connection = $this->createDefaultDBConnection($pdoConnection);
        }

        return self::$connection;
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->getApp()->getContainer()->get('entityManager');
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->getApp()->getContainer();
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    protected function getAuthorizationHeaders($username, $password, $client)
    {
        $hash = md5($client . $username . $password);
        if (isset($this->authHeaders[$hash])) {
            return $this->authHeaders[$hash];
        }

        $response = $this->post(
            $this->urlFor(AuthRouteConfig::ROUTE_AUTH_TOKEN),
            array(
                'grant_type' => 'password',
                'client_id' => $client,
                'username' => $username,
                'password' => $password
            )
        );

        $result = json_decode($response->getBody());

        $this->authHeaders[$hash] = array(
            'Authorization' => 'Bearer ' . $result->access_token,
            'Client-Version' => '1.6.1');

        return $this->authHeaders[$hash];
    }

    /**
     * @param $requestUri
     * @param $params
     * @param $headers
     * @return ResponseInterface
     */
    public function post($requestUri, $params = array(), $headers = array())
    {
        return $this->request(self::METHOD_POST, $requestUri, $params, $headers);
    }

    /**
     * @param string $routeName
     * @param array $params
     * @param array $queryParams
     * @return string
     */
    public function urlFor($routeName, $params = array(), $queryParams = array())
    {
        /** @var Router $router */
        $router = $this->getApp()->getContainer()->get('router');
        return $router->pathFor($routeName, $params, $queryParams);
    }

    /**
     * @return DebugStack
     */
    protected function getSqlLogger()
    {
        return $this->getContainer()->get('sqlLogger');
    }

    /**
     * Override your deprecated method
     */
    public function getMock(
        $originalClassName,
        $methods = array(),
        array $arguments = array(),
        $mockClassName = '',
        $callOriginalConstructor = true,
        $callOriginalClone = true,
        $callAutoload = true,
        $cloneArguments = false,
        $callOriginalMethods = false,
        $proxyTarget = null
    ) {
        $mb = $this->getMockBuilder($originalClassName)
            ->setMethods($methods)
            ->setConstructorArgs($arguments)
            ->setMockClassName($mockClassName)
            ->setProxyTarget($proxyTarget);
        $callOriginalConstructor ? $mb->enableOriginalConstructor() : $mb->disableOriginalConstructor();
        $callOriginalClone ? $mb->enableOriginalClone() : $mb->disableOriginalClone();
        $cloneArguments ? $mb->enableArgumentCloning() : $mb->disableArgumentCloning();
        $callOriginalMethods ? $mb->enableProxyingToOriginalMethods() : $mb->disableProxyingToOriginalMethods();
        return $mb->getMock();
    }
}
