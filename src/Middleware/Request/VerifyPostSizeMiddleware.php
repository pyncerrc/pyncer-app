<?php
namespace Pyncer\App\Middleware\Request;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Psr\Log\LoggerAwareInterface as PsrLoggerAwareInterface;
use Psr\Log\LoggerAwareTrait as PsrLoggerAwareTrait;
use Pyncer\Exception\RuntimeException;
use Pyncer\Http\Server\MiddlewareInterface;
use Pyncer\Http\Server\RequestHandlerInterface;

use function ini_get;
use function intval;
use function substr;

class VerifyPostSizeMiddleware  implements
    MiddlewareInterface,
    PsrLoggerAwareInterface
{
    use PsrLoggerAwareTrait;

    public function __invoke(
        PsrServerRequestInterface $request,
        PsrResponseInterface $response,
        RequestHandlerInterface $handler
    ): PsrResponseInterface
    {
        $length = $request->getHeader('Content-Length');
        if ($length && intval($length[0]) > $this->getPostMaxSize()) {
            if ($this->logger) {
                // TODO: context like url or something
                $this->logger->info('Max post size reached.');
            }

            throw new RuntimeException('Post size is too big.');
        }

        return $handler->next($request, $response);
    }

    /**
     * Determine the server 'post_max_size' as bytes.
     *
     * @return int
     */
    protected function getPostMaxSize(): int
    {
        $postMaxSize = ini_get('post_max_size');

        switch (substr($postMaxSize, -1)) {
            case 'K':
            case 'k':
                return intval($postMaxSize) * 1024;
            case 'M':
            case 'm':
                return intval($postMaxSize) * 1048576;
            case 'G':
            case 'g':
                return intval($postMaxSize) * 1073741824;
        }

        return intval($postMaxSize);
    }
}
