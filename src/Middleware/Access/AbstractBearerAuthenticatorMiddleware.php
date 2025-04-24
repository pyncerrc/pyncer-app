<?php
namespace Pyncer\App\Middleware\Access;

use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Pyncer\App\Identifier as ID;
use Pyncer\App\Middleware\Access\AbstractAuthenticatorMiddleware;
use Pyncer\Access\AuthenticatorInterface;
use Pyncer\Access\BearerAuthenticator;
use Pyncer\Data\Mapper\MapperAdaptorInterface;
use Pyncer\Exception\UnexpectedValueException;
use Pyncer\Http\Server\RequestHandlerInterface;

use function str_starts_with;
use function trim;

abstract class AbstractBearerAuthenticatorMiddleware extends AbstractAuthenticatorMiddleware
{
    private string $tokenMapperAdaptorIdentifier;
    private ?string $accessPath;
    private array $publicPaths;

    public function __construct(
        ?string $tokenMapperAdaptorIdentifier = null,
        ?string $userMapperAdaptorIdentifier = null,
        string $realm = 'app',
        bool $allowGuests = false,
        ?int $guestUserId = null,
        ?string $accessPath = null,
        array $publicPaths = [],
    ) {
        parent::__construct(
            $userMapperAdaptorIdentifier,
            $realm,
            $allowGuests,
            $guestUserId,
        );

        $this->setTokenMapperAdaptorIdentifier(
            $tokenMapperAdaptorIdentifier ?? ID::mapperAdaptor('token')
        );
        $this->setAccessPath($accessPath);
        $this->setPublicPaths($publicPaths);
    }

    public function getTokenMapperAdaptorIdentifier(): string
    {
        return $this->tokenMapperAdaptorIdentifier;
    }
    public function setTokenMapperAdaptorIdentifier(string $value): static
    {
        $this->tokenMapperAdaptorIdentifier = $value;
        return $this;
    }

    protected function getAccessPath(): ?string
    {
        return $this->accessPath;
    }
    protected function setAccessPath(?string $value): static
    {
        if ($value !== null) {
            $value = '/' . trim($value, '/');
        }

        $this->accessPath = $value;
        return $this;
    }

    protected function getPublicPaths(): array
    {
        return $this->publicPaths;
    }
    protected function setPublicPaths(array $value): static
    {
        foreach ($value as $key => $path) {
            if (str_starts_with($path, '@')) {
                $path = explode('/', $path, 2);
                $path[1] = trim($path[1] ?? '', '/');
                $value[$key] = implode('/', $path);
            } else {
                $value[$key] = '/' . trim($path, '/');
            }
        }

        $this->publicPaths = $value;
        return $this;
    }

    protected function forgeAuthenticator(
        PsrServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): AuthenticatorInterface
    {
        if (!$handler->has($this->getTokenMapperAdaptorIdentifier())) {
            throw new UnexpectedValueException(
                'Token mapper adaptor expected.'
            );
        }

        $tokenMapperAdaptor = $handler->get($this->getTokenMapperAdaptorIdentifier());
        if (!$tokenMapperAdaptor instanceof MapperAdaptorInterface) {
            throw new UnexpectedValueException(
                'Invalid token mapper adaptor.'
            );
        }

        if (!$handler->has($this->getUserMapperAdaptorIdentifier())) {
            throw new UnexpectedValueException(
                'User mapper adaptor expected.'
            );
        }

        $userMapperAdaptor = $handler->get($this->getUserMapperAdaptorIdentifier());
        if (!$userMapperAdaptor instanceof MapperAdaptorInterface) {
            throw new UnexpectedValueException(
                'Invalid user mapper adaptor.'
            );
        }

        return $this->forgeBearerAuthenicator(
            $tokenMapperAdaptor,
            $userMapperAdaptor,
            $request,
        );
    }

    abstract protected function forgeBearerAuthenicator(
        MapperAdaptorInterface $tokenMapperAdaptor,
        MapperAdaptorInterface $userMapperAdaptor,
        PsrServerRequestInterface $request,
    ): AuthenticatorInterface;

    public function isAuthorized(
        PsrServerRequestInterface $request,
        AuthenticatorInterface $access
    ): bool
    {
        if (parent::isAuthorized($request, $access)) {
            return true;
        }

        $uriPath = '/' . trim($request->getUri()->getPath(), '/');

        // Access path
        if ($this->getAccessPath() !== null) {
            if ($uriPath === $this->getAccessPath() ||
                str_starts_with($uriPath, $this->getAccessPath() . '/')
            ) {
                return true;
            }
        }

        // Public paths
        foreach ($this->getPublicPaths() as $value) {
            $method = null;
            $path = $value;
            $globPaths = false;

            if (str_starts_with($path, '@')) {
                $path = explode('/', $path, 2);
                $method = strtoupper(substr($path[0], 1));
                $path = '/' . ($path[1] ?? '');
            }

            if (str_ends_with($path, '/*')) {
                $path = substr($path, 0, -2);
                if ($path == '') {
                    $path = '/';
                }
                $globPaths = true;
            }

            if ($method !== null && $method !== $request->getMethod()) {
                continue;
            }

            if ($uriPath === $path) {
                return true;
            }

            if ($globPaths) {
                if ($path === '/' || str_starts_with($uriPath, $path . '/')) {
                    return true;
                }
            }
        }

        return false;
    }
}
