<?php
namespace Pyncer\App\Middleware;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Pyncer\Http\Server\MiddlewareInterface;
use Pyncer\Http\Server\RequestHandlerInterface;

use function error_reporting;
use function ini_set;

class DebugMiddleware implements MiddlewareInterface
{
    private bool $enabled;
    private bool $errorResponse;

    public function __construct(
        bool $enabled = false,
        bool $errorResponse = false,
    ) {
        $this->setEnabled($enabled);
        $this->setErrorResponse($errorResponse);
    }

    public function getEnabled(): bool
    {
        return $this->enabled;
    }
    public function setEnabled(bool $value): static
    {
        $this->enabled = $value;
        return $this;
    }

    public function getErrorResponse(): bool
    {
        return $this->errorResponse;
    }
    public function setErrorResponse(bool $value): static
    {
        $this->errorResponse = $value;
        return $this;
    }

    public function __invoke(
        PsrServerRequestInterface $request,
        PsrResponseInterface $response,
        RequestHandlerInterface $handler
    ): PsrResponseInterface
    {
        if ($this->getEnabled()) {
            // Log and show all errors
            error_reporting(E_ALL);
            ini_set("log_errors", 1);
            ini_set("display_errors", 1);
            ini_set('display_startup_errors', 1);

            if ($this->getErrorResponse()) {
                $handler->onError(function(
                    $request,
                    $response,
                    $handler,
                    $class,
                    $error
                ) {
                    return new Response(
                        Status::SERVER_ERROR_500_INTERNAL_SERVER_ERROR,
                        [],
                        (new StreamFactory())->createStream($class . "\n\n" . strval($error->getException()))
                    );
                });
            }
        } else {
            // We still want errors to be reported to the log file
            error_reporting(E_ALL);
            ini_set("log_errors", 1);
            // Hide errors from output
            ini_set("display_errors", 0);
            ini_set('display_startup_errors', 0);
        }

        return $handler->next($request, $response);
    }
}
