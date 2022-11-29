<?php
namespace Pyncer\App\Middleware;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Pyncer\App\Identifier as ID;
use Pyncer\Exception\UnexpectedValueException;
use Pyncer\Http\Message\DataResponseInterface;
use Pyncer\Http\Server\MiddlewareInterface;
use Pyncer\Http\Server\RequestHandlerInterface;
use Pyncer\Routing\ResponseInterface;

class JsonMiddleware implements MiddlewareInterface
{
    private bool $jsonp;

    public function __construct(bool $jsonp = false)
    {
        $this->setJsonp($jsonp);
    }

    public function getJsonp(): bool
    {
        return $this->jsonp;
    }
    public function setJsonp(bool $value): static
    {
        $this->jsonp = $value;
        return $this;
    }

    public function __invoke(
        PsrServerRequestInterface $request,
        PsrResponseInterface $response,
        RequestHandlerInterface $handler
    ): PsrResponseInterface
    {
        $router = $handler->get(ID::ROUTER);

        if (!$router) {
            throw new UnexpectedValueException('Router expected.');
        } elseif (!($router instanceof ResponseInterface)) {
            throw new UnexpectedValueException('Invalid router.');
        }

        $dataResponse = $router->getResponse($handler);

        if ($dataResponse instanceof DataResponseInterface) {
            if ($this->getJsonp()) {
                $callback = $request->getQueryParams()['callback'] ?? '';
            } else {
                $callback = '';
            }

            if ($dataResponse->getJsonpCallback() !== $callback) {
                $dataResponse = $dataResponse->withJsonpCallback($callback);
            }
        }

        // Normal response from here on
        $response = $response->withStatus(
            $dataResponse->getStatusCode(),
            $dataResponse->getReasonPhrase()
        );

        $response = $response->withBody($dataResponse->getBody());

        foreach ($dataResponse->getHeaders() as $key => $header) {
            $response = $response->withHeader($key, $header);
        }

        return $handler->next($request, $response);
    }
}
