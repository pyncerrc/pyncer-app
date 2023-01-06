<?php
namespace Pyncer\App\Middleware\Redirect;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Pyncer\Exception\InvalidArgumentException;
use Pyncer\Http\Server\MiddlewareInterface;
use Pyncer\Http\Server\RequestHandlerInterface;
use Pyncer\Http\Message\Status;

use function in_array;
use function rtrim;
use function str_ends_with;

class SlashMiddleware implements MiddlewareInterface
{
    private bool $includeSlash;
    private array $extensions;
    private ?Status $redirectStatus;

    public function __construct(
        bool $includeSlash = false,
        array $extensions = [],
        ?Status $redirectStatus = null
    ) {
        $this->setIncludedSlash($includeSlash);
        $this->setExtensions($extensions);
        $this->setRedirectStatus($redirectStatus);
    }

    public function getIncludeSlash(): bool
    {
        return $this->includeSlash;
    }
    public function setIncludedSlash(bool $value): static
    {
        $this->includeSlash = $value;
        return $this;
    }

    public function getExtensions(): array
    {
        return $this->extensions;
    }
    public function setExtensions(array $value): static
    {
        $this->extensions = $value;
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
        $path = $uri->getPath();

        if ($path === '' || $path === '/') {
            return $handler->next($request, $response);
        }

        $path = rtrim($path, '/');

        if ($this->getIncludeSlash()) {
            $path .= '/';

            if ($this->getExtensions()) {
                foreach ($this->getExtensions() as $extension) {
                    if (str_ends_with($path, '.' . $extension . '/')) {
                        $path = rtrim($path, '/');
                        break;
                    }
                }
            }
        }

        if ($uri->getPath() !== $path) {
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
        }

        return $handler->next($request, $response);
    }
}
