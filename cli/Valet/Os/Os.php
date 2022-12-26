<?php

namespace Valet\Os;

abstract class Os
{
    abstract public function installer(): Installer;

    public static function assign()
    {
        if (PHP_OS === 'Darwin') {
            return new Mac();
        }

        return new Linux();
    }
}
