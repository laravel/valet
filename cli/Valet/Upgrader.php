<?php

namespace Valet;

use Configuration;
use Site;

class Upgrader
{
    public function __construct(public Filesystem $files)
    {
    }

    /**
     * Run all the upgrades that should be run every time Valet commands are run.
     *
     * @return void
     */
    public function onEveryRun(): void
    {
        $this->relocateOldConfig();
        $this->pruneMissingDirectories();
        $this->pruneSymbolicLinks();
        $this->fixOldSampleValetDriver();
        $this->errorIfOldCustomDrivers();
    }

    /**
     * Relocate config dir to ~/.config/valet/ if found in old location.
     *
     * @return void
     */
    public function relocateOldConfig()
    {
        if (is_dir(VALET_LEGACY_HOME_PATH) && ! is_dir(VALET_HOME_PATH)) {
            Configuration::createConfigurationDirectory();
        }
    }

    /**
     * Prune all non-existent paths from the configuration.
     *
     * @return void
     */
    public function pruneMissingDirectories(): void
    {
        Configuration::prune();
    }

    /**
     * Remove all broken symbolic links in the Valet config Sites diretory.
     *
     * @return void
     */
    public function pruneSymbolicLinks(): void
    {
        Site::pruneLinks();
    }

    /**
     * If the user has the old `SampleValetDriver` without the Valet namespace,
     * replace it with the new `SampleValetDriver` that uses the namespace.
     *
     * @return void
     */
    public function fixOldSampleValetDriver(): void
    {
        $samplePath = VALET_HOME_PATH.'/Drivers/SampleValetDriver.php';

        if ($this->files->exists($samplePath)) {
            if (! str_contains($this->files->get($samplePath), 'namespace')) {
                $this->files->putAsUser(
                    VALET_HOME_PATH.'/Drivers/SampleValetDriver.php',
                    $this->files->getStub('SampleValetDriver.php')
                );
            }
        }
    }

    /**
     * Throw an exception if the user has old (non-namespaced) custom drivers.
     *
     * @return void
     */
    public function errorIfOldCustomDrivers(): void
    {
        $driversPath = VALET_HOME_PATH.'/Drivers';

        foreach ($this->files->scanDir($driversPath) as $driver) {
            if (! str_contains($this->files->get($driversPath.'/'.$driver), 'namespace')) {
                warning('Please make sure all custom drivers have been upgraded for Valet 4.');
                exit;
            }
        }
    }
}
