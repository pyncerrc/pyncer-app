<?php
namespace Pyncer\App\Middleware;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Pyncer\App\Identifier as ID;
use Pyncer\Exception\UnexpectedValueException;
use Pyncer\I18n\I18n;
use Pyncer\Http\Server\MiddlewareInterface;
use Pyncer\Http\Server\RequestHandlerInterface;
use Pyncer\Source\SourceMap;

class I18nMiddleware implements MiddlewareInterface
{
    private string $sourceMapIdentifier

    public function __construct(
        string $sourceMapIdentifier
    ) {
        $this->setSourceMapIdentifier($sourceMapIdentifier);
    }

    public function getSourceMapIdentifier(): string
    {
        return $this->sourceMapIdentifier;
    }
    public function setSourceMapIdentifier(string $value): static
    {
        $this->sourceMapIdentifier = $value;
        return $this;
    }

    public function __invoke(
        PsrServerRequestInterface $request,
        PsrResponseInterface $response,
        RequestHandlerInterface $handler
    ): PsrResponseInterface
    {
        if (!$handler->has($this->getSourceMapIdentifier())) {
            throw new UnexpectedValueException('Source map expected.');
        }

        $sourceMap = $handler->get($this->getSourceMapIdentifier());
        if (!$sourceMap instanceof SourceMap) {
            throw new UnexpectedValueException('Invalid source map.');
        }

        $i18n = new I18n($sourceMap);

        $handler->set(ID::I18N, $i18n);

        return $handler->next($request, $response);
    }
}
