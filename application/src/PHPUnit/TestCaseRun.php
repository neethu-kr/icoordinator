<?php

namespace iCoordinator\PHPUnit;

class TestCaseRun
{
    private $method;

    private $requestUri;

    private $bodyParams = [];

    private $headers = [
        'Content-Type' => 'application/json'
    ];

    /**
     * @param $method
     * @param $requestUri
     * @param array $bodyParams
     * @param array $headers
     */
    public function __construct($method, $requestUri, $bodyParams = [], $headers = array())
    {
        $this->method = $method;
        $this->requestUri = $requestUri;
        $this->bodyParams = $bodyParams;
        $this->headers = array_merge($this->headers, $headers);
    }

    /**
     * @return mixed
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return mixed
     */
    public function getRequestUri()
    {
        return $this->requestUri;
    }

    /**
     * @return array
     */
    public function getBodyParams()
    {
        return $this->bodyParams;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }
}
