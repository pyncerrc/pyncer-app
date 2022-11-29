<?php
namespace Pyncer\App\Middleware;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Pyncer\Exception\InvalidArgumentException;
use Pyncer\Http\Server\MiddlewareInterface;
use Pyncer\Http\Server\RequestHandlerInterface;
use Pyncer\Http\Message\Status;

use function count;
use function explode;
use function filter_var;
use function in_array;
use function strpos;
use function substr;

class WwwMiddleware implements MiddlewareInterface
{
    private bool $includeWww;
    private ?Status $redirectStatus;

    public function __construct(
        bool $includeWww = false,
        ?Status $redirectStatus = null
    ) {
        $this->setIncludedWww($includeWww);
        $this->setRedirectStatus($redirectStatus);
    }

    public function getIncludeWww(): bool
    {
        return $this->includeWww;
    }
    public function setIncludedWww(bool $value): static
    {
        $this->includeWww = $value;
        return $this;
    }

    public function getRedirectStatus(): ?Status
    {
        return $this->redirectStatus;
    }
    public function setRedirectStatus(?Status $value): static
    {
        if (!in_array(
            $value,
            [
                null,
                Status::REDIRECTION_301_MOVED_PERMANENTLY,
                Status::REDIRECTION_302_FOUND
            ],
            true
        )) {
            throw new InvalidArgumentException(
                'Invalid redirect status specified, expected null, 301, or 302.'
            );
        }

        $this->redirectStatus = $value;
        return $this;
    }

    public function __invoke(
        PsrServerRequestInterface $request,
        PsrResponseInterface $response,
        RequestHandlerInterface $handler
    ): PsrResponseInterface
    {
        $uri = $request->getUri();
        $host = $uri->getHost();

        if ($this->getIncludeWww()) {
            if ($this->canAddWww($host)) {
                $host = 'www' . $host;
            }
        } elseif (strpos($host, 'www.') === 0) {
            $host = substr($host, 4);
        }

        if ($uri->getHost() !== $host) {
            $uri = $uri->withHost($host);

            $status = $this->getRedirectStatus();
            if ($status !== null) {
                if ($status === Status::REDIRECTION_301_MOVED_PERMANENTLY) {
                    $response = $response->withHeader(
                        'Cache-Control',
                        'max-age=86400'
                    );
                }

                return $response->withStatus($status->getStatusCode())
                    ->withHeader('Location', strval($uri));
            }

            $request = $request->withUri($uri);
        }

        return $handler->next($request, $response);
    }

    private function canAddWww($host): bool
    {
        if ($host === '' || filter_var($host, FILTER_VALIDATE_IP)) {
            return false;
        }

        $host = explode('.', $host);

        switch (count($host)) {
            case 1: // localhost
                return false;
            case 2: // example.com
                return true;
            case 3: // example.co.uk
                if ($host[1] === 'co') {
                    return true;
                }
                break;
        }

        return false;
    }
}
