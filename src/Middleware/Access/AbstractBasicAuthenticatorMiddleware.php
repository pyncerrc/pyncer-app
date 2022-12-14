<?php
namespace Pyncer\App\Middleware\Access;

use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Pyncer\App\Middleware\Access\AbstractAuthenticatorMiddleware;
use Pyncer\Access\BasicAuthenticator;
use Pyncer\Data\Mapper\MapperAdaptorInterface;
use Pyncer\Exception\UnexpectedValueException;
use Pyncer\Http\Server\RequestHandlerInterface;

abstract class AbstractBasicAuthenticatorMiddleware extends AbstractAuthenticatorMiddleware
{
    protected function forgeAuthenticator(
        PsrServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): AuthenticatorInterface
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

        return $this->forgeBasicAuthenicator(
            $userMapperAdaptor,
            $request,
        );
    }

    abstract protected function forgeBasicAuthenicator(
        MapperAdaptorInterface $userMapperAdaptor,
        PsrServerRequestInterface $request
    ): AuthenticatorInterface;
}
