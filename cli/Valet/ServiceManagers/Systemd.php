<?php

namespace Valet\ServiceManagers;

use DomainException;
use Valet\CommandLine;
use Valet\Contracts\ServiceManager;

class Systemd implements ServiceManager
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
            $this->cli->quietly('sudo systemctl start ' . $this->getRealService($service));
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
            $this->cli->quietly('sudo systemctl stop ' . $this->getRealService($service));
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
            $this->cli->quietly('sudo systemctl restart ' . $this->getRealService($service));
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
            $status = $this->cli->run('systemctl status '.$this->getRealService($service).' | grep "Active:"');
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
        return $this->cli->run('systemctl status '.$this->getRealService($service));
    }

    /**
     * Enable services.
     *
     * @param
     * @return void
     */
    public function enable($services)
    {
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            try {
                $service = $this->getRealService($service);
                $enabled = strpos(trim($this->cli->run("systemctl is-enabled {$service}")), 'enabled');

                if ($enabled === false) {
                    $this->cli->quietly('sudo systemctl enable ' . $service);
                    info(ucfirst($service).' has been enabled');
                    return true;
                }

                info(ucfirst($service).' was already enabled');

                return true;
            } catch (DomainException $e) {
                warning(ucfirst($service).' unavailable.');
                return false;
            }
        }
    }

    /**
     * Determine if service manager is available on the system.
     *
     * @return bool
     */
    public function isAvailable()
    {
        try {
            $output = $this->cli->run('which systemctl', function ($exitCode, $output) {
                throw new DomainException('Systemd not available');
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
            return strpos($this->cli->run("systemctl status {$service} | grep Loaded"), 'Loaded: loaded');
        }, function () {
            throw new DomainException("Unable to determine service name.");
        });
    }
}
