<?php
namespace Pyncer\App\Middleware\Log;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Pyncer\App\Identifier as ID;
use Pyncer\Http\Server\MiddlewareInterface;
use Pyncer\Http\Server\RequestHandlerInterface;
use Pyncer\Log\TemporaryLogger;

class TemporaryLoggerMiddleware implements MiddlewareInterface
{
    public function __invoke(
        PsrServerRequestInterface $request,
        PsrResponseInterface $response,
        RequestHandlerInterface $handler
    ): PsrResponseInterface
    {
        $logger = new TemporaryLogger();

        if ($handler->has(ID::LOGGER)) {
            $logger->inherit($handler->get(ID::LOGGER));
        }

        $handler->set(ID::LOGGER, $logger);

        return $handler->next($request, $response);
    }
}
