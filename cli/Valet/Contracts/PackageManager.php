<?php

namespace Valet\Contracts;

interface PackageManager
{
    /**
     * Determine if the given package is installed.
     *
     * @param  string  $package
     * @return bool
     */
    function installed($package);

    /**
     * Ensure that the given package is installed.
     *
     * @param  string  $package
     * @return void
     */
    function ensureInstalled($package);

    /**
     * Install the given package and throw an exception on failure.
     *
     * @param  string  $package
     * @return void
     */
    function installOrFail($package);

    /**
     * Configure package manager on valet install.
     *
     * @return void
     */
    function setup();

    /**
     * Restart dnsmasq in distro.
     */
    function dnsmasqRestart($sm);

    /**
     * Determine if package manager is available on the system.
     *
     * @return bool
     */
    function isAvailable();
}
