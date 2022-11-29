<?php
namespace Pyncer\App;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface as PsrMiddlewareInterface;
use Pyncer\App\Identifier as ID;
use Pyncer\Container\Container;
use Pyncer\Http\Message\Factory\ServerRequestFactory;
use Pyncer\Http\Message\Factory\ResponseFactory;
use Pyncer\Http\Message\FileStream;
use Pyncer\Http\Server\MiddlewareManager;
use Pyncer\Http\Server\MiddlewareInterface;
use Pyncer\Http\Server\RequestHandlerInterface;

use function header;
use function implode;
use function Pyncer\initialize as pyncer_initialize;
use function sprintf;

class App extends Container implements RequestHandlerInterface
{
    public function __construct(
        ?PsrServerRequestInterface $request = null,
        ?PsrResponseInterface $response = null
    ) {
        if ($request === null) {
            $request = (new ServerRequestFactory())->createServerRequestFromGlobals();
        }

        if ($response === null) {
            $response = (new ResponseFactory())->createResponse();
        }

        $this->set(
            ID::MIDDLEWARE,
            new MiddlewareManager($request, $response, $this)
        );

        $this->initialize();
    }

    protected function initialize(): void
    {
        pyncer_initialize();
    }

    public function count(): int
    {
        return $this->get(ID::MIDDLEWARE)->count();
    }

    public function append(
        PsrMiddlewareInterface|MiddlewareInterface|callable ...$callable
    ): static
    {
        $this->get(ID::MIDDLEWARE)->append(...$callable);
        return $this;
    }

    public function prepend(
        PsrMiddlewareInterface|MiddlewareInterface|callable ...$callable
    ): static
    {
        $this->get(ID::MIDDLEWARE)->prepend(...$callable);
        return $this;
    }

    public function run(
        ?PsrResponseInterface $response = null
    ): PsrResponseInterface
    {
        return $this->get(ID::MIDDLEWARE)->run($response);
    }

    public function handle(
        PsrServerRequestInterface $request
    ): PsrResponseInterface
    {
        return $this->get(ID::MIDDLEWARE)->handle($request);
    }

    public function next(
        PsrServerRequestInterface $request,
        PsrResponseInterface $response
    ): PsrResponseInterface
    {
        return $this->get(ID::MIDDLEWARE)->next($request, $response);
    }

    public function set(string $id, mixed $value): static
    {
        if ($id === ID::LOGGER && $value instanceof PsrLoggerInterface) {
            $this->get(ID::MIDDLEWARE)->setLogger(value);
        }

        return parent::set($id, $value);
    }

    public function send(PsrResponseInterface $response = null): void
    {
        if ($response === null) {
            $response = $this->run();
        }

        $reasonPhrase = $response->getReasonPhrase();
        header(sprintf(
            'HTTP/%s %d%s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            ($reasonPhrase ? ' ' . $reasonPhrase : '')
        ));

        foreach ($response->getHeaders() as $key => $values) {
            header($key . ': ' . implode(',', $values));
        }

        $body = $response->getBody();
        if ($body instanceof FileStream && $body->getUseReadFile()) {
            $body->readFile();
        } else {
            echo $body->getContents();
        }
    }
}
