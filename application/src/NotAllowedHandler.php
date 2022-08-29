<?php

namespace iCoordinator;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Body;

class NotAllowedHandler
{
    /**
     * Invoke error handler
     *
     * @param  ServerRequestInterface $request  The most recent Request object
     * @param  ResponseInterface      $response The most recent Response object
     * @param  string[]               $methods  Allowed HTTP methods
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $methods)
    {
        if ($request->getMethod() == 'OPTIONS') {
            return $response->withHeader('Access-Control-Allow-Methods', implode(', ', $methods));
        } else {
            $body = new Body(fopen('php://temp', 'r+'));
            $body->write('<p>Method not allowed. Must be one of: ' . implode(', ', $methods) . '</p>');

            return $response
                ->withStatus(405)
                ->withHeader('Content-type', 'text/html')
                ->withHeader('Allow', implode(', ', $methods))
                ->withBody($body);
        }
    }
}
