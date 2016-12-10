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
     * @param  array  $options
     * @param  array  $taps
     * @return void
     */
    function ensureInstalled($formula, $options = [], $taps = []);

    /**
     * Install the given formula and throw an exception on failure.
     *
     * @param  string  $formula
     * @param  array  $options
     * @param  array  $taps
     * @return void
     */
    function installOrFail($formula, $options = [], $taps = []);

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
