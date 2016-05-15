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
        return in_array($package, explode(PHP_EOL, $this->cli->run('dpkg -l | grep '.$package.' | sed \'s_  _\t_g\' | cut -f 2')));
    }

    /**
     * Determine if a compatible PHP version is installed.
     *
     * @return bool
     */
    function hasInstalledPhp()
    {
        return $this->installed(get_config('php-latest'))
            || $this->installed(get_config('php-56'))
            || $this->installed(get_config('php-55'));
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
        if (! $this->files->isLink(get_config('php-bin'))) {
            throw new DomainException("Unable to determine linked PHP.");
        }

        $resolvedPath = $this->files->readLink(get_config('php-bin'));

        if (strpos($resolvedPath, get_config('php-latest')) !== false) {
            return get_config('php-latest');
        } elseif (strpos($resolvedPath, get_config('php-56')) !== false) {
            return get_config('php-56');
        } elseif (strpos($resolvedPath, get_config('php-55')) !== false) {
            return get_config('php-55');
        } else {
            throw new DomainException("Unable to determine linked PHP.");
        }
    }
}
