<?php

namespace Valet\PackageManagers;

use DomainException;
use Valet\CommandLine;
use Valet\Contracts\PackageManager;

class Apt implements PackageManager
{
    var $cli;

    /**
     * Create a new Apt instance.
     *
     * @param  CommandLine  $cli
     * @return void
     */
    function __construct(CommandLine $cli)
    {
        $this->cli = $cli;
    }

    /**
     * Determine if the given formula is installed.
     *
     * @param  string $formula
     * @return bool
     */
    function installed($formula)
    {
        return !strpos($this->cli->run('dpkg -l '.$formula), 'no packages found');
    }

    /**
     * Ensure that the given formula is installed.
     *
     * @param  string $formula
     * @return void
     */
    function ensureInstalled($formula)
    {
        if (! $this->installed($formula)) {
            $this->installOrFail($formula);
        }
    }

    /**
     * Install the given formula and throw an exception on failure.
     *
     * @param  string $formula
     * @return void
     */
    function installOrFail($formula)
    {
        output('<info>['.$formula.'] is not installed, installing it now via Apt...</info> 🍻');

        $this->cli->run(trim('apt-get install -y '.$formula), function ($exitCode, $errorOutput) use ($formula) {
            output($errorOutput);

            throw new DomainException('Apt was unable to install ['.$formula.'].');
        });
    }

    /**
     * Return full path to etc configuration.
     *
     * @param  string $path
     * @return string
     */
    function etcDir($path = '')
    {
        return '/etc' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * Return full path to log.
     *
     * @param  string $path
     * @return string
     */
    function logDir($path = '')
    {
        return '/var/log' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * Return full path to opt.
     *
     * @param  string $path
     * @return string
     */
    function optDir($path = '')
    {
        return '/opt' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * Configure package manager on valet install.
     *
     * @return void
     */
    function setup()
    {
        // Nothing to do
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
