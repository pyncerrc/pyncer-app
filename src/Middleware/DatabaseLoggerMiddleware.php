<?php
namespace Pyncer\App\Middleware;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Pyncer\App\Identifier as ID;
use Pyncer\Http\Server\MiddlewareInterface;
use Pyncer\Http\Server\RequestHandlerInterface;
use Pyncer\Log\DatabaseLogger;

class DatabaseLoggerMiddleware implements MiddlewareInterface
{
    private string $table;

    public function __construct(string $table) {
        $this->setTable($table);
    }

    public function getTable(): string
    {
        return $this->table;
    }
    public function setTable(string $value): static
    {
        $this->table = $value;
        return $this;
    }

    public function __invoke(
        PsrServerRequestInterface $request,
        PsrResponseInterface $response,
        RequestHandlerInterface $handler
    ): PsrResponseInterface
    {
        $connection = $handler->get(ID::DATABASE);
        $logger = new DatabaseLogger($connection , $this->getTable());

        if ($handler->has(ID::LOGGER)) {
            $logger->inherit($handler->get(ID::LOGGER));
        }

        $handler->set(ID::LOGGER, $logger);

        return $handler->next($request, $response);
    }
}
