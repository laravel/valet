<?php

namespace Valet\PackageManagers;

use DomainException;
use Valet\CommandLine;
use Valet\Contracts\PackageManager;

class Pacman implements PackageManager
{
    public $cli;

    /**
     * Create a new Pacman instance.
     *
     * @param CommandLine $cli
     * @return void
     */
    public function __construct(CommandLine $cli)
    {
        $this->cli = $cli;
    }

    /**
     * Get array of installed packages
     *
     * @param string $package
     * @return array
     */
    public function packages($package)
    {
        $query = "pacman -Qqs {$package}";

        return explode(PHP_EOL, $this->cli->run($query));
    }

    /**
     * Determine if the given package is installed.
     *
     * @param string $package
     * @return bool
     */
    public function installed($package)
    {
        return in_array($package, $this->packages($package));
    }

    /**
     * Ensure that the given package is installed.
     *
     * @param string $package
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
     * @param string $package
     * @return void
     */
    public function installOrFail($package)
    {
        output('<info>[' . $package . '] is not installed, installing it now via Pacman...</info> 🍻');

        $this->cli->run(trim('pacman --noconfirm --needed -S ' . $package), function ($exitCode, $errorOutput) use ($package) {
            output($errorOutput);

            throw new DomainException('Pacman was unable to install [' . $package . '].');
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
     * Restart dnsmasq in Ubuntu.
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
            $output = $this->cli->run('which pacman', function ($exitCode, $output) {
                throw new DomainException('Pacman not available');
            });

            return $output != '';
        } catch (DomainException $e) {
            return false;
        }
    }
}
