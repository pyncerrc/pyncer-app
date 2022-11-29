<?php
namespace Pyncer\App\Middleware;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Pyncer\Exception\InvalidArgumentException;
use Pyncer\Http\Server\MiddlewareInterface;
use Pyncer\Http\Server\RequestHandlerInterface;
use Pyncer\Http\Message\Status as SC;

use function in_array;
use function strval;

class RedirectUriMiddleware implements MiddlewareInterface
{
    private string $uri;
    private ?Status $redirectStatus;

    public function __construct(string $uri, ?Status $redirectStatus = null)
    {
        $this->setUri($uri);
        $this->setRedirectStatus($redirectStatus);
    }

    public function getUri(): string
    {
        return $this->uri;
    }
    public function setUri(string $value): static
    {
        $this->uri = $value;
        return $this;
    }

    public function getRedirectStatus(): Status
    {
        return $this->redirectStatus;
    }
    public function setRedirectStatus(Status $value): static
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
                'Invalid redirect status code specified, expected null, 301 or 302.'
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

        if ($this->getUri() === '' || $this->getUri() === strval($uri)) {
            return $handler->next($request, $response);
        }

        $status = $this->getRedirectStatus();
        if (isset($status)) {
            if ($status === Status::REDIRECTION_301_MOVED_PERMANENTLY) {
                $response = $response->withHeader(
                    'Cache-Control',
                    'max-age=86400'
                );
            }

            return $response->withStatus($status->getStatusCode())
                ->withHeader('Location', $this->getUri());
        }

        $request = $request->withUri($this->getUri());

        return $handler->next($request, $response);
    }
}
