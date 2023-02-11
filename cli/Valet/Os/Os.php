<?php

namespace Valet\Os;

abstract class Os
{
    abstract public function installer(): Installer;

    public static function assign()
    {
        if (static::isMac()) {
            return new Mac();
        }

        return new Linux();
    }

    public static function isMac(): bool
    {
        return (PHP_OS === 'Darwin');
    }

    public static function isLinux(): bool
    {
        return ! static::isMac();
    }
}
