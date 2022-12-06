<?php
namespace Pyncer\App;

class Identifier
{
    const DATABASE = 'database';
    const I18N = 'i18n';
    const LOGGER = 'logger';
    const MIDDLEWARE = 'middleware';
    const PAGE = 'page';
    const ROUTER = 'router';
    const SESSION = 'session';

    private function __construct()
    {}

    public static function isValid(string $value): bool
    {
        switch ($value) {
            case static::DATABASE:
            case static::I18N:
            case static::LOGGER:
            case static::MIDDLEWARE:
            case static::PAGE:
            case static::ROUTER:
            case static::SESSION:
                return true;
        }

        return false;
    }
}
