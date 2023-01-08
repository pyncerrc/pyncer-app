<?php
namespace Pyncer\App\Middleware\Response;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Pyncer\Http\Message\Status;
use Pyncer\Http\Server\MiddlewareInterface;
use Pyncer\Http\Server\RequestHandlerInterface;

use function strval;

class HstsMiddleware implements MiddlewareInterface
{
    private bool $enabled;
    private int $maxAge;
    private bool $includeSubDomains;
    private bool $preload;

    public function __construct(
        bool $enabled = false,
        int $maxAge = 31536000,
        bool $includeSubDomains = false,
        bool $preload = false,
    ) {
        $this->setEnabled($enabled);
        $this->setMaxAge($maxAge);
        $this->setIncludeSubDomains($includeSubDomains);
        $this->setPreload($preload);
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

    public function getMaxAge(): int
    {
        return $this->maxAge;
    }
    public function setMaxAge(int $value): static
    {
        $this->maxAge = $value;
        return $this;
    }

    public function getIncludeSubDomains(): bool
    {
        return $this->includeSubDomains;
    }
    public function setIncludeSubDomains(bool $value): static
    {
        $this->includeSubDomains = $value;
        return $this;
    }

    public function getPreload(): bool
    {
        return $this->preload;
    }
    public function setPreload(bool $value): static
    {
        $this->preload = $value;
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

        $uri = $request->getUri();
        $scheme = $uri->getScheme();

        if ($scheme !== 'https') {
            return $handler->next($request, $response);
        }

        $maxAge = $this->getMaxAge();
        $includeSubDomains = $this->getIncludeSubdomains();
        $preload = $this->getPreload();

        if ($preload) {
            // Minimum 1 year if preload is enabled
            $maxAge = max($maxAge, 31536000);
            $includeSubDomains = true;
        }

        $value = 'max-age=' . $maxAge;

        if ($includeSubDomains) {
            $value .= '; includeSubDomains';

            if ($preload) {
                $value .= '; preload';
            }
        }

        $response = $response->withHeader(
            'Strict-Transport-Security',
            $value
        );

        return $handler->next($request, $response);
    }
}
