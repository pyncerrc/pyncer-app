<?php
namespace Pyncer\App\Middleware\Session;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Pyncer\App\Identifier as ID;
use Pyncer\Http\Message\Cookie;
use Pyncer\Http\Server\MiddlewareInterface;
use Pyncer\Http\Server\RequestHandlerInterface;
use Pyncer\Session\PhpSession;

class PhpSessionMiddleware implements MiddlewareInterface
{
    private ?Cookie $cookie;
    private array $options;
    private ?int $idExpirationInterval;

    public function __construct(
        Cookie $cookie = null,
        array $options = [],
        ?int $idExpirationInterval = null,
    ) {
        $this->setCookie($cookie);
        $this->setOptions($options);
        $this->setIdExpirationInterval($idExpirationInterval);
    }

    public function getCookie(): ?Cookie
    {
        return $this->cookie;
    }
    public function setCookie(?Cookie $value): static
    {
        $this->cookie = $value;
        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
    public function setOptions(array $value): static
    {
        $this->options = $value;
        return $this;
    }

    public function getIdExpirationInterval(): ?int
    {
        return $this->idExpirationInterval;
    }
    public function setIdExpirationInterval(?int $value): static
    {
        $this->idExpirationInterval = $value ?: null;
        return $this;
    }

    public function __invoke(
        PsrServerRequestInterface $request,
        PsrResponseInterface $response,
        RequestHandlerInterface $handler
    ): PsrResponseInterface
    {
        $cookie = $this->getCookie();

        $session = new PhpSession(
            $cookie ? $cookie->getName() : null,
            $this->getOptions(),
            $this->getIdExpirationInterval(),
        );

        if ($cookie === null) {
            $cookie = new Cookie($session->getName());
        }

        $cookieParams = $request->getCookieParams();
        $id = $cookieParams[$session->getName()] ?? null;
        if ($id !== null) {
            $session->setId($id);
        }

        $session->start();

        $handler->set(ID::SESSION, $session);

        $response = $handler->next($request, $response);

        $session->commit();

        $cookie->setValue($session->getId() ?? '');

        $response = $response->withAddedHeader('Set-Cookie', $cookie);

        return $response;
    }
}
