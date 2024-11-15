<?php
namespace Pyncer\App\Middleware\Log;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Pyncer\App\Identifier as ID;
use Pyncer\Http\Server\MiddlewareInterface;
use Pyncer\Http\Server\RequestHandlerInterface;
use Pyncer\Log\GroupLogger;

class LoggerMiddleware implements MiddlewareInterface
{
    public function __invoke(
        PsrServerRequestInterface $request,
        PsrResponseInterface $response,
        RequestHandlerInterface $handler
    ): PsrResponseInterface
    {
        $logger = new GroupLogger();

        if ($handler->has(ID::LOGGER)) {
            $existingLogger = $handler->get(ID::LOGGER);
            $logger->addLogger($existingLogger);
        }

        $handler->set(ID::LOGGER, $logger);

        return $handler->next($request, $response);
    }
}
