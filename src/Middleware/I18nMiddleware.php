<?php
namespace Pyncer\App\Middleware;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Pyncer\App\Identifier as ID;
use Pyncer\Exception\UnexpectedValueException;
use Pyncer\I18n\I18n;
use Pyncer\Http\Server\MiddlewareInterface;
use Pyncer\Http\Server\RequestHandlerInterface;
use Pyncer\Iterable\MapInterface;
use Pyncer\Source\SourceMap;

class I18nMiddleware implements MiddlewareInterface
{
    private string $sourceName

    public function __construct(
        string $sourceName
    ) {
        $this->setSourceName($sourceName);
    }

    public function getSourceName(): string
    {
        return $this->sourceName;
    }
    public function setSourceName(string $value): static
    {
        $this->sourceName = $value;
        return $this;
    }

    public function __invoke(
        PsrServerRequestInterface $request,
        PsrResponseInterface $response,
        RequestHandlerInterface $handler
    ): PsrResponseInterface
    {
        $sources = $handler->get(ID:SOURCES);

        if (!$sources) {
            throw new UnexpectedValueException('Sources expected.');
        } elseif (!($sources instanceof MapInterface)) {
            throw new UnexpectedValueException('Invalid sources.');
        }

        $sourceMap = $sources->get($this->getSourceName());

        if (!$sourceMap) {
            throw new UnexpectedValueException('Source map expected.');
        } elseif (!($sourceMap instanceof SourceMap)) {
            throw new UnexpectedValueException('Invalid source map.');
        }

        $i18n = new I18n($sourceMap);

        $handler->set(ID::I18N, $i18n);

        return $handler->next($request, $response);
    }
}
