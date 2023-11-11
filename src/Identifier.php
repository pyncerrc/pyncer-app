<?php
namespace Pyncer\App;

use Pyncer\Exception\LogicException;

use function Pyncer\Utility\to_snake_case as pyncer_to_snake_case;

class Identifier
{
    public const ACCESS = 'access';
    public const DATABASE = 'database';
    public const I18N = 'i18n';
    public const IMAGE = 'image';
    public const INSTALL = 'install';
    public const LOGGER = 'logger';
    public const MAPPER_ADAPTOR = 'mapper_adaptor';
    public const MIDDLEWARE = 'middleware';
    public const ROUTER = 'router';
    public const SESSION = 'session';
    public const SOURCE_MAP = 'source_map';
    public const SNYPPET = 'snyppet';

    protected static array $identifiers = [];

    private function __construct()
    {}

    public static function register(string $identifier): void
    {
        if (strtolower($identifier) !== $identifier) {
            throw new LogicException('Invalid identifier name. (' . $identifier . ')');
        }

        if (!in_array($identifier, static::$identifiers)) {
            static::$identifiers[] = $identifier;
        }
    }

    public static function isValid(string $value): bool
    {
        if (in_array($value, static::$identifiers)) {
            return true;
        }

        switch ($value) {
            case static::ACCESS:
            case static::DATABASE:
            case static::I18N:
            case static::IMAGE:
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

        if ($arguments) {
            $arguments = array_map('strval', $arguments);
            $arguments = implode('__', $arguments);

            $value .= '__' . $arguments;
        }

        return $value;
    }
}
