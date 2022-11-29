<?php
namespace Pyncer\App\Middleware;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Pyncer\App\Identifier as ID;
use Pyncer\Http\Server\MiddlewareInterface;
use Pyncer\Http\Server\RequestHandlerInterface;
use Pyncer\Session\PhpSession;

class StartPhpSessionMiddleware implements MiddlewareInterface
{
    private ?string $prefix;

    public function __construct(string $prefix = null)
    {
        $this->setPrefix($prefix);
    }

    public function getPrefix(): ?string
    {
        return $this->prefix;
    }
    public function setPrefix(?string $value): static
    {
        if ($value === '') {
            $value = null;
        }

        $this->prefix = $value;
        return $this;
    }

    public function __invoke(
        PsrServerRequestInterface $request,
        PsrResponseInterface $response,
        RequestHandlerInterface $handler
    ): PsrResponseInterface
    {
        $session = new PhpSession($this->getPrefix());
        $session->start();

        $handler->set(ID::SESSION, $session);

        return $handler->next($request, $response);
    }
}
