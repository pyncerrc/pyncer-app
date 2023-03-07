<?php
namespace Pyncer\App;

use Pyncer\Exception\LogicException;

use function Pyncer\Utility\to_snake_case as pyncer_to_snake_case;

class Identifier
{
    const ACCESS = 'access';
    const DATABASE = 'database';
    const I18N = 'i18n';
    const INSTALL = 'install';
    const LOGGER = 'logger';
    const MAPPER_ADAPTOR = 'mapper_adaptor';
    const MIDDLEWARE = 'middleware';
    const ROUTER = 'router';
    const SESSION = 'session';
    const SOURCE_MAP = 'source_map';
    const SNYPPET = 'snyppet';

    protected static array $identifiers = [];

    private function __construct()
    {}

    public static function register(string $identifier): void
    {
        if (strtolower($identifier) !== $identifier) {
            throw new LogicException('Invalid identifier name. (' . $identifier . ')');
        }

        if (!array_key_exists($identifier, static::$identifiers)) {
            static::$identifiers[$identifier] = [];
        }
    }

    public static function isValid(string $value): bool
    {
        if (array_key_exists($value, static::$identifiers)) {
            return true;
        }

        switch ($value) {
            case static::ACCESS:
            case static::DATABASE:
            case static::I18N:
            case static::INSTALL:
            case static::LOGGER:
            case static::MAPPER_ADAPTOR:
            case static::MIDDLEWARE:
            case static::ROUTER:
            case static::SESSION:
            case static::SOURCE_MAP:
            case static::SNYPPET:
                return true;
        }

        return false;
    }

    public static function __callStatic(string $name, array $arguments): mixed
    {
        $value = pyncer_to_snake_case($name);

        if (!static::isValid($value)) {
            throw new LogicException('Identifier not found. (' . $name . ')');
        }

        if ($arguments) {
            $arguments = array_map('strval', $arguments);
            $arguments = implode('__', $arguments);

            $value .= '__' . $arguments;
        }

        return $value;
    }
}
