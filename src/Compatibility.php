<?php

namespace Valet;

class Compatibility
{
    /** @var string */
    protected static $class;

    /**
     * Returns the value required for the current OS running Valet.
     *
     * @param string $key
     * @return string
     */
    public static function get($key)
    {
        if (empty(self::$class)) {
            self::$class = sprintf('\\Valet\\Compatibility%s', PHP_OS);
        }
        $class = self::$class;

        return constant($class.'::'.$key);
    }
}