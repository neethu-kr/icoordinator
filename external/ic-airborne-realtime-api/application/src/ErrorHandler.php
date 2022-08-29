<?php

namespace iCoordinator;

use Exception;
use iCoordinator\Controller\AbstractRestController;
use iCoordinator\Service\Exception\ConflictException;
use iCoordinator\Service\Exception\LockedException;
use iCoordinator\Service\Exception\NotFoundException;
use iCoordinator\Service\Exception\ValidationFailedException;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LogLevel;

/**
 * Default error handler
 *
 * This is the default Slim application error handler. All it does is output
 * a clean and simple HTML page with diagnostic information.
 */
class ErrorHandler
{
    use ContainerAwareTrait;

    /**
     * @var Logger
     */
    protected $logger;

    public function __construct(ContainerInterface $c, Logger $logger)
    {
        $this->container = $c;
        $this->logger = $logger;
    }

    /**
     *
     * Invoke error handler
     *
     * @param ServerRequestInterface $request The most recent Request object
     * @param ResponseInterface $response The most recent Response object
     * @param Exception $exception The caught Exception object
     *
     * @return ResponseInterface
     * @throws \Exception
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, Exception $exception)
    {
        switch (true) {
            case $exception instanceof ValidationFailedException:
                return $response->withStatus(
                    AbstractRestController::STATUS_BAD_REQUEST,
                    $exception->getMessage()
                );
                break;
            case $exception instanceof ConflictException:
                return $response->withStatus(
                    AbstractRestController::STATUS_CONFLICT,
                    $exception->getMessage()
                );
                break;
            case $exception instanceof LockedException:
                return $response->withStatus(
                    AbstractRestController::STATUS_LOCKED,
                    $exception->getMessage()
                );
                break;
            case $exception instanceof NotFoundException:
                return $response->withStatus(
                    AbstractRestController::STATUS_NOT_FOUND,
                    $exception->getMessage()
                );
                break;
            default:
                $settings = $this->container->get('settings');

                $this->logger->log(
                    LogLevel::ERROR,
                    sprintf(
                        'Uncaught Exception %s: "%s" at %s line %s',
                        get_class($exception),
                        $exception->getMessage(),
                        $exception->getFile(),
                        $exception->getLine()
                    ),
                    [
                        'url' => (string)$request->getUri(),
                        'bodyParams' => $request->getParsedBody(),
                        'queryParams' => $request->getQueryParams(),
                        'cookies' => $request->getCookieParams()
                    ]
                );

                if (isset($settings['mode']) && !in_array($settings['mode'], ['staging', 'production'])) {
                    throw $exception;
                } else {
                    return $response->withStatus(AbstractRestController::STATUS_INTERNAL_SERVER_ERROR);
                }
                break;
        }
    }
}
