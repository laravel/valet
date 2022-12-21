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

    public function pruneMissingDirectories()
    {
        Configuration::prune();
    }

    public function pruneSymbolicLinks()
    {
        Site::pruneLinks();
    }

    public function fixOldSampleValetDriver()
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

    public function errorIfOldCustomDrivers()
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
