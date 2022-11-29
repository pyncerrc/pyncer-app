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

    public function __construct(bool $enabled = false)
    {
        $this->setEnabled($enabled);
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
