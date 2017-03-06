<?php

namespace Valet\ServiceManagers;

use DomainException;
use Valet\CommandLine;
use Valet\Contracts\ServiceManager;

class LinuxService implements ServiceManager
{
    public $cli;

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
    public function start($services)
    {
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            info("Starting $service...");
            $this->cli->quietly('sudo service ' . $this->getRealService($service) . ' start');
        }
    }

    /**
     * Stop the given services.
     *
     * @param
     * @return void
     */
    public function stop($services)
    {
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            info("Stopping $service...");
            $this->cli->quietly('sudo service ' . $this->getRealService($service) . ' stop');
        }
    }

    /**
     * Restart the given services.
     *
     * @param
     * @return void
     */
    public function restart($services)
    {
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            info("Restarting $service...");
            $this->cli->quietly('sudo service ' . $this->getRealService($service) . ' restart');
        }
    }

    /**
     * Status of the given services.
     *
     * @param
     * @return void
     */
    public function printStatus($services)
    {
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            $status = $this->cli->run('service '.$this->getRealService($service).' status | grep "Active:"');
            $running = strpos(trim($status), 'running');

            if ($running) {
                info(ucfirst($service).' is running...');
            } else {
                warning(ucfirst($service).' is stopped...');
            }
        }
    }

    /**
     * Status of the given services.
     *
     * @param
     * @return void
     */
    public function status($service)
    {
        return $this->cli->run('service '.$this->getRealService($service).' status');
    }

    /**
     * Determine if service manager is available on the system.
     *
     * @return bool
     */
    public function isAvailable()
    {
        try {
            $output = $this->cli->run('which service', function ($exitCode, $output) {
                throw new DomainException('Service not available');
            });

            return $output != '';
        } catch (DomainException $e) {
            return false;
        }
    }

    /**
     * Determine real service name
     *
     * @param string $service
     * @return string
     */
    public function getRealService($service)
    {
        return collect($service)->first(function ($service) {
            return !strpos($this->cli->run('service ' . $service . ' status'), 'not-found');
        }, function () {
            throw new DomainException("Unable to determine service name.");
        });
    }
}
