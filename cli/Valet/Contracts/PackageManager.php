<?php

namespace Valet\Contracts;

interface PackageManager
{
    /**
     * Determine if the given formula is installed.
     *
     * @param  string  $formula
     * @return bool
     */
    function installed($formula);

    /**
     * Ensure that the given formula is installed.
     *
     * @param  string  $formula
     * @return void
     */
    function ensureInstalled($formula);

    /**
     * Install the given formula and throw an exception on failure.
     *
     * @param  string  $formula
     * @return void
     */
    function installOrFail($formula);

    /**
     * Return full path to etc configuration.
     *
     * @param  string  $path
     * @return string
     */
    function etcDir($path);

    /**
     * Return full path to log.
     *
     * @param  string  $path
     * @return string
     */
    function logDir($path);

    /**
     * Configure package manager on valet install.
     *
     * @return void
     */
    function setup();

    /**
     * Determine if package manager is available on the system.
     *
     * @return bool
     */
    function isAvailable();
}
