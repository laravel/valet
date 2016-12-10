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
     * @param  array $options
     * @param  array $taps
     * @return void
     */
    function ensureInstalled($formula, $options = [], $taps = [])
    {
        // TODO: Implement ensureInstalled() method.
    }

    /**
     * Install the given formula and throw an exception on failure.
     *
     * @param  string $formula
     * @param  array $options
     * @param  array $taps
     * @return void
     */
    function installOrFail($formula, $options = [], $taps = [])
    {
        // TODO: Implement installOrFail() method.
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
