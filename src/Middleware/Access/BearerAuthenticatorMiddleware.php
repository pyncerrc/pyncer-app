<?php
namespace Pyncer\App\Middleware\Access;

use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Pyncer\App\Middleware\Access\AbstractBearerAuthenticatorMiddleware;
use Pyncer\Access\AuthenticatorInterface;
use Pyncer\Access\BearerAuthenticator;
use Pyncer\Data\Mapper\MapperAdaptorInterface;

class BearerAuthenticatorMiddleware extends AbstractBearerAuthenticatorMiddleware
{
    protected function forgeBearerAuthenicator(
        MapperAdaptorInterface $tokenMapperAdaptor,
        MapperAdaptorInterface $userMapperAdaptor,
        PsrServerRequestInterface $request,
    ): AuthenticatorInterface
    {
        return new BearerAuthenticator(
            tokenMapperAdaptor: $tokenMapperAdaptor,
            userMapperAdaptor: $userMapperAdaptor,
            request: $request,
            realm: $this->getRealm(),
            guestUserId: $this->getGuestUserId(),
        );
    }
}
