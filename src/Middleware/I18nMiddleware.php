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
    private string $sourceMapIdentifier;
    private array $localeCodes;
    private string $defaultLocaleCode;
    private ?string $fallbackLocaleCode;

    public function __construct(
        string $sourceMapIdentifier,
        array $localeCodes,
        string $defaultLocaleCode,
        ?string $fallbackLocaleCode = null,
    ) {
        $this->setSourceMapIdentifier($sourceMapIdentifier);
        $this->setLocaleCodes($localeCodes);
        $this->setDefaultLocaleCode($defaultLocaleCode);
        $this->setFallbackLocaleCode($fallbackLocaleCode);
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

    public function getLocaleCodes(): array
    {
        return $this->localeCodes;
    }
    public function setLocaleCodes(array $value): static
    {
        $this->localeCodes = $value;
        return $this;
    }

    public function getDefaultLocaleCode(): string
    {
        return $this->defaultLocaleCode;
    }
    public function setDefaultLocaleCode(string $value): static
    {
        $this->defaultLocaleCode = $value;
        return $this;
    }

    public function getFallbackLocaleCode(): ?string
    {
        return $this->fallbackLocaleCode;
    }
    public function setFallbackLocaleCode(?string $value): static
    {
        $this->fallbackLocaleCode = $value;
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

        foreach ($this->getLocaleCodes() as $localeCode) {
            $i18n->addLocale($localeCode);
        }

        $defaultLocale = $this->getRequestLocale($request) ??
            $this->getDefaultLocaleCode();

        $i18n->setDefaultLocaleCode($defaultLocale);

        $i18n->setFallbackLocaleCode($this->getFallbackLocaleCode());

        $handler->set(ID::I18N, $i18n);

        return $handler->next($request, $response);
    }

    private function getRequestLocale(
        PsrServerRequestInterface $request,
    ): ?string
    {
        $header = $request->getHeader('Accept-Language');

        if (!$header) {
            return null;
        }

        $header = $header[0];

        if ($header === '') {
            return null;
        }

        $languages = explode(',', $header);
        foreach ($languages as $key => $language) {
            // Remove quality score
            // Ex "en-US,en;q=0.5"
            $language = explode(';', $language)[0];

            foreach ($this->getLocaleCodes() as $localeCode) {
                if ($localeCode === $language) {
                    return $language;
                }

                $language = explode('-', $language, 2);
                $language = $language[0];

                $localeShortCode = substr($localeCode, 0, strlen($language . '-'));

                if ($localeShortCode === $language . '-') {
                    return $language;
                }
            }
        }

        return null;
    }
}
