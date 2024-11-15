<?php
namespace Pyncer\App\Middleware\Log;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Psr\Log\LoggerInterface as PsrLoggerInterface;
use Pyncer\App\Identifier as ID;
use Pyncer\Data\Mapper\MapperAdaptorInterface;
use Pyncer\Exception\UnexpectedValueException;
use Pyncer\Http\Server\MiddlewareInterface;
use Pyncer\Http\Server\RequestHandlerInterface;
use Pyncer\Log\DatabaseLogger;
use Pyncer\Log\GroupLogger;

class DatabaseLoggerMiddleware implements MiddlewareInterface
{
    private string $mapperAdaptorIdentifier;

    public function __construct(
        ?string $mapperAdaptorIdentifier = null,
    ) {
        $this->setMapperAdaptorIdentifier(
            $mapperAdaptorIdentifier ?? ID::mapperAdaptor('log')
        );
    }

    public function getMapperAdaptorIdentifier(): string
    {
        return $this->mapperAdaptorIdentifier;
    }
    public function setMapperAdaptorIdentifier(string $value): static
    {
        $this->mapperAdaptorIdentifier = $value;
        return $this;
    }

    public function __invoke(
        PsrServerRequestInterface $request,
        PsrResponseInterface $response,
        RequestHandlerInterface $handler
    ): PsrResponseInterface
    {
        if (!$handler->has($this->getMapperAdaptorIdentifier())) {
            throw new UnexpectedValueException('Mapper adaptor expected.');
        }

        $mapperAdaptor = $handler->get($this->getMapperAdaptorIdentifier());
        if (!$mapperAdaptor instanceof MapperAdaptorInterface) {
            throw new UnexpectedValueException(
                'Invalid mapper adaptor.'
            );
        }

        $logger = new DatabaseLogger($mapperAdaptor);

        if ($handler->has(ID::LOGGER)) {
            $existingLogger = $handler->get(ID::LOGGER);

            if ($existingLogger instanceof GroupLogger) {
                $existingLogger->addLogger($logger);
            } elseif ($existingLogger instanceof PsrLoggerInterface) {
                $logger->inherit($existingLogger);
                $handler->set(ID::LOGGER, $logger);
            } else {
                throw new UnexpectedValueException('Invalid logger.');
            }
        } else {
            $handler->set(ID::LOGGER, $logger);
        }

        return $handler->next($request, $response);
    }
}
