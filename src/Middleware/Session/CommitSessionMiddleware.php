<?php
namespace Pyncer\App\Middleware\Session;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Pyncer\App\Identifier as ID;
use Pyncer\Http\Server\MiddlewareInterface;
use Pyncer\Http\Server\RequestHandlerInterface;
use Pyncer\Session\SessionInterface;

class CommitSessionMiddleware implements MiddlewareInterface
{
    public function __invoke(
        PsrServerRequestInterface $request,
        PsrResponseInterface $response,
        RequestHandlerInterface $handler
    ): PsrResponseInterface
    {
        if (!$handler->has(ID::SESSION)) {
            throw new UnexpectedValueException('Session expected.');
        }

        $session = $handler->get(ID::SESSION);
        if (!$session instanceof SessionInterface) {
            throw new UnexpectedValueException('Invalid session.');
        }

        if ($session->hasStarted()) {
            $session->commit();
        }

        return $handler->next($request, $response);
    }
}
