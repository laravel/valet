<?php

namespace Valet\ServiceManagers;

use Valet\CommandLine;
use Valet\Contracts\ServiceManager;

class LinuxService implements ServiceManager
{
    var $cli;

    /**
     * Create a new Brew instance.
     *
     * @param  CommandLine $cli
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
            $this->cli->quietly('sudo service ' . $service . ' start');
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
            $this->cli->quietly('sudo service ' . $service . ' stop');
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
            $this->cli->quietly('sudo service ' . $service . ' restart');
        }
    }

    /**
     * Determine if service manager is available on the system.
     *
     * @return bool
     */
    function isAvailable()
    {
        return exec('which service') != '';
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
            return 'php' . substr(PHP_VERSION, 0, 3) . '-fpm';
        }

        return $service;
    }
}
