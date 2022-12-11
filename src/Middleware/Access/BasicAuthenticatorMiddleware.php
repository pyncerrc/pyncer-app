<?php
namespace Pyncer\App\Middleware\Access;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Psr\Log\LoggerAwareInterface as PsrLoggerAwareInterface;
use Psr\Log\LoggerAwareTrait as PsrLoggerAwareTrait;
use Psr\Log\LoggerInterface as PsrLoggerInterface;
use Pyncer\App\Identifier as ID;
use Pyncer\App\Middleware\Auth\AuthenticatorMiddlewareTrait;
use Pyncer\Access\BearerAuthenticator;
use Pyncer\Data\Mapper\MapperAdaptorInterface;
use Pyncer\Exception\UnexpectedValueException;
use Pyncer\Http\Message\Status;
use Pyncer\Http\Server\MiddlewareInterface;
use Pyncer\Http\Server\RequestHandlerInterface;

class BasicAuthenticatorMiddleware implements
    MiddlewareInterface,
    PsrLoggerAwareInterface
{
    use AuthenticatorMiddlewareTrait;
    use PsrLoggerAwareTrait;

    private ?string $accessPath;

    public function __construct(
        string $userMapperAdaptorIdentifier,
        string $realm,
        bool $allowGuests = false,
    ) {
        $this->setUserMapperAdaptorIdentifier($userMapperAdaptorIdentifier);
        $this->setRealm($realm);
        $this->setAllowGuests($allowGuests);
    }

    public function __invoke(
        PsrServerRequestInterface $request,
        PsrResponseInterface $response,
        RequestHandlerInterface $handler
    ): PsrResponseInterface
    {
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

        $access = new BasicAuthenticator(
            $userMapperAdaptor,
            $request,
            $this->getRealm()
        );

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

        $accessResponse = $access->getResponse($handler);
        if ($accessResponse !== null) {
            return $accessResponse;
        }

        if ($access->isGuest() && !$this->getAllowGuests()) {
            return $access->getChallengeResponse(
                Status::CLIENT_ERROR_403_FORBIDDEN
            );
        }

        $handler->set(ID::ACCESS, $access);

        return $handler->next($request, $response);
    }
}
