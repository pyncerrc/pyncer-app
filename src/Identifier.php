<?php
namespace Pyncer\App;

class Identifier
{
    const DATABASE = 'database';
    // const EVENTS = 'events';
    const I18N = 'i18n';
    const LOGGER = 'logger';
    const MIDDLEWARE = 'middleware';
    const PAGE = 'page';
    const ROUTER = 'router';
    const SESSION = 'session';
    const SOURCES = 'sources';
    const TRANSLATOR = 'translator';

    private function __construct()
    {}

    public static function isValid(string $value): bool
    {
        switch ($value) {
            case self::DATABASE:
            // case self::EVENTS:
            case self::I18N:
            case self::LOGGER:
            case self::MIDDLEWARE:
            case self::PAGE:
            case self::ROUTER:
            case self::SESSION:
            case self::SOURCES:
            case self::TRANSLATOR:
                return true;
        }

        return false;
    }
}
