<?php

namespace Valet;

use Exception;
use DomainException;

class Ubuntu
{
    var $cli, $files;

    /**
     * Create a new Ubuntu instance.
     *
     * @param  CommandLine  $cli
     * @param  Filesystem  $files
     * @return void
     */
    function __construct(CommandLine $cli, Filesystem $files)
    {
        $this->cli = $cli;
        $this->files = $files;
    }

    /**
     * Determine if the given formula is installed.
     *
     * @param  string  $package
     * @return bool
     */
    function installed($package)
    {
        return in_array($package, explode(PHP_EOL, $this->cli->run('dpkg --get-selections | grep '.$package)));
    }

    /**
     * Determine if a compatible PHP version is installed.
     *
     * @return bool
     */
    function hasInstalledPhp()
    {
        return $this->installed('php7.0')
            || $this->installed('php5.6')
            || $this->installed('php5.5');
    }

    /**
     * Ensure that the given formula is installed.
     *
     * @param  string  $package
     * @return void
     */
    function ensureInstalled($package)
    {
        if (! $this->installed($package)) {
            $this->installOrFail($package);
        }
    }

    /**
     * Install the given formula and throw an exception on failure.
     *
     * @param  string  $package
     * @return void
     */
    function installOrFail($package)
    {
        output('<info>['.$package.'] is not installed, installing it now via Ubuntu...</info> 🍻');

        $this->cli->run('apt-get install '.$package, function ($errorOutput) use ($package) {
            output($errorOutput);

            throw new DomainException('Ubuntu was unable to install ['.$package.'].');
        });
    }

    /**
     * Restart the given Homebrew services.
     *
     * @param
     */
    function restartService($services)
    {
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            $this->cli->quietly('sudo service '.$service.' restart');
        }
    }

    /**
     * Stop the given Homebrew services.
     *
     * @param
     */
    function stopService($services)
    {
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            $this->cli->quietly('sudo service '.$service.' stop');
        }
    }

    /**
     * Determine which version of PHP is linked in Homebrew.
     *
     * @return string
     */
    function linkedPhp()
    {
        if (! $this->files->isLink('/usr/bin/php')) {
            throw new DomainException("Unable to determine linked PHP.");
        }

        $resolvedPath = $this->files->readLink('/usr/bin/php');

        if (strpos($resolvedPath, 'php7.0') !== false) {
            return 'php7.0';
        } elseif (strpos($resolvedPath, 'php5.6') !== false) {
            return 'php5.6';
        } elseif (strpos($resolvedPath, 'php5.5') !== false) {
            return 'php5.5';
        } else {
            throw new DomainException("Unable to determine linked PHP.");
        }
    }

    /**
     * Restart the linked PHP-FPM Homebrew service.
     *
     * @return void
     */
    function restartLinkedPhp()
    {
        $this->restartService($this->linkedPhp());
    }
}
