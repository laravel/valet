<?php

namespace Valet\Context;

abstract class Context
{
    public static function assign()
    {
        if (static::isHerd()) {
            return new Herd();
        }

        return new Standalone();
    }

    public static function isHerd()
    {
        return true; // @todo implement
    }

    public static function isStandalone()
    {
        return ! static::isHerd();
    }

    abstract public function name();
}
