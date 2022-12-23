<?php

namespace Valet\Os;

class Os
{
    public static function assign()
    {
        if (PHP_OS === 'Darwin') {
            return new Mac();
        }

        return new Linux();
    }
}
