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
     */
    public function onEveryRun(): void
    {
        $this->pruneMissingDirectories();
        $this->pruneSymbolicLinks();
        $this->fixOldSampleValetDriver();
        $this->errorIfOldCustomDrivers();
    }

    /**
     * Prune all non-existent paths from the configuration.
     */
    public function pruneMissingDirectories(): void
    {
        try {
            Configuration::prune();
        } catch (\JsonException $e) {
            warning('Invalid configuration file at '.Configuration::path().'.');
            exit;
        }
    }

    /**
     * Remove all broken symbolic links in the Valet config Sites diretory.
     */
    public function pruneSymbolicLinks(): void
    {
        Site::pruneLinks();
    }

    /**
     * If the user has the old `SampleValetDriver` without the Valet namespace,
     * replace it with the new `SampleValetDriver` that uses the namespace.
     */
    public function fixOldSampleValetDriver(): void
    {
        $samplePath = VALET_HOME_PATH.'/Drivers/SampleValetDriver.php';

        if ($this->files->exists($samplePath)) {
            $contents = $this->files->get($samplePath);

            if (! str_contains($contents, 'namespace')) {
                if ($contents !== $this->files->get(__DIR__.'/../stubs/Valet3SampleValetDriver.php')) {
                    warning('Existing SampleValetDriver.php has been customized.');
                    warning('Backing up at '.$samplePath.'.bak');

                    $this->files->putAsUser(
                        VALET_HOME_PATH.'/Drivers/SampleValetDriver.php.bak',
                        $contents
                    );
                }

                $this->files->putAsUser(
                    VALET_HOME_PATH.'/Drivers/SampleValetDriver.php',
                    $this->files->getStub('SampleValetDriver.php')
                );
            }
        }
    }

    /**
     * Throw an exception if the user has old (non-namespaced) custom drivers.
     */
    public function errorIfOldCustomDrivers(): void
    {
        $driversPath = VALET_HOME_PATH.'/Drivers';

        if (! $this->files->isDir($driversPath)) {
            return;
        }

        foreach ($this->files->scanDir($driversPath) as $driver) {
            if (! ends_with($driver, 'ValetDriver.php')) {
                continue;
            }

            if (! str_contains($this->files->get($driversPath.'/'.$driver), 'namespace')) {
                warning('Please make sure all custom drivers have been upgraded for Valet 4.');
                warning('See the upgrade guide for more info:');
                warning('https://github.com/laravel/valet/blob/master/UPGRADE.md');
                exit;
            }
        }
    }
}
