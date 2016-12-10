<?php

namespace Valet\PackageManagers;

use Valet\Contracts\PackageManager;

class Apt implements PackageManager
{

    /**
     * Determine if the given formula is installed.
     *
     * @param  string $formula
     * @return bool
     */
    function installed($formula)
    {
        // TODO: Implement installed() method.
    }

    /**
     * Ensure that the given formula is installed.
     *
     * @param  string $formula
     * @return void
     */
    function ensureInstalled($formula)
    {
        // TODO: Implement ensureInstalled() method.
    }

    /**
     * Install the given formula and throw an exception on failure.
     *
     * @param  string $formula
     * @return void
     */
    function installOrFail($formula)
    {
        // TODO: Implement installOrFail() method.
    }

    /**
     * Return full path to etc configuration.
     *
     * @param  string $path
     * @return string
     */
    function etcDir($path = '')
    {
        return '/etc' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * Return full path to log.
     *
     * @param  string $path
     * @return string
     */
    function logDir($path = '')
    {
        return '/var/log' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * Configure package manager on valet install.
     *
     * @return void
     */
    function setup()
    {
        // TODO: Implement setup() method.
    }

    /**
     * Determine if package manager is available on the system.
     *
     * @return bool
     */
    function isAvailable()
    {
        return exec('which apt') != '';
    }
}
