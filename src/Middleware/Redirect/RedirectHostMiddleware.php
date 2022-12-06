<?php
namespace Pyncer\App\Middleware\Redirect;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Pyncer\Exception\InvalidArgumentException;
use Pyncer\Http\Message\Status;
use Pyncer\Http\Server\MiddlewareInterface;
use Pyncer\Http\Server\RequestHandlerInterface;

use function in_array;

class RedirectHostMiddleware implements MiddlewareInterface
{
    private string $host;
    private ?Status $redirectStatus;

    public function __construct(string $host, ?Status $redirectStatus = null)
    {
        $this->setHost($host);
        $this->setRedirectStatus($redirectStatus);
    }

    public function getHost(): string
    {
        return $this->host;
    }
    public function setHost(string $value): static
    {
        $this->host = $value;
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
                'Invalid redirect status specified, expected null, 301 or 302.'
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

        if ($this->getHost() === '' || $this->getHost() === $uri->getHost()) {
            return $handler->next($request, $response);
        }

        $uri = $uri->withHost($this->getHost());

        $status = $this->getRedirectStatus();
        if ($status !== null) {
            if ($status === Status::REDIRECTION_301_MOVED_PERMANENTLY) {
                $response = $response->withHeader(
                    'Cache-Control',
                    'max-age=86400'
                );
            }

            return $response->withStatus($status->value)
                ->withHeader('Location', strval($uri));
        }

        $request = $request->withUri($uri);

        return $handler->next($request, $response);
    }
}
