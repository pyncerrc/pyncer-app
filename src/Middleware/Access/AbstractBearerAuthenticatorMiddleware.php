<?php
namespace Pyncer\App\Middleware\Access;

use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Pyncer\App\Identifier as ID;
use Pyncer\App\Middleware\Access\AbstractAuthenticatorMiddleware;
use Pyncer\Access\AuthenticatorInterface;
use Pyncer\Access\BearerAuthenticator;
use Pyncer\Data\Mapper\MapperAdaptorInterface;
use Pyncer\Exception\UnexpectedValueException;
use Pyncer\Http\Message\Status;
use Pyncer\Http\Server\RequestHandlerInterface;

use function str_starts_with;
use function trim;

abstract class AbstractBearerAuthenticatorMiddleware extends AbstractAuthenticatorMiddleware
{
    private string $tokenMapperAdaptorIdentifier;
    private ?string $accessPath;

    public function __construct(
        string $tokenMapperAdaptorIdentifier,
        string $userMapperAdaptorIdentifier,
        string $realm,
        bool $allowGuests = false,
        ?string $accessPath = null,
    ) {
        parent::__construct(
            $userMapperAdaptorIdentifier,
            $realm,
            $allowGuests
        );

        $this->setTokenMapperAdaptorIdentifier($tokenMapperAdaptorIdentifier);
        $this->setAccessPath($accessPath);
    }

    public function getTokenMapperAdaptorIdentifier(): ?string
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
        PsrServerRequestInterface $request
    ): AuthenticatorInterface;

    public function isAuthorized(
        PsrServerRequestInterface $request,
        AuthenticatorInterface $access
    ): bool
    {
        if (parent::isAuthorized($request, $access)) {
            return true;
        }

        if ($this->getAccessPath() === null) {
            return false;
        }

        $uri = $request->getUri();
        if ($uri->getPath() === $this->getAccessPath() ||
            str_starts_with($uri->getPath(), $this->getAccessPath() . '/')
        ) {
            return true;
        }

        return false;
    }
}
