<?php
namespace Pyncer\App\Middleware\Routing;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Psr\Log\LoggerAwareInterface as PsrLoggerAwareInterface;
use Psr\Log\LoggerAwareTrait as PsrLoggerAwareTrait;
use Psr\Log\LoggerInterface as PsrLoggerInterface;
use Pyncer\App\Identifier as ID;
use Pyncer\Exception\UnexpectedValueException;
use Pyncer\Http\Server\MiddlewareInterface;
use Pyncer\Http\Server\RequestHandlerInterface;
use Pyncer\I18n\I18n;
use Pyncer\Routing\PageRouter;
use Pyncer\Routing\I18nPageRouter;
use Pyncer\Routing\Path\AliasRoutingPath;
use Pyncer\Routing\Path\Base64IdRoutingPath;
use Pyncer\Routing\Path\GlobRoutingPath;
use Pyncer\Routing\Path\IdRoutingPath;
use Pyncer\Routing\Path\UidRoutingPath;
use Pyncer\Source\SourceMapInterface;

use function Pyncer\Http\clean_path as pyncer_http_clean_path;

class PageRouterMiddleware implements
    MiddlewareInterface,
    PsrLoggerAwareInterface
{
    use PsrLoggerAwareTrait;

    private string $sourceMapIdentifier;
    private bool $enableI18n;
    private bool $enableRewriting;
    private bool $enableRedirects;
    private string $baseUrlPath;
    private string $allowedPathCharacters;

    public function __construct(
        string $sourceMapIdentifier,
        bool $enableI18n = false,
        bool $enableRewriting = false,
        bool $enableRedirects = false,
        string $baseUrlPath = '',
        string $allowedPathCharacters = '-'
    ) {
        $this->setSourceMapIdentifier($sourceMapIdentifier);
        $this->setEnableI18n($enableI18n);
        $this->setEnableRewriting($enableRewriting);
        $this->setEnableRedirects($enableRedirects);
        $this->setBaseUrlPath($baseUrlPath);
        $this->setAllowedPathCharacters($allowedPathCharacters);
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

    public function getEnableI18n(): bool
    {
        return $this->enableI18n;
    }
    public function setEnableI18n(bool $value): static
    {
        $this->enableI18n = $value;
        return $this;
    }

    public function getEnableRewriting(): bool
    {
        return $this->enableRewriting;
    }
    public function setEnableRewriting(bool $value): static
    {
        $this->enableRewriting = $value;
        return $this;
    }

    public function getEnableRedirects(): bool
    {
        return $this->enableRedirects;
    }
    public function setEnableRedirects(bool $value): static
    {
        $this->enableRedirects = $value;
        return $this;
    }

    public function getBaseUrlPath(): string
    {
        return $this->baseUrlPath;
    }
    public function setBaseUrlPath(string $value): static
    {
        $this->baseUrlPath = pyncer_http_clean_path($value);
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
        if (!$handler->has($this->getSourceMapIdentifier())) {
            throw new UnexpectedValueException('Source map expected.');
        }

        $sourceMap = $handler->get($this->getSourceMapIdentifier());
        if (!$sourceMap instanceof SourceMapInterface) {
            throw new UnexpectedValueException('Invalid source map.');
        }

        if ($this->getEnableI18n() && $handler->has(ID::I18N)) {
            $i18n = $handler->get(ID::I18N);

            if (!$i18n instanceof I18n) {
                throw new UnexpectedValueException('Invalid i18n.');
            }

            $router = new I18nPageRouter(
                $sourceMap,
                $request,
                $i18n
            );
        } else {
            $router = new PageRouter(
                $sourceMap,
                $request
            );
        }

        if ($this->logger) {
            $router->setLogger($this->logger);
        } elseif ($handler->has(ID::LOGGER)) {
            $logger = $handler->get(ID::LOGGER);

            if ($logger instanceof PsrLoggerInterface) {
                $router->setLogger($logger);
            } else {
                throw new UnexpectedValueException('Invalid logger.');
            }
        }

        $router->setEnableRewriting($this->getEnableRewriting());

        $router->setEnableRedirects($this->getEnableRedirects());

        $router->setBaseUrlPath($this->getBaseUrlPath());

        $router->setAllowedPathCharacters($this->getAllowedPathCharacters());

        $router->getRoutingPaths()->add(
            new IdRoutingPath(),
            new UidRoutingPath(),
            new Base64IdRoutingPath(),
            new AliasRoutingPath(),
            new GlobRoutingPath()
        );

        $router->initialize();

        $handler->set(ID::ROUTER, $router);

        return $handler->next($request, $response);
    }
}
