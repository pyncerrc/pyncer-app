<?php
namespace Pyncer\App\Middleware\Redirect;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Pyncer\Exception\InvalidArgumentException;
use Pyncer\Http\Message\Status;
use Pyncer\Http\Server\MiddlewareInterface;
use Pyncer\Http\Server\RequestHandlerInterface;

use function in_array;
use function explode;
use function ksort;
use function Pyncer\String\ltrim_string as pyncer_ltrim_string;
use function rtrim;
use function substr;
use function str_starts_with;
use function trim;

class RedirectsMiddleware implements MiddlewareInterface
{
    private array $redirects;
    private ?Status $redirectStatus;

    public function __construct(
        array $redirects,
        ?Status $redirectStatus = null
    ) {
        $this->setRedirects($redirects);
        $this->setRedirectStatus($redirectStatus);
    }

    public function getRedirects(): array
    {
        return $this->redirects;
    }
    public function setRedirects(array $value): static
    {
        $this->redirects = $value;
        // Ensure logest matches are first
        krsort($this->redirects);
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
        $host = $uri->getHost();
        $path = trim($uri->getPath(), '/');
        if ($path !== '') {
            $path = '/' . $path;
        }

        $current = $host . $path;

        foreach ($this->getRedirects() as $old => $new) {
            $old = rtrim($old, '/');
            $new = rtrim($new, '/');

            if (substr($old, 0, 1) == '/') {
                $old = $host . $old;
            }

            if (!str_starts_with($current, $old)) {
                continue;
            }

            $new = explode('/', $new, 2);
            if ($new[0] !== '') {
                $uri = $uri->withHost($new[0]);
            }

            $path = '';
            if (isset($new[1]) && $new[1] !== '') {
                $path = '/' . $new[1];
            }

            $end = pyncer_ltrim_string($current, $old);
            if ($end !== '') {
                $path .= $end;
            }

            $uri = $uri->withPath($path);

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
            break;
        }

        return $handler->next($request, $response);
    }
}
