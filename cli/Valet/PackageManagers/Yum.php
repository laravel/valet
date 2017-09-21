<?php

namespace Valet\PackageManagers;

use DomainException;
use Valet\CommandLine;
use Valet\Contracts\PackageManager;

class Yum implements PackageManager
{
    public $cli;

    /**
     * Create a new Yum instance.
     *
     * @param  CommandLine $cli
     * @return void
     */
    public function __construct(CommandLine $cli)
    {
        $this->cli = $cli;
    }

    /**
     * Determine if the given package is installed.
     *
     * @param  string $package
     * @return bool
     */
    public function installed($package)
    {
        $query = "yum list installed {$package} | grep {$package} | sed 's_  _\\t_g' | sed 's_\\._\\t_g' | cut -f 1";

        $packages = explode(PHP_EOL, $this->cli->run($query));

        return in_array($package, $packages);
    }

    /**
     * Ensure that the given package is installed.
     *
     * @param  string $package
     * @return void
     */
    public function ensureInstalled($package)
    {
        if (!$this->installed($package)) {
            $this->installOrFail($package);
        }
    }

    /**
     * Install the given package and throw an exception on failure.
     *
     * @param  string $package
     * @return void
     */
    public function installOrFail($package)
    {
        output('<info>[' . $package . '] is not installed, installing it now via Yum...</info> 🍻');

        $this->cli->run(trim('yum install -y ' . $package), function ($exitCode, $errorOutput) use ($package) {
            output($errorOutput);

            throw new DomainException('Yum was unable to install [' . $package . '].');
        });
    }

    /**
     * Configure package manager on valet install.
     *
     * @return void
     */
    public function setup()
    {
        // Nothing to do
    }

    /**
     * Restart dnsmasq in Fedora.
     */
    public function nmRestart($sm)
    {
        $sm->restart('NetworkManager');
    }

    /**
     * Determine if package manager is available on the system.
     *
     * @return bool
     */
    public function isAvailable()
    {
        try {
            $output = $this->cli->run('which yum', function ($exitCode, $output) {
                throw new DomainException('Yum not available');
            });

            return $output != '';
        } catch (DomainException $e) {
            return false;
        }
    }
}
