<?php
namespace Pyncer\App\Middleware\Log;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Psr\Log\LoggerInterface as PsrLoggerInterface;
use Pyncer\App\Identifier as ID;
use Pyncer\Exception\UnexpectedValueException;
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
            $existingLogger = $handler->get(ID::LOGGER);

            if ($existingLogger instanceof PsrLoggerInterface) {
                $logger->inherit($existingLogger);
            } else {
                throw new UnexpectedValueException('Invalid logger.');
            }

        }

        $handler->set(ID::LOGGER, $logger);

        return $handler->next($request, $response);
    }
}
