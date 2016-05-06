<?php

namespace Valet;

use Symfony\Component\Process\Process;

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

            if ('Linux' === PHP_OS) {
                self::resolveLinuxLaunchDaemon();
            }
        }
        $class = self::$class;

        return constant($class.'::'.$key);
    }

    /**
     * Checks for systemctl in the system. If not found, fallback to upstart.
     */
    protected static function resolveLinuxLaunchDaemon()
    {
        $process = new Process('which systemctl');
        $process->run();

        self::$class = sprintf('\\Valet\\Compatibility%sSystemd', PHP_OS);
        if (0 === strlen(trim($process->getOutput()))) {
            self::$class = sprintf('\\Valet\\Compatibility%sUpstart', PHP_OS);
        }
    }
}