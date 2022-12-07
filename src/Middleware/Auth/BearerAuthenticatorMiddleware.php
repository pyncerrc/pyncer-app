<?php
namespace Pyncer\App\Middleware\Auth;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Psr\Log\LoggerAwareInterface as PsrLoggerAwareInterface;
use Psr\Log\LoggerAwareTrait as PsrLoggerAwareTrait;
use Psr\Log\LoggerInterface as PsrLoggerInterface;
use Pyncer\App\Identifier as ID;
use Pyncer\App\Middleware\Auth\AuthenticatorMiddlewareTrait;
use Pyncer\Auth\AuthenticatorInterface;
use Pyncer\Auth\BearerAuthenticator;
use Pyncer\Data\Mapper\MapperAdaptorInterface;
use Pyncer\Exception\UnexpectedValueException;
use Pyncer\Http\Message\Status;
use Pyncer\Http\Server\MiddlewareInterface;
use Pyncer\Http\Server\RequestHandlerInterface;

use function str_starts_with;
use function trim;

class BearerAuthenticatorMiddleware implements
    MiddlewareInterface,
    PsrLoggerAwareInterface
{
    use AuthenticatorMiddlewareTrait;
    use PsrLoggerAwareTrait;

    private string $tokenMapperAdaptorIdentifier;
    private ?string $authPath;

    public function __construct(
        string $tokenMapperAdaptorIdentifier,
        string $userMapperAdaptorIdentifier,
        string $realm,
        bool $allowGuests = false,
        ?string $authPath = null,
    ) {
        $this->setTokenMapperAdaptorIdentifier($tokenMapperAdaptorIdentifier);
        $this->setUserMapperAdaptorIdentifier($userMapperAdaptorIdentifier);
        $this->setRealm($realm);
        $this->setAllowGuests($allowGuests);
        $this->setAuthPath($authPath);
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

    protected function getAuthPath(): ?string
    {
        return $this->authPath;
    }
    protected function setAuthPath(?string $value): static
    {
        $this->authPath = '/' . trim($value, '/');
        return $this;
    }

    public function __invoke(
        PsrServerRequestInterface $request,
        PsrResponseInterface $response,
        RequestHandlerInterface $handler
    ): PsrResponseInterface
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

        $auth = new BearerAuthenticator(
            $tokenMapperAdaptor,
            $userMapperAdaptor,
            $request,
            $this->getRealm()
        );

        if ($this->logger) {
            $auth->setLogger($this->logger);
        } elseif ($handler->has(ID::LOGGER)) {
            $logger = $handler->get(ID::LOGGER);

            if ($logger instanceof PsrLoggerInterface) {
                $auth->setLogger($logger);
            } else {
                throw new UnexpectedValueException('Invalid logger.');
            }
        }

        $authResponse = $auth->getResponse($handler);
        if ($authResponse !== null) {
            return $authResponse;
        }

        if (!$this->isAuthorized($request, $auth)) {
            return $auth->getChallengeResponse(
                Status::CLIENT_ERROR_403_FORBIDDEN
            );
        }

        $handler->set(ID::AUTH, $auth);

        return $handler->next($request, $response);
    }

    public function isAuthorized(
        PsrServerRequestInterface $request,
        AuthenticatorInterface $auth
    )
    {
        if ($auth->isUser() || $this->getAllowGuests()) {
            return true;
        }

        if ($this->getAuthPath() === null) {
            return false;
        }

        $uri = $request->getUri();
        if ($uri->getPath() === $this->getAuthPath() ||
            str_starts_with($uri->getPath(), $this->getAuthPath() . '/')
        ) {
            return true;
        }

        return false;
    }
}
