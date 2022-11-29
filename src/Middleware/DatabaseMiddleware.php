<?php
namespace Pyncer\App\Middleware;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Pyncer\App\Identifier as ID;
use Pyncer\Database\Driver;
use Pyncer\Http\Server\MiddlewareInterface;
use Pyncer\Http\Server\RequestHandlerInterface;

class DatabaseMiddleware implements MiddlewareInterface
{
    private Driver $driver;

    public function __construct(Driver $driver)
    {
        $this->setDriver($driver);
    }

    public function getDriver(): Driver
    {
        return $this->driver;
    }
    public function setDriver(Driver $value): static
    {
        $this->driver = $value;
        return $this;
    }

    public function __invoke(
        PsrServerRequestInterface $request,
        PsrResponseInterface $response,
        RequestHandlerInterface $handler
    ): PsrResponseInterface
    {
        $handler->set(ID::DATABASE, $this->driver->getConnection());

        return $handler->next($request, $response);
    }
}
