<?php
namespace Pyncer\App\Middleware;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Pyncer\App\Identifier as ID;
use Pyncer\Exception\UnexpectedValueException;
use Pyncer\Http\Server\MiddlewareInterface;
use Pyncer\Http\Server\RequestHandlerInterface;
use Pyncer\Session\SessionInterface;
use Pyncer\Utility\Exception\TokenMismatchException;

use function in_array;

class VerifyCsrfTokenMiddleware implements MiddlewareInterface
{
    public function __invoke(
        PsrServerRequestInterface $request,
        PsrResponseInterface $response,
        RequestHandlerInterface $handler
    ): PsrResponseInterface
    {
        if (!$this->isTokenMatchRequired($request)) {
            return $handler->next($request, $response);
        }

        $session = $handler->get(ID::SESSION);

        if (!$session) {
            throw new UnexpectedValueException('Session expected.');
        } elseif (!($session instanceof SessionInterface)) {
            throw new UnexpectedValueException('Invalid session.');
        }

        $csrfToken = $session->getCsrfToken();
        if (!$csrfToken->equals($this->getCsrfToken($request))) {
            throw new TokenMismatchException();
        }

        return $handler->next($request, $response);
    }

    protected function getCsrfToken(PsrRequestInterface $request): ?string
    {
        $token = $request->getHeader('X-Csrf-Token');
        if ($token) {
            return $token[0];
        }

        return null;
    }

    protected function isTokenMatchRequired(PsrRequestInterface $request): bool
    {
        return !in_array($request->getMethod(), ['HEAD', 'GET', 'OPTIONS']);
    }
}
