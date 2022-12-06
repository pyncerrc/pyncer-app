<?php
namespace Pyncer\App\Middleware\Response;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Pyncer\Exception\InvalidArgumentException;
use Pyncer\Http\Server\MiddlewareInterface;
use Pyncer\Http\Server\RequestHandlerInterface;

use function array_map;
use function boolval;
use function implode;
use function in_array;
use function is_array;
use function strtolower;

class CorsMiddleware implements MiddlewareInterface
{
    private bool $enabled;
    private string|array $allowedOrigins;
    private array $allowedMethods;
    private bool $allowCredentials;
    private array $allowedHeaders;
    private ?array $exposedHeaders;
    private ?int $maxAge;

    public function __construct(
        bool $enabled = false,
        string|array $origins = '*',
        array $methods = ['GET', 'POST', 'PATCH', 'PUT', 'DELETE'],
        bool $credentials = false,
        array $headers = ['Authorization', 'Content-Type'],
        ?array $exposedHeaders = null,
        ?int $maxAge = null
    ) {
        $this->setEnabled($enabled);
        $this->setAllowedOrigins($origins);
        $this->setAllowedMethods($methods);
        $this->setAllowCredentials($credentials);
        $this->setAllowedHeaders($headers);
        $this->setExposedHeaders($exposedHeaders);
        $this->setMaxAge($maxAge);
    }

    public function getEnabled(): bool
    {
        return $this->enabled;
    }
    public function setEnabled(bool $value): static
    {
        $this->enabled = $value;
        return $this;
    }

    public function getAllowedOrigins(): string|array
    {
        return $this->allowedOrigins;
    }
    public function setAllowedOrigins(string|array $value): static
    {
        $this->allowedOrigins = $value;
        return $this;
    }

    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }
    public function setAllowedMethods(array $value): static
    {
        $this->allowedMethods = array_map('strtoupper', $value);
        return $this;
    }

    public function getAllowCredentials(): bool
    {
        return $this->allowCredentials;
    }
    public function setAllowCredentials($value): static
    {
        $this->allowCredentials = boolval($value);
        return $this;
    }

    public function getAllowedHeaders(): array
    {
        return $this->allowedHeaders;
    }
    public function setAllowedHeaders(array $value): static
    {
        $value = array_map('strtolower', $value);
        $this->allowedHeaders = $value;
        return $this;
    }

    public function getExposedHeaders(): ?array
    {
        return $this->exposedHeaders;
    }
    public function setExposedHeaders(?array $value): static
    {
        if ($value !== null) {
            $value = array_map('strtolower', $value);
        }
        $this->exposedHeaders = $value;
        return $this;
    }

    public function getMaxAge(): ?int
    {
        return $this->maxAge;
    }
    public function setMaxAge(?int $value): static
    {
        if ($value !== null && $value < 0) {
            throw new InvalidArgumentException('Max age must be greater than zero.');
        }

        $this->maxAge = $value;
        return $this;
    }

    public function __invoke(
        PsrServerRequestInterface $request,
        PsrResponseInterface $response,
        RequestHandlerInterface $handler
    ): PsrResponseInterface
    {
        if (!$this->getEnabled()) {
            return $handler->next($request, $response);
        }

        $origin = $request->getHeader('Origin');
        if ($origin && $this->isValidOrigin($origin[0])) {
            $response = $response->withHeader(
                'Access-Control-Allow-Origin',
                $origin[0]
            );

            if ($request->getMethod() == 'OPTIONS') {
                $requestMethod = $request->getHeaderLine(
                    'Access-Control-Request-Method'
                );
                if (in_array($requestMethod, $this->getAllowedMethods())) {
                    $methods = $this->getAllowedMethods();
                    $methods = implode(',', $methods);
                    $response = $response->withHeader(
                        'Access-Control-Allow-Methods',
                        $methods
                    );
                }

                $requestHeaders = $request->getHeader(
                    'Access-Control-Request-Headers'
                );
                $requestHeaders = $this->cleanRequestHeaders($requestHeaders);
                if ($requestHeaders) {
                    $requestHeaders = implode(',', $requestHeaders);
                    $response = $response->withHeader(
                        'Access-Control-Allow-Headers',
                        $requestHeaders
                    );
                }

                if ($this->getMaxAge() !== null) {
                    $response = $response->withHeader(
                        'Access-Control-Max-Age',
                        $this->getMaxAge()
                    );
                }
            }

            if ($this->getAllowCredentials()) {
                $response = $response->withHeader(
                    'Access-Control-Allow-Credentials',
                    'true'
                );
            }

            if ($this->getExposedHeaders() !== null) {
                $response = $response->withHeader(
                    'Access-Control-Expose-Headers',
                    implode(',', $this->getExposeHeaders())
                );
            }
        }

        if ($request->getMethod() == 'OPTIONS') {
            return $response;
        }

        return $handler->next($request, $response);
    }

    protected function isValidOrigin($origin): bool
    {
        $origins = $this->getAllowedOrigins();

        if ($origins === '*' || in_array('*', $origins, true)) {
            return true;
        }

        if (is_array($origins)) {
            return in_array($origin, $origins, true);
        }

        return ($origin === $origins);
    }

    protected function cleanRequestHeaders(array $value): array
    {
        $cleanHeaders = [];

        foreach ($value as $headers) {
            $headers = explode(',', $headers);
            $headers = array_map('trim', $headers);

            foreach ($headers as $header) {
                $matchHeader = strtolower($header);

                if (!in_array($matchHeader, $this->getAllowedHeaders())) {
                    continue;
                }

                $cleanHeaders[] = $header;
            }
        }

        return $cleanHeaders;
    }
}
