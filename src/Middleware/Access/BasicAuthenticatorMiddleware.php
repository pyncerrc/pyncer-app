<?php
namespace Pyncer\App\Middleware\Access;

use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Pyncer\App\Middleware\Access\AbstractBasicAuthenticatorMiddleware;
use Pyncer\Access\AuthenticatorInterface;
use Pyncer\Access\BasicAuthenticator;
use Pyncer\Data\Mapper\MapperAdaptorInterface;

class BasicAuthenticatorMiddleware extends AbstractBasicAuthenticatorMiddleware
{
    protected function forgeBasicAuthenicator(
        MapperAdaptorInterface $userMapperAdaptor,
        PsrServerRequestInterface $request,
    ): AuthenticatorInterface
    {
        return new BasicAuthenticator(
            userMapperAdaptor: $userMapperAdaptor,
            request: $request,
            realm: $this->getRealm(),
            guestUserId: $this->getGuestUserId(),
        );
    }
}
