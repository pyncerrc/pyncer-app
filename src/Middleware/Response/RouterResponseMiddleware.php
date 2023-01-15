<?php
namespace Pyncer\App\Middleware\Response;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Pyncer\App\Identifier as ID;
use Pyncer\Exception\UnexpectedValueException;
use Pyncer\Http\Message\JsonResponseInterface;
use Pyncer\Http\Message\Status;
use Pyncer\Http\Server\MiddlewareInterface;
use Pyncer\Http\Server\RequestHandlerInterface;
use Pyncer\Http\Server\RequestResponseInterface;

class RouterResponseMiddleware implements MiddlewareInterface
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
        if (!$handler->has(ID::ROUTER)) {
            throw new UnexpectedValueException('Router expected.');
        }

        $router = $handler->get(ID::ROUTER);
        if (!$router instanceof RequestResponseInterface) {
            throw new UnexpectedValueException('Invalid router.');
        }

        $routerResponse = $router->getResponse($handler);

        if ($routerResponse === null) {
            $status = Status::CLIENT_ERROR_404_NOT_FOUND;

            $response = $response->withStatus(
                $status->getStatusCode(),
                $status->getReasonPhrase()
            );

            return $handler->next($request, $response);
        }

        if ($routerResponse instanceof JsonResponseInterface) {
            if ($this->getJsonp()) {
                $callback = $request->getQueryParams()['callback'] ?? '';
            } else {
                $callback = '';
            }

            if ($routerResponse->getCallback() !== $callback) {
                $routerResponse = $routerResponse->withCallback($callback);
            }
        }

        // Normal response from here on
        $response = $response->withStatus(
            $routerResponse->getStatusCode(),
            $routerResponse->getReasonPhrase()
        );

        $response = $response->withBody($routerResponse->getBody());

        foreach ($routerResponse->getHeaders() as $key => $header) {
            $response = $response->withHeader($key, $header);
        }

        return $handler->next($request, $response);
    }
}
