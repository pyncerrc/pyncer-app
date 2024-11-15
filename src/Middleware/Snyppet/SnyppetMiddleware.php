<?php
namespace Pyncer\App\Middleware\Snyppet;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Pyncer\App\Identifier as ID;
use Pyncer\Exception\UnexpectedValueException;
use Pyncer\Http\Server\MiddlewareInterface;
use Pyncer\Http\Server\RequestHandlerInterface;
use Pyncer\Snyppet\InstallManager;
use Pyncer\Snyppet\SnyppetManager;

class SnyppetMiddleware implements MiddlewareInterface
{
    private bool $enabled;
    private string $type;
    private ?array $snyppets;

    public function __construct(
        bool $enabled = false,
        string $type = 'initialize',
        ?array $snyppets = null,
    ) {
        $this->setEnabled($enabled);
        $this->setType($type);
        $this->setSnyppets($snyppets);
    }

    public function getEnabled(): bool
    {
        return $this->enabled;
    }
    public function setEnabled(bool $value): static
    {
        $this->enabled = $value;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }
    public function setType(string $value): static
    {
        $this->type = $value;
        return $this;
    }

    public function getSnyppets(): ?array
    {
        return $this->snyppets;
    }
    public function setSnyppets(?array $value): static
    {
        $this->snyppets = $value;
        return $this;
    }

    public function __invoke(
        PsrServerRequestInterface $request,
        PsrResponseInterface $response,
        RequestHandlerInterface $handler
    ): PsrResponseInterface
    {
        if (!$this->getEnabled()) {
            return $handler->next($request, $response);
        }

        if ($handler->has(ID::SNYPPET)) {
            $snyppetManager = $handler->get(ID::SNYPPET);
        } else {
            $snyppetManager = new SnyppetManager($this->getSnyppets());

            $handler->set(ID::SNYPPET, $snyppetManager);
        }

        $middlewares = [];

        foreach ($snyppetManager as $snyppet) {
            foreach ($snyppet->getMiddlewares($this->getType()) as $middleware) {
                $middlewares[] = $middleware;
            }
        }

        $middlewares = array_reverse($middlewares);

        foreach ($middlewares as $key => $value) {
            $handler->prepend($value);
        }

        return $handler->next($request, $response);
    }
}
