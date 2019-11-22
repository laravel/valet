<?php

namespace Valet\Contracts;

interface PackageManager
{
    /**
     * Determine if the given package is installed.
     *
     * @param string $package
     * @return bool
     */
    public function installed($package);

    /**
     * Ensure that the given package is installed.
     *
     * @param string $package
     * @return void
     */
    public function ensureInstalled($package);

    /**
     * Install the given package and throw an exception on failure.
     *
     * @param string $package
     * @return void
     */
    public function installOrFail($package);

    /**
     * Configure package manager on valet install.
     *
     * @return void
     */
    public function setup();

    /**
     * Restart dnsmasq in distro.
     */
    public function nmRestart($sm);

    /**
     * Determine if package manager is available on the system.
     *
     * @return bool
     */
    public function isAvailable();
}
