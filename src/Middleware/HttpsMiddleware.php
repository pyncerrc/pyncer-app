<?php
namespace Pyncer\App\Middleware;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Pyncer\Http\Message\Status;
use Pyncer\Http\Server\MiddlewareInterface;
use Pyncer\Http\Server\RequestHandlerInterface;

use function strval;

class HttpsMiddleware implements MiddlewareInterface
{
    private bool $enabled;
    private bool $forceHttps;

    public function __construct(
        bool $enabled = false,
        bool $forceHttps = false
    ) {
        $this->setEnabled($enabled);
        $this->setForceHttps($forceHttps);
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

    public function getForceHttps(): bool
    {
        return $this->forceHttps;
    }
    public function setForceHttps(bool $value): static
    {
        $this->forceHttps = $value;
        return $this;
    }

    public function __invoke(
        PsrServerRequestInterface $request,
        PsrResponseInterface $response,
        RequestHandlerInterface $handler
    ): PsrResponseInterface
    {
        if (!$this->getEnabled()) {
            return $handler->next($request, $response);
        }

        $update = false;

        if ($this->getForceHttps()) {
            $upgrade = true;
        } else {
            $upgradeHeader = $request->getHeaderLine('Upgrade-Insecure-Requests');
            if ($upgradeHeader) {
                $upgrade = true;
            }
        }

        if (!$upgrade) {
            return $handler->next($request, $response);
        }

        $uri = $request->getUri();

        $scheme = $uri->getScheme();
        if ($scheme !== 'https') {
            $uri = $uri->withScheme('https');
            $uri = $uri->withPort(null);

            $status = Status::REDIRECTION_302_FOUND;
            return $response->withStatus($status->getStatusCode())
                ->withHeader('Location', strval($uri));
        }

        return $handler->next($request, $response);
    }
}
