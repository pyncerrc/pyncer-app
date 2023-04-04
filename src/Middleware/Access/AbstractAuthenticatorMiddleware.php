<?php
namespace Pyncer\App\Middleware\Access;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Psr\Log\LoggerAwareInterface as PsrLoggerAwareInterface;
use Psr\Log\LoggerAwareTrait as PsrLoggerAwareTrait;
use Psr\Log\LoggerInterface as PsrLoggerInterface;
use Pyncer\App\Identifier as ID;
use Pyncer\Access\AuthenticatorInterface;
use Pyncer\Exception\UnexpectedValueException;
use Pyncer\Http\Message\Status;
use Pyncer\Http\Server\MiddlewareInterface;
use Pyncer\Http\Server\RequestHandlerInterface;

abstract class AbstractAuthenticatorMiddleware implements
    MiddlewareInterface,
    PsrLoggerAwareInterface
{
    use PsrLoggerAwareTrait;

    private string $userMapperAdaptorIdentifier;
    private string $realm;
    private bool $allowGuests;

    public function __construct(
        ?string $userMapperAdaptorIdentifier = null,
        string $realm = 'app',
        bool $allowGuests = false,
    ) {
        $this->setUserMapperAdaptorIdentifier(
            $userMapperAdaptorIdentifier ?? ID::mapperAdaptor('user')
        );
        $this->setRealm($realm);
        $this->setAllowGuests($allowGuests);
    }

    public function getUserMapperAdaptorIdentifier(): string
    {
        return $this->userMapperAdaptorIdentifier;
    }
    public function setUserMapperAdaptorIdentifier(string $value): static
    {
        $this->userMapperAdaptorIdentifier = $value;
        return $this;
    }

    protected function getRealm(): string
    {
        return $this->realm;
    }
    protected function setRealm(string $value): static
    {
        $this->realm = $value;
        return $this;
    }

    protected function getAllowGuests(): bool
    {
        return $this->allowGuests;
    }
    protected function setAllowGuests(bool $value): static
    {
        $this->allowGuests = $value;
        return $this;
    }

    public function __invoke(
        PsrServerRequestInterface $request,
        PsrResponseInterface $response,
        RequestHandlerInterface $handler
    ): PsrResponseInterface
    {
        $access = $this->forgeAuthenticator($request, $handler);

        if ($access instanceof PsrLoggerAwareInterface) {
            if ($this->logger) {
                $access->setLogger($this->logger);
            } elseif ($handler->has(ID::LOGGER)) {
                $logger = $handler->get(ID::LOGGER);

                if ($logger instanceof PsrLoggerInterface) {
                    $access->setLogger($logger);
                } else {
                    throw new UnexpectedValueException('Invalid logger.');
                }
            }
        }

        $accessResponse = $access->getResponse($handler);

        if ($accessResponse === null &&
            !$this->isAuthorized($request, $access)
        ) {
            $accessResponse = $access->getChallengeResponse(
                Status::CLIENT_ERROR_401_UNAUTHORIZED,
                [
                    'error_description' => 'The authorization token is missing.'
                ]
            );
        }

        if ($accessResponse !== null) {
            // Add any headers that were added to current response such as CORS
            foreach ($response->getHeaders() as $key => $header) {
                $accessResponse = $accessResponse->withHeader($key, $header);
            }

            return $accessResponse;
        }

        $handler->set(ID::ACCESS, $access);

        return $handler->next($request, $response);
    }

    abstract protected function forgeAuthenticator(
        PsrServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): AuthenticatorInterface;

    public function isAuthorized(
        PsrServerRequestInterface $request,
        AuthenticatorInterface $access
    ): bool
    {
        if ($access->isUser() || $this->getAllowGuests()) {
            return true;
        }

        return false;
    }
}
