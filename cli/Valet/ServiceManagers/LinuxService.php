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
            if ($this->hasSystemd()) {
                $status = $this->cli->run('systemctl status '.$this->getRealService($service).' | grep "Active:"');
                $running = strpos(trim($status), 'running');

                if ($running) {
                    return info(ucfirst($service).' is running...');
                } else {
                    return warning(ucfirst($service).' is stopped...');
                }
            }

            return info($this->cli->run('service '.$this->getRealService($service)));
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
     * Enable services.
     *
     * @param
     * @return void
     */
    public function enable($services)
    {
        if ($this->hasSystemd()) {
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
                    warning(ucfirst($service).' not available.');
                    return false;
                }
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

    /**
     * Determine if systemd is available on the system.
     *
     * @return bool
     */
    private function hasSystemd()
    {
        try {
            $this->cli->run('which systemctl', function ($exitCode, $output) {
                throw new DomainException('Systemd not available');
            });

            return true;
        } catch (DomainException $e) {
            return false;
        }
    }
}
