<?php

namespace Valet\ServiceManagers;

use Valet\CommandLine;
use Valet\Contracts\ServiceManager;

class BrewService implements ServiceManager
{
    var $cli;

    /**
     * Create a new Brew instance.
     *
     * @param  CommandLine  $cli
     */
    public function __construct(CommandLine $cli)
    {
        $this->cli = $cli;
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
            $this->cli->quietly('sudo brew services start '.$service);
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
            $this->cli->quietly('sudo brew services stop '.$service);
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
            $this->cli->quietly('sudo brew services restart '.$service);
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
}
