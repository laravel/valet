<?php

namespace Valet\Os;

use Valet\Status;

abstract class Os
{
    abstract public function installer(): Installer;

    abstract public function status(): Status;

    abstract public function etcDir(): string;

    abstract public function logDir(): string;

    public static function assign()
    {
        if (static::isMac()) {
            return new Mac();
        }

        return new Linux();
    }

    public static function isMac(): bool
    {
        return PHP_OS === 'Darwin';
    }

    public static function isLinux(): bool
    {
        return ! static::isMac();
    }
}
