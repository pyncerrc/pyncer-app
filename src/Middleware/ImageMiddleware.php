<?php
namespace Pyncer\App\Middleware;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Pyncer\App\Identifier as ID;
use Pyncer\Image\Driver;
use Pyncer\Http\Server\MiddlewareInterface;
use Pyncer\Http\Server\RequestHandlerInterface;

class ImageMiddleware implements MiddlewareInterface
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
        $handler->set(ID::IMAGE, function() {
            return $this->driver->getImage();
        });

        return $handler->next($request, $response);
    }
}
