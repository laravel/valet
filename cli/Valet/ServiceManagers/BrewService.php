<?php

namespace Valet\ServiceManagers;

use DomainException;
use Valet\CommandLine;
use Valet\Contracts\ServiceManager;
use Valet\Filesystem;

class BrewService implements ServiceManager
{
    var $cli, $files;

    /**
     * Create a new Brew instance.
     *
     * @param  CommandLine  $cli
     * @param  Filesystem  $files
     */
    public function __construct(CommandLine $cli, Filesystem $files)
    {
        $this->cli = $cli;
        $this->files = $files;
    }

    /**
     * Start the given services.
     *
     * @param
     * @return void
     */
    function start($services)
    {
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            $this->cli->quietly('sudo brew services start ' . $service);
        }
    }

    /**
     * Stop the given services.
     *
     * @param
     * @return void
     */
    function stop($services)
    {
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            $this->cli->quietly('sudo brew services stop ' . $service);
        }
    }

    /**
     * Restart the given services.
     *
     * @param
     * @return void
     */
    function restart($services)
    {
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            $this->cli->quietly('sudo brew services restart ' . $service);
        }
    }

    /**
     * Determine if service manager is available on the system.
     *
     * @return bool
     */
    function isAvailable()
    {
        return exec('which brew') != '';
    }

    /**
     * Determine real service name
     *
     * @param string $service
     * @return string
     */
    function getRealService($service)
    {
        if ($service == 'php') {
            return $this->linkedPhp();
        }

        return $service;
    }

    /**
     * Determine which version of PHP is linked in Homebrew.
     *
     * @return string
     */
    function linkedPhp()
    {
        if (!$this->files->isLink('/usr/local/bin/php')) {
            throw new DomainException("Unable to determine linked PHP.");
        }

        $resolvedPath = $this->files->readLink('/usr/local/bin/php');

        if (strpos($resolvedPath, 'php71') !== false) {
            return 'php71';
        } elseif (strpos($resolvedPath, 'php70') !== false) {
            return 'php70';
        } elseif (strpos($resolvedPath, 'php56') !== false) {
            return 'php56';
        } elseif (strpos($resolvedPath, 'php55') !== false) {
            return 'php55';
        } else {
            throw new DomainException("Unable to determine linked PHP.");
        }
    }
}
