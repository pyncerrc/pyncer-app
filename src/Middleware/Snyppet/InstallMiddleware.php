<?php
namespace Pyncer\App\Middleware\Snyppet;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Pyncer\App\Identifier as ID;
use Pyncer\Data\Mapper\MapperAdaptorInterface;
use Pyncer\Database\ConnectionInterface;
use Pyncer\Exception\UnexpectedValueException;
use Pyncer\Http\Server\MiddlewareInterface;
use Pyncer\Http\Server\RequestHandlerInterface;
use Pyncer\Snyppet\InstallManager;
use Pyncer\Snyppet\SnyppetManager;

class InstallMiddleware implements MiddlewareInterface
{
    private bool $enabled;
    private string $mapperAdaptorIdentifier;
    private bool $install;
    private bool $upgrade;
    private ?array $snyppets;

    public function __construct(
        bool $enabled = false,
        ?string $mapperAdaptorIdentifier = null,
        bool $install = false,
        bool $upgrade = false,
        ?array $snyppets = null,
    ) {
        $this->setEnabled($enabled);
        $this->setMapperAdaptorIdentifier(
            $mapperAdaptorIdentifier ?? ID::mapperAdaptor('install')
        );
        $this->setInstall($install);
        $this->setUpgrade($upgrade);
        $this->setSnyppets($snyppets);
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

    public function getMapperAdaptorIdentifier(): string
    {
        return $this->mapperAdaptorIdentifier;
    }
    public function setMapperAdaptorIdentifier(string $value): static
    {
        $this->mapperAdaptorIdentifier = $value;
        return $this;
    }

    public function getInstall(): bool
    {
        return $this->install;
    }
    public function setInstall(bool $value): static
    {
        $this->install = $value;
        return $this;
    }

    public function getUpgrade(): bool
    {
        return $this->upgrade;
    }
    public function setUpgrade(bool $value): static
    {
        $this->upgrade = $value;
        return $this;
    }

    public function getSnyppets(): ?array
    {
        return $this->snyppets;
    }
    public function setSnyppets(?array $value): static
    {
        $this->snyppets = $value;
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

        // Database
        if (!$handler->has(ID::DATABASE)) {
            throw new UnexpectedValueException(
                'Database connection expected.'
            );
        }

        $connection = $handler->get(ID::DATABASE);
        if (!$connection instanceof ConnectionInterface) {
            throw new UnexpectedValueException('Invalid database connection.');
        }

        // Install mapper adaptor
        if (!$handler->has($this->getMapperAdaptorIdentifier())) {
            throw new UnexpectedValueException('Mapper adaptor expected. (' . $this->getMapperAdaptorIdentifier() . ')');
        }

        $mapperAdaptor = $handler->get($this->getMapperAdaptorIdentifier());
        if (!$mapperAdaptor instanceof MapperAdaptorInterface) {
            throw new UnexpectedValueException(
                'Invalid mapper adaptor.'
            );
        }

        // Snyppet manager
        if (!$handler->has(ID::SNYPPET)) {
            throw new UnexpectedValueException('Snyppet manager expected.');
        }

        $snyppetManager = $handler->get(ID::SNYPPET);
        if (!$snyppetManager instanceof SnyppetManager) {
            throw new UnexpectedValueException(
                'Invalid snyppet manager.'
            );
        }

        $installManager = new InstallManager(
            $connection,
            $mapperAdaptor,
            $snyppetManager
        );

        if ($this->getUpgrade()) {
            $installManager->upgradeAll($this->getSnyppets());
        }

        if ($this->getInstall()) {
            if ($snyppetManager->has('install')) {
                // Ensure install snyppet is installed first
                if (!$installManager->isInstalled('install')) {
                    $installManager->install('install');
                }
            }

            $installManager->installAll($this->getSnyppets());
        }

        $handler->set(ID::INSTALL, $installManager);

        return $handler->next($request, $response);
    }
}
