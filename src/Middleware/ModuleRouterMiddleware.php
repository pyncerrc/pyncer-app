<?php
namespace Pyncer\App\Middleware;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Psr\Log\LoggerAwareInterface as PsrLoggerAwareInterface;
use Psr\Log\LoggerAwareTrait as PsrLoggerAwareTrait;
use Pyncer\App\Identifier as ID;
use Pyncer\Http\Server\MiddlewareInterface;
use Pyncer\Http\Server\RequestHandlerInterface;
use Pyncer\Iterable\MapInterface;
use Pyncer\Routing\I18nModuleRouter;
use Pyncer\Routing\ModuleRouter;
use Pyncer\Routing\Path\AliasRoutingPath;
use Pyncer\Routing\Path\Base64IdRoutingPath;
use Pyncer\Routing\Path\GlobRoutingPath;
use Pyncer\Routing\Path\IdRoutingPath;
use Pyncer\Source\SourceMap;

use function Pyncer\Http\clean_path;

class ModuleRouterMiddleware implements
    MiddlewareInterface,
    PsrLoggerAwareInterface
{
    use PsrLoggerAwareTrait;

    private string $sourceName;
    private string $basePath;
    private bool $enableRewriteRules;
    private string $allowedPathCharacters;

    public function __construct(
        string $sourceName,
        bool $enableI18n = false,
        bool $enableRewriteRules = false,
        string $basePath = '',
        string $allowedPathCharacters = '-'
    ) {
        $this->setSourceName($sourceName);
        $this->setEnableI18n($enableI18n);
        $this->setEnableRewriteRules($enableRewriteRules);
        $this->setBasePath($basePath);
        $this->setAllowedPathCharacters($allowedPathCharacters);
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

    public function getEnableI18n(): bool
    {
        return $this->enableI18n;
    }
    public function setEnableI18n(bool $value): static
    {
        $this->enableI18n = $value;
        return $this;
    }

    public function getEnableRewriteRules(): bool
    {
        return $this->enableRewriteRules;
    }
    public function setEnableRewriteRules(bool $value): static
    {
        $this->enableRewriteRules = $value;
        return $this;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }
    public function setBasePath(string $value): static
    {
        $this->basePath = clean_path($value);
        return $this;
    }

    public function getAllowedPathCharacters(): string
    {
        return $this->allowedPathCharacters;
    }
    public function setAllowedPathCharacters(string $value): static
    {
        $this->allowedPathCharacters = $value;
        return $this;
    }

    public function __invoke(
        PsrServerRequestInterface $request,
        PsrResponseInterface $response,
        RequestHandlerInterface $handler
    ): PsrResponseInterface
    {
        $sources = $handler->get(ID::SOURCES);

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

        $i18n = $handler->get(ID::I18N);

        if ($this->getEnableI18n() && $i18n !== null ) {
            $router = new I18nModuleRouter(
                $sourceMap,
                $request,
                $i18n
            );
        } else {
            $router = new ModuleRouter(
                $sourceMap,
                $request
            );
        }

        if ($this->logger) {
            $router->setLogger($this->logger);
        } else {
            $logger = $handler->get(ID::LOGGER);
            if ($logger) {
                $router->setLogger($logger);
            }
        }

        $router->setEnableRewriteRules($this->getEnableRewriteRules());

        $router->setBaseUrlPath($this->getBasePath());

        $router->setAllowedPathCharacters($this->getAllowedPathCharacters());

        $router->getRoutingPaths()->add(
            new IdRoutingPath(),
            new Base64IdRoutingPath(),
            new AliasRoutingPath(),
            new GlobRoutingPath()
        );

        $router->initialize();

        $handler->set(ID::ROUTER, $router);

        return $handler->next($request, $response);
    }
}
